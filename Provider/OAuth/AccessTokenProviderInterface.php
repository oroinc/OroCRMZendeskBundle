<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\OAuth;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\ConfigurationException;
use Oro\Bundle\ZendeskBundle\Exception\InvalidRefreshTokenException;
use Oro\Bundle\ZendeskBundle\Exception\OAuthAuthorizationException;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;
use Oro\Bundle\ZendeskBundle\Model\OAuth\TokenResponse;

/**
 * OAuth access token provider for Zendesk with PKCE support.
 */
interface AccessTokenProviderInterface
{
    /**
     * Exchange authorization code for access and refresh tokens.
     *
     * @throws OAuthAuthorizationException When code exchange fails or returns invalid response
     */
    public function getAccessTokenByAuthorizationCode(
        ZendeskRestTransport $transport,
        string $code,
        string $codeVerifier
    ): TokenResponse;

    /**
     * Refresh access token using existing refresh token.
     *
     * @throws ConfigurationException When refresh token is not available
     * @throws InvalidRefreshTokenException When refresh token is invalid or expired
     * @throws TokenRefreshException When token refresh fails or max retries exceeded
     */
    public function refreshAccessToken(
        ZendeskRestTransport $transport
    ): TokenResponse;
}
