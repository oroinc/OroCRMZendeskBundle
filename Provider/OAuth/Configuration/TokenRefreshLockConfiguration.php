<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\OAuth\Configuration;

/**
 * Configuration for token refresh locking mechanism.
 */
readonly class TokenRefreshLockConfiguration
{
    public function __construct(
        private int $lockTtl,
        private int $attemptLimit,
        private int $waitBetweenAttemptsMs,
    ) {
    }

    public function getLockTtl(): int
    {
        return $this->lockTtl;
    }

    public function getAttemptLimit(): int
    {
        return $this->attemptLimit;
    }

    public function getWaitBetweenAttemptsMs(): int
    {
        return $this->waitBetweenAttemptsMs;
    }

    /**
     * Total waiting time in seconds
     */
    public function getTotalWaitTime(): float
    {
        return $this->getAttemptLimit() * $this->getWaitBetweenAttemptsMs() / 1000;
    }
}
