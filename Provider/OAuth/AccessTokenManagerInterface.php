<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\OAuth;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\ConfigurationException;
use Oro\Bundle\ZendeskBundle\Exception\InvalidRefreshTokenException;
use Oro\Bundle\ZendeskBundle\Exception\RestException;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;
use RuntimeException;

/**
 * Manages OAuth access token lifecycle: fetches tokens and persists them.
 */
interface AccessTokenManagerInterface
{
    /**
     * Exchange authorization code for access and refresh tokens, then persist to transport.
     *
     * @throws RestException
     * @throws RuntimeException
     */
    public function exchangeAuthorizationCode(
        ZendeskRestTransport $transport,
        string $code,
        string $codeVerifier
    ): void;

    /**
     * Refresh access token using existing refresh token and persist to transport.
     *
     * @throws ConfigurationException
     * @throws InvalidRefreshTokenException
     * @throws RestException
     * @throws TokenRefreshException
     * @throws RuntimeException
     */
    public function refreshAccessToken(ZendeskRestTransport $transport): void;
}
