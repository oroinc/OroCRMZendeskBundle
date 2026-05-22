<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\OAuth;

use Oro\Bundle\ZendeskBundle\Provider\OAuth\Configuration\TokenRefreshLockConfiguration;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\TokenRefreshLockManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Lock\Exception\ExceptionInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

final class TokenRefreshLockManagerTest extends TestCase
{
    private const TEST_TRANSPORT_ID = 42;
    private const LOCK_TTL = 10;

    private LockFactory&MockObject $lockFactory;
    private SharedLockInterface&MockObject $lock;
    private LoggerInterface&MockObject $logger;
    private TokenRefreshLockManager $lockManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->lock = $this->createMock(SharedLockInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->lockManager = new TokenRefreshLockManager(
            $this->createConfiguration(attemptLimit: 2, waitBetweenAttemptsMs: 0),
            $this->lockFactory,
            $this->logger,
        );
    }

    public function testExecuteWithLockAcquiresAndReleases(): void
    {
        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->willReturn($this->lock);

        $this->lock->expects(self::once())
            ->method('acquire')
            ->willReturn(true);

        $this->lock->expects(self::once())
            ->method('release');

        $result = $this->lockManager->executeWithLock(self::TEST_TRANSPORT_ID, static fn () => 'ok');

        self::assertSame('ok', $result);
    }

    public function testExecuteWithLockRetriesThenSucceeds(): void
    {
        $this->lockFactory->expects(self::exactly(2))
            ->method('createLock')
            ->willReturn($this->lock);

        $this->lock->expects(self::exactly(2))
            ->method('acquire')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->lock->expects(self::once())
            ->method('release');

        $result = $this->lockManager->executeWithLock(self::TEST_TRANSPORT_ID, static fn () => 'done');

        self::assertSame('done', $result);
    }

    public function testExecuteWithLockThrowsAfterMaxAttempts(): void
    {
        $this->lockFactory->expects(self::exactly(2))
            ->method('createLock')
            ->willReturn($this->lock);

        $this->lock->expects(self::exactly(2))
            ->method('acquire')
            ->willReturn(false);

        self::expectException(RuntimeException::class);

        $this->lockManager->executeWithLock(self::TEST_TRANSPORT_ID, static fn () => 'never');
    }

    public function testExecuteWithLockRetriesAfterAcquireException(): void
    {
        $exception = $this->createMock(ExceptionInterface::class);

        $this->lockFactory->expects(self::exactly(2))
            ->method('createLock')
            ->willReturn($this->lock);

        $this->lock->expects(self::exactly(2))
            ->method('acquire')
            ->willReturnOnConsecutiveCalls(
                self::throwException($exception),
                true
            );

        $this->lock->expects(self::once())
            ->method('release');

        $result = $this->lockManager->executeWithLock(self::TEST_TRANSPORT_ID, static fn () => 'recovered');

        self::assertSame('recovered', $result);
    }

    private function createConfiguration(int $attemptLimit, int $waitBetweenAttemptsMs): TokenRefreshLockConfiguration
    {
        return new TokenRefreshLockConfiguration(
            lockTtl: self::LOCK_TTL,
            attemptLimit: $attemptLimit,
            waitBetweenAttemptsMs: $waitBetweenAttemptsMs,
        );
    }
}
