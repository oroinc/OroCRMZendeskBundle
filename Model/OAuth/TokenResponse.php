<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Model\OAuth;

use InvalidArgumentException;

/**
 * OAuth tokens data.
 */
readonly class TokenResponse
{
    private const REQUIRED_KEYS = [
        'access_token',
        'refresh_token',
    ];

    public function __construct(
        private string $accessToken,
        private string $refreshToken,
    ) {
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function create(array $data): self
    {
        foreach (self::REQUIRED_KEYS as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException(sprintf('Missing required "%s" in token data.', $key));
            }
        }

        return new self(
            accessToken: $data['access_token'],
            refreshToken: $data['refresh_token'],
        );
    }
}
