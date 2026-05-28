<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\InvalidRefreshTokenException;
use Oro\Bundle\ZendeskBundle\Exception\OAuthExpiredException;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenManagerInterface;
use Oro\Bundle\ZendeskBundle\Provider\Rest\Client\Guzzle\TokenRefreshHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TokenRefreshHandlerTest extends TestCase
{
    private const PLAIN_ACCESS_TOKEN = 'plain-access-token';
    private const ENCRYPTED_ACCESS_TOKEN = 'encrypted-access-token';
    private const HTTPS_EXAMPLE_ZENDESK_COM = 'https://example.zendesk.com';
    private const DUMMY_ID = 42;

    private AccessTokenManagerInterface&MockObject $tokenManager;
    private SymmetricCrypterInterface&MockObject $crypter;
    private LoggerInterface&MockObject $logger;
    private TokenRefreshHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenManager = $this->createMock(AccessTokenManagerInterface::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new TokenRefreshHandler(
            $this->tokenManager,
            $this->crypter,
            $this->logger,
        );
    }

    public function testRefreshTokenThrowsWhenTransportContextMissing(): void
    {
        $this->tokenManager->expects(self::never())
            ->method('refreshAccessToken');

        $this->crypter->expects(self::never())
            ->method('decryptData');

        self::expectException(TokenRefreshException::class);
        self::expectExceptionMessage('Cannot refresh token: transport context not initialized.');

        $this->handler->refreshToken();
    }

    public function testRefreshTokenRefreshesAndDecryptsAccessToken(): void
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::once())
            ->method('getAccessToken')
            ->willReturn(self::ENCRYPTED_ACCESS_TOKEN);

        $this->handler->setTransportContext($transport);

        $this->tokenManager->expects(self::once())
            ->method('refreshAccessToken')
            ->with($transport);

        $this->crypter->expects(self::once())
            ->method('decryptData')
            ->with(self::ENCRYPTED_ACCESS_TOKEN)
            ->willReturn(self::PLAIN_ACCESS_TOKEN);

        $this->logger->expects(self::never())
            ->method('error');

        $result = $this->handler->refreshToken();

        self::assertSame(self::PLAIN_ACCESS_TOKEN, $result);
    }

    public function testRefreshTokenDecryptsEmptyStringWhenAccessTokenMissing(): void
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::once())
            ->method('getAccessToken')
            ->willReturn(null);

        $this->handler->setTransportContext($transport);

        $this->tokenManager->expects(self::once())
            ->method('refreshAccessToken')
            ->with($transport);

        $this->crypter->expects(self::once())
            ->method('decryptData')
            ->with('')
            ->willReturn('');

        self::assertSame('', $this->handler->refreshToken());
    }

    public function testRefreshTokenTransformsInvalidRefreshTokenExceptionToOAuthExpiredException(): void
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::once())
            ->method('getId')
            ->willReturn(self::DUMMY_ID);
        $transport->expects(self::once())
            ->method('getUrl')
            ->willReturn(self::HTTPS_EXAMPLE_ZENDESK_COM);

        $this->handler->setTransportContext($transport);

        $previous = new InvalidRefreshTokenException('Refresh token is invalid or expired');

        $this->tokenManager->expects(self::once())
            ->method('refreshAccessToken')
            ->with($transport)
            ->willThrowException($previous);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Zendesk OAuth refresh token is invalid or expired',
                self::callback(static function (array $context): bool {
                    return isset($context['transport_id'], $context['url'], $context['error'])
                        && $context['transport_id'] === self::DUMMY_ID
                        && $context['url'] === self::HTTPS_EXAMPLE_ZENDESK_COM
                        && $context['error'] === 'Refresh token is invalid or expired';
                })
            );

        $this->crypter->expects(self::never())
            ->method('decryptData');

        self::expectException(OAuthExpiredException::class);
        self::expectExceptionMessage(
            'Zendesk OAuth authorization has expired. Please reconnect your Zendesk account.'
        );

        try {
            $this->handler->refreshToken();
        } catch (OAuthExpiredException $exception) {
            self::assertSame($previous, $exception->getPrevious());

            throw $exception;
        }
    }
}
