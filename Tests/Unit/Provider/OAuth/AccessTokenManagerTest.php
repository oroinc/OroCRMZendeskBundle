<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\OAuth;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ZendeskBundle\Entity\Repository\ZendeskRestTransportRepository;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;
use Oro\Bundle\ZendeskBundle\Model\OAuth\TokenResponse;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenManager;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenProviderInterface;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\TokenRefreshLockManager;
use Oro\Component\Config\Common\ConfigObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class AccessTokenManagerTest extends TestCase
{
    private const TEST_TRANSPORT_ID = 1;
    private const TEST_CODE = 'auth-code-123';
    private const TEST_CODE_VERIFIER = 'verifier-456';
    private const TEST_ACCESS_TOKEN = 'access-token-abc';
    private const TEST_REFRESH_TOKEN = 'refresh-token-xyz';
    private const TEST_ENCRYPTED_ACCESS_TOKEN = 'encrypted-access-token';
    private const TEST_ENCRYPTED_REFRESH_TOKEN = 'encrypted-refresh-token';

    private AccessTokenProviderInterface&MockObject $tokenProvider;
    private SymmetricCrypterInterface&MockObject $crypter;
    private ManagerRegistry&MockObject $doctrine;
    private TokenRefreshLockManager&MockObject $lockManager;
    private LoggerInterface&MockObject $logger;
    private EntityManagerInterface&MockObject $em;
    private ZendeskRestTransportRepository&MockObject $repository;
    private AccessTokenManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenProvider = $this->createMock(AccessTokenProviderInterface::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->lockManager = $this->createMock(TokenRefreshLockManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ZendeskRestTransportRepository::class);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->manager = new AccessTokenManager(
            $this->tokenProvider,
            $this->crypter,
            $this->doctrine,
            $this->lockManager,
            $this->logger,
        );
    }

    public function testExchangeAuthorizationCodePersistsTokensAndRefreshesTransport(): void
    {
        $transport = $this->transport(id: self::TEST_TRANSPORT_ID);
        $tokenResponse = new TokenResponse(self::TEST_ACCESS_TOKEN, self::TEST_REFRESH_TOKEN);

        $this->tokenProvider->expects(self::once())
            ->method('getAccessTokenByAuthorizationCode')
            ->with($transport, self::TEST_CODE, self::TEST_CODE_VERIFIER)
            ->willReturn($tokenResponse);

        $this->expectTokenEncryption();
        $this->expectManagedTransport($transport);
        $this->expectTokenPersistence();
        $this->expectLegacyAuthenticationFieldsCleanup();

        $this->manager->exchangeAuthorizationCode($transport, self::TEST_CODE, self::TEST_CODE_VERIFIER);
    }

    public function testRefreshAccessTokenThrowsWhenTransportNotPersisted(): void
    {
        $transport = $this->transport();

        self::expectException(TokenRefreshException::class);
        self::expectExceptionMessage('Transport must be persisted before token refresh');

        $this->manager->refreshAccessToken($transport);
    }

    public function testRefreshAccessTokenThrowsWhenRefreshTokenMissing(): void
    {
        $transport = $this->transport(id: self::TEST_TRANSPORT_ID, refreshToken: null);

        self::expectException(TokenRefreshException::class);
        self::expectExceptionMessage('Transport does not have a refresh token');

        $this->manager->refreshAccessToken($transport);
    }

    public function testRefreshAccessTokenSkipsApiCallWhenRecentlyRefreshed(): void
    {
        $transport = $this->transport(id: self::TEST_TRANSPORT_ID);

        $lastRefreshAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $lastRefreshAt->modify('-30 seconds');
        $transport->expects(self::any())
            ->method('getOauthLastRefreshAt')
            ->willReturn($lastRefreshAt);

        $this->expectLockExecution();

        $this->em->expects(self::once())
            ->method('contains')
            ->with($transport)
            ->willReturn(false);

        $this->tokenProvider->expects(self::never())
            ->method('refreshAccessToken');

        $this->logger->expects(self::once())
            ->method('debug')
            ->with('Token was recently refreshed, skipping API call', self::anything());

        $this->manager->refreshAccessToken($transport);
    }

    public function testRefreshAccessTokenCallsApiWhenThresholdExceeded(): void
    {
        $transport = $this->transportWithOldRefreshTime(self::TEST_TRANSPORT_ID);
        $tokenResponse = new TokenResponse(self::TEST_ACCESS_TOKEN, self::TEST_REFRESH_TOKEN);

        $this->expectLockExecution();

        $this->tokenProvider->expects(self::once())
            ->method('refreshAccessToken')
            ->with($transport)
            ->willReturn($tokenResponse);

        $this->expectTokenEncryption();
        $this->expectManagedTransport($transport, times: 2);
        $this->expectTokenPersistence();

        $this->manager->refreshAccessToken($transport);
    }

    public function testExchangeAuthorizationCodeUsesDirectMutationWhenTransportNotManaged(): void
    {
        $transport = $this->transport(id: self::TEST_TRANSPORT_ID);
        $tokenResponse = new TokenResponse(self::TEST_ACCESS_TOKEN, self::TEST_REFRESH_TOKEN);

        $this->tokenProvider->expects(self::once())
            ->method('getAccessTokenByAuthorizationCode')
            ->willReturn($tokenResponse);

        $this->expectTokenEncryption();

        $this->em->expects(self::once())
            ->method('contains')
            ->with($transport)
            ->willReturn(false);

        $this->em->expects(self::never())
            ->method('refresh');

        $this->expectTokenPersistence();
        $this->expectLegacyAuthenticationFieldsCleanup();

        $transport->expects(self::once())
            ->method('setAccessToken')
            ->with(self::TEST_ENCRYPTED_ACCESS_TOKEN);

        $transport->expects(self::once())
            ->method('setRefreshToken')
            ->with(self::TEST_ENCRYPTED_REFRESH_TOKEN);

        $transport->expects(self::once())
            ->method('setOauthLastRefreshAt')
            ->with(self::isInstanceOf(\DateTimeImmutable::class));

        $transport->expects(self::once())
            ->method('clearSettings');

        $this->manager->exchangeAuthorizationCode($transport, self::TEST_CODE, self::TEST_CODE_VERIFIER);
    }

    private function expectLockExecution(): void
    {
        $this->lockManager->expects(self::once())
            ->method('executeWithLock')
            ->willReturnCallback(fn (int $id, \Closure $callback) => $callback());
    }

    private function expectTokenEncryption(): void
    {
        $this->crypter->expects(self::exactly(2))
            ->method('encryptData')
            ->willReturnCallback(fn (string $data): string => match ($data) {
                self::TEST_ACCESS_TOKEN => self::TEST_ENCRYPTED_ACCESS_TOKEN,
                self::TEST_REFRESH_TOKEN => self::TEST_ENCRYPTED_REFRESH_TOKEN,
                default => 'encrypted-' . $data,
            });
    }

    private function expectManagedTransport(ZendeskRestTransport $transport, int $times = 1): void
    {
        $this->em->expects(self::exactly($times))
            ->method('contains')
            ->with($transport)
            ->willReturn(true);

        $this->em->expects(self::exactly($times))
            ->method('refresh')
            ->with($transport);
    }

    private function expectTokenPersistence(): void
    {
        $this->repository->expects(self::once())
            ->method('updateOAuthTokensById')
            ->with(
                self::TEST_TRANSPORT_ID,
                self::TEST_ENCRYPTED_ACCESS_TOKEN,
                self::TEST_ENCRYPTED_REFRESH_TOKEN,
                self::isInstanceOf(\DateTimeImmutable::class)
            );
    }

    private function expectLegacyAuthenticationFieldsCleanup(): void
    {
        $this->repository->expects(self::once())
            ->method('clearLegacyAuthenticationFields')
            ->with(self::TEST_TRANSPORT_ID);
    }

    private function transportWithOldRefreshTime(int $id): ZendeskRestTransport&MockObject
    {
        $transport = $this->transport(id: $id);
        $transport->expects(self::any())
            ->method('getOauthLastRefreshAt')
            ->willReturn(new \DateTime('2000-01-01', new \DateTimeZone('UTC')));

        return $transport;
    }

    private function transport(
        ?int $id = null,
        ?string $refreshToken = self::TEST_REFRESH_TOKEN
    ): ZendeskRestTransport&MockObject {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::any())
            ->method('getId')
            ->willReturn($id);
        $transport->expects(self::any())
            ->method('getRefreshToken')
            ->willReturn($refreshToken);

        $channel = $this->createMock(Channel::class);
        $settings = ConfigObject::create(['isTwoWaySyncEnabled' => false]);

        $channel->expects(self::any())
            ->method('getSynchronizationSettings')
            ->willReturn($settings);

        $transport->expects(self::any())
            ->method('getChannel')
            ->willReturn($channel);

        return $transport;
    }
}
