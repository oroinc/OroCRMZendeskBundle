<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Exception;

use Exception;

/**
 * Thrown when a Zendesk OAuth access token cannot be refreshed.
 */
class TokenRefreshException extends Exception implements ZendeskException
{
    public static function missingTransportContext(): self
    {
        return new self('Cannot refresh token: transport context not initialized.');
    }

    public static function maxRetriesExceeded(int $attempts, int $statusCode = 0, ?Exception $previous = null): self
    {
        return new self(
            sprintf('Failed to refresh access token after %d attempts', $attempts),
            $statusCode,
            $previous
        );
    }

    public static function invalidResponse(string $reason, ?Exception $previous = null): self
    {
        return new self(
            sprintf('Invalid token refresh response: %s', $reason),
            0,
            $previous
        );
    }
}
