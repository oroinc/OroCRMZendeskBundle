<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Enum\Transport;

/**
 * Zendesk transport authorization type enumeration.
 */
enum AuthorizationType: string
{
    case OAUTH = 'oauth';
    case EMAIL_TOKEN = 'email_token';

    public const DEFAULT = self::OAUTH;

    public function isOAuth(): bool
    {
        return self::OAUTH === $this;
    }

    public function isEmailToken(): bool
    {
        return self::EMAIL_TOKEN === $this;
    }
}
