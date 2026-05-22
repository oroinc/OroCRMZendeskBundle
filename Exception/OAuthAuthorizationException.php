<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Exception;

use Exception;

/**
 * Thrown when OAuth authorization code exchange fails.
 */
class OAuthAuthorizationException extends Exception implements ZendeskException
{
    public static function codeExchangeFailed(string $reason, int $statusCode = 0, ?Exception $previous = null): self
    {
        return new self(
            sprintf('Failed to exchange authorization code for access token: %s', $reason),
            $statusCode,
            $previous
        );
    }

    public static function invalidResponse(string $reason, ?Exception $previous = null): self
    {
        return new self(
            sprintf('Invalid OAuth token response: %s', $reason),
            0,
            $previous
        );
    }
}
