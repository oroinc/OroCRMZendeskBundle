<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\OAuth;

use Oro\Bundle\ZendeskBundle\Provider\OAuth\Configuration\TokenRefreshLockConfiguration;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Lock\Exception\ExceptionInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

/**
 * Manages distributed locks for Zendesk OAuth token refresh operations.
 */
class TokenRefreshLockManager
{
    private const LOCK_KEY_PREFIX = 'oro_integration_zendesk_token_refresh_';

    public function __construct(
        private readonly TokenRefreshLockConfiguration $lockConfiguration,
        private readonly LockFactory $lockFactory,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @throws RuntimeException If lock cannot be acquired within timeout
     */
    public function executeWithLock(int $transportId, callable $callback): mixed
    {
        $lock = $this->acquireLock($transportId);

        try {
            $this->logger?->info('Token refresh lock acquired, executing callback', [
                'transport_id' => $transportId,
            ]);

            return $callback();
        } finally {
            // Release in a separate method with catch block to avoid losing callback exception.
            $this->releaseLock($lock, $transportId);
        }
    }

    /**
     * Acquires a token refresh lock for a transport.
     *
     * Tries a **non-blocking lock** repeatedly with short waits (WAIT_BETWEEN_ATTEMPTS_MS)
     * until acquired or timeout (ATTEMPT_LIMIT × wait) is reached.
     *
     * @throws RuntimeException
     */
    private function acquireLock(int $transportId): SharedLockInterface
    {
        $lockKey = self::LOCK_KEY_PREFIX . $transportId;

        for ($attempt = 1; $attempt <= $this->lockConfiguration->getAttemptLimit(); $attempt++) {
            try {
                $lock = $this->lockFactory->createLock($lockKey, $this->lockConfiguration->getLockTtl(), false);
                // Attempt to acquire lock (non-blocking)
                if ($lock->acquire()) {
                    return $lock;
                }
            } catch (ExceptionInterface $e) {
                $this->logger?->debug('Error acquiring token refresh lock', [
                    'transport_id' => $transportId,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }

            // Log only on the first attempt to avoid flooding logs.
            if (1 === $attempt) {
                $this->logger?->info('Token refresh lock held by another process, waiting...', [
                    'transport_id' => $transportId,
                ]);
            }

            usleep($this->lockConfiguration->getWaitBetweenAttemptsMs() * 1000);
        }

        throw new RuntimeException(sprintf(
            'Failed to acquire token refresh lock for transport "%d" after "%d" attempts (%.1f seconds)',
            $transportId,
            $this->lockConfiguration->getAttemptLimit(),
            $this->lockConfiguration->getTotalWaitTime()
        ));
    }

    private function releaseLock(SharedLockInterface $lock, int $transportId): void
    {
        try {
            $lock->release();

            $this->logger?->debug('Token refresh lock released', ['transport_id' => $transportId]);
        } catch (ExceptionInterface $e) {
            $this->logger?->error(
                'Failed to release token refresh lock',
                ['transport_id' => $transportId, 'error' => $e->getMessage()]
            );
        }
    }
}
