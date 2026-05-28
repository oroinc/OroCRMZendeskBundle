<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Model;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;

/**
 * Clears unused ZendeskRestTransport credentials based on its authorization type.
 */
class AuthorizationTypeCredentialsCleaner
{
    public static function clearByAuthorizationType(ZendeskRestTransport $transport): void
    {
        $authorizationType = $transport->getAuthorizationType();

        match ($authorizationType) {
            AuthorizationType::OAUTH => self::clearEmailTokenCredentials($transport),
            AuthorizationType::EMAIL_TOKEN => self::clearOAuthCredentials($transport),
        };

        $transport->clearSettings();
    }

    private static function clearEmailTokenCredentials(ZendeskRestTransport $transport): void
    {
        $transport->setEmail(null);
        $transport->setToken(null);
    }

    private static function clearOAuthCredentials(ZendeskRestTransport $transport): void
    {
        $transport->setOauthClientId(null);
        $transport->setAccessToken(null);
        $transport->setRefreshToken(null);
        $transport->setOauthLastRefreshAt(null);
    }
}
