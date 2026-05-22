<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ZendeskBundle\Command\RefreshOAuthTokensCommand;
use Oro\Bundle\ZendeskBundle\Entity\Repository\ZendeskRestTransportRepository;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\ConfigurationException;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class RefreshOAuthTokensCommandTest extends TestCase
{
    private const DEFAULT_THRESHOLD_DAYS = 7;
    private const CRON_DEFINITION = '0 2 * * *';

    private ManagerRegistry&MockObject $doctrine;
    private ZendeskRestTransportRepository&MockObject $repository;
    private AccessTokenManagerInterface&MockObject $tokenManager;
    private LoggerInterface&MockObject $logger;
    private RefreshOAuthTokensCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ZendeskRestTransportRepository::class);
        $this->tokenManager = $this->createMock(AccessTokenManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ZendeskRestTransport::class)
            ->willReturn($this->repository);

        $this->command = new RefreshOAuthTokensCommand(
            $this->doctrine,
            $this->tokenManager,
            self::CRON_DEFINITION,
            self::DEFAULT_THRESHOLD_DAYS,
            $this->logger,
        );
    }

    public function testIsActiveReturnsTrueWhenIntegrationsExist(): void
    {
        $this->repository->expects(self::once())
            ->method('countAuthorizedIntegrations')
            ->willReturn(1);

        self::assertTrue($this->command->isActive());
    }

    public function testIsActiveReturnsFalseWhenNoIntegrations(): void
    {
        $this->repository->expects(self::once())
            ->method('countAuthorizedIntegrations')
            ->willReturn(0);

        self::assertFalse($this->command->isActive());
    }

    public function testExecuteReturnsSuccessWhenNoTransportsNeedRefresh(): void
    {
        $this->repository->expects(self::once())
            ->method('findTransportsNeedingTokenRefresh')
            ->with(self::DEFAULT_THRESHOLD_DAYS)
            ->willReturn([]);

        $input = new StringInput('');
        $output = new BufferedOutput();

        $exitCode = $this->command->run($input, $output);

        self::assertSame(0, $exitCode);

        $outputText = $output->fetch();
        self::assertStringContainsString('No transports need token refresh', $outputText);
    }

    public function testExecuteRefreshesAllTransportsSuccessfully(): void
    {
        $transport1 = $this->transportWithId(1);
        $transport2 = $this->transportWithId(2);

        $this->repository->expects(self::once())
            ->method('findTransportsNeedingTokenRefresh')
            ->with(self::DEFAULT_THRESHOLD_DAYS)
            ->willReturn([$transport1, $transport2]);

        $this->tokenManager->expects(self::exactly(2))
            ->method('refreshAccessToken')
            ->withConsecutive(
                [$transport1],
                [$transport2]
            );

        $this->logger->expects(self::exactly(2))
            ->method('info')
            ->with(
                'Zendesk OAuth token refreshed',
                self::logContextWithTransportId()
            );

        $input = new StringInput('');
        $output = new BufferedOutput();

        $exitCode = $this->command->run($input, $output);

        self::assertSame(0, $exitCode);

        $outputText = $output->fetch();
        self::assertStringContainsString('Found 2 transport(s)', $outputText);
        self::assertStringContainsString('Successfully refreshed: 2', $outputText);
        self::assertStringContainsString('Failed: 0', $outputText);
    }

    public function testExecuteHandlesMixedSuccessAndFailure(): void
    {
        $transport1 = $this->transportWithId(1);
        $transport2 = $this->transportWithId(2);
        $transport3 = $this->transportWithId(3);

        $this->repository->expects(self::once())
            ->method('findTransportsNeedingTokenRefresh')
            ->with(self::DEFAULT_THRESHOLD_DAYS)
            ->willReturn([$transport1, $transport2, $transport3]);

        $this->tokenManager->expects(self::exactly(3))
            ->method('refreshAccessToken')
            ->willReturnMap([
                [$transport1, null],
                [$transport2, null],
                [$transport3, null],
            ])
            ->willReturnCallback(function (ZendeskRestTransport $transport) {
                if (3 === $transport->getId()) {
                    throw new TokenRefreshException('Token expired');
                }
            });

        $this->logger->expects(self::exactly(2))
            ->method('info')
            ->with('Zendesk OAuth token refreshed', self::callback(static function (array $context): bool {
                return isset($context['transport_id']);
            }));

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to refresh Zendesk OAuth token',
                self::logContextWithTransportId()
            );

        $input = new StringInput('');
        $output = new BufferedOutput();

        $exitCode = $this->command->run($input, $output);

        self::assertSame(1, $exitCode);

        $outputText = $output->fetch();
        self::assertStringContainsString('Found 3 transport(s)', $outputText);
        self::assertStringContainsString('Successfully refreshed: 2', $outputText);
        self::assertStringContainsString('Failed: 1', $outputText);
        self::assertStringContainsString('✗', $outputText);
    }

    public function testExecuteLogsAndReportsAllFailures(): void
    {
        $transport1 = $this->transportWithId(1);
        $transport2 = $this->transportWithId(2);

        $this->repository->expects(self::once())
            ->method('findTransportsNeedingTokenRefresh')
            ->with(self::DEFAULT_THRESHOLD_DAYS)
            ->willReturn([$transport1, $transport2]);

        $exception1 = new ConfigurationException('Missing refresh token');
        $exception2 = new TokenRefreshException('Max retries exceeded');

        $this->tokenManager->expects(self::exactly(2))
            ->method('refreshAccessToken')
            ->willReturnCallback(function (ZendeskRestTransport $transport) use ($exception1, $exception2) {
                $transportId = $transport->getId();
                if (1 === $transportId) {
                    throw $exception1;
                }
                if (2 === $transportId) {
                    throw $exception2;
                }
            });

        $this->logger->expects(self::exactly(2))
            ->method('error')
            ->with(
                'Failed to refresh Zendesk OAuth token',
                self::callback(static function (array $context): bool {
                    return isset($context['transport_id'], $context['error']);
                })
            );

        $input = new StringInput('');
        $output = new BufferedOutput();

        $exitCode = $this->command->run($input, $output);

        self::assertSame(1, $exitCode);

        $outputText = $output->fetch();
        self::assertStringContainsString('Missing refresh token', $outputText);
        self::assertStringContainsString('Max retries exceeded', $outputText);
        self::assertStringContainsString('Failed: 2', $outputText);
    }

    public function testExecuteUsesConfiguredThresholdDays(): void
    {
        $command = new RefreshOAuthTokensCommand(
            $this->doctrine,
            $this->tokenManager,
            self::CRON_DEFINITION,
            14, // Custom threshold: 14 days
            $this->logger,
        );

        $this->repository->expects(self::once())
            ->method('findTransportsNeedingTokenRefresh')
            ->with(14) // Should use custom threshold
            ->willReturn([]);

        $input = new StringInput('');
        $output = new BufferedOutput();

        $command->run($input, $output);
    }

    public function testGetDefaultDefinitionReturnsCronExpression(): void
    {
        self::assertSame(self::CRON_DEFINITION, $this->command->getDefaultDefinition());
    }

    /**
     * @dataProvider refreshFailureExceptionProvider
     */
    public function testExecuteContinuesAfterException(string $exceptionType, \Exception $exception): void
    {
        $transport1 = $this->transportWithId(1);
        $transport2 = $this->transportWithId(2);

        $this->repository->expects(self::once())
            ->method('findTransportsNeedingTokenRefresh')
            ->with(self::DEFAULT_THRESHOLD_DAYS)
            ->willReturn([$transport1, $transport2]);

        // First transport throws exception, second succeeds
        $this->tokenManager->expects(self::exactly(2))
            ->method('refreshAccessToken')
            ->willReturnCallback(function (ZendeskRestTransport $transport) use ($exception) {
                if (1 === $transport->getId()) {
                    throw $exception;
                }
            });

        $this->logger->expects(self::once())
            ->method('error');

        $this->logger->expects(self::once())
            ->method('info');

        $input = new StringInput('');
        $output = new BufferedOutput();

        $exitCode = $this->command->run($input, $output);

        self::assertSame(1, $exitCode);

        $outputText = $output->fetch();
        self::assertStringContainsString('Successfully refreshed: 1', $outputText);
        self::assertStringContainsString('Failed: 1', $outputText);
    }

    public static function refreshFailureExceptionProvider(): \Iterator
    {
        yield [
            'exceptionType' => 'ConfigurationException',
            'exception' => new ConfigurationException('Missing token'),
        ];

        yield [
            'exceptionType' => 'TokenRefreshException',
            'exception' => new TokenRefreshException('Max retries exceeded'),
        ];

        yield [
            'exceptionType' => 'Generic Exception',
            'exception' => new \Exception('Unknown error'),
        ];
    }

    private function transportWithId(int $id): ZendeskRestTransport&MockObject
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        $refreshedAt = new \DateTime('-10 days', new \DateTimeZone('UTC'));
        $transport->expects(self::any())
            ->method('getOauthLastRefreshAt')
            ->willReturn($refreshedAt);

        return $transport;
    }

    private static function logContextWithTransportId(): mixed
    {
        return self::callback(static function (array $context): bool {
            return isset($context['transport_id']);
        });
    }
}
