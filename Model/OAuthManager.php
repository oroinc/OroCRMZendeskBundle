<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Model;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\OAuth\Scope;
use Oro\Bundle\ZendeskBundle\Model\OAuth\OAuthCallbackUrlProviderInterface;
use Oro\Bundle\ZendeskBundle\Model\OAuth\PkceHelper;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manages OAuth 2.0 Authorization Code flow with PKCE for Zendesk Global OAuth
 */
class OAuthManager implements OAuthManagerInterface
{
    private const CODE_VERIFIER_SESSION_KEY = 'zendesk_oauth_code_verifier';
    private const AUTHORIZE_ENDPOINT = '/oauth/authorizations/new';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly AccessTokenManagerInterface $tokenManager,
        private readonly OAuthCallbackUrlProviderInterface $callbackUrlProvider,
    ) {
    }

    /**
     * Generate authorization URL with PKCE challenge
     */
    #[\Override]
    public function generateAuthorizeUrl(ZendeskRestTransport $transport, string $state): string
    {
        $codeVerifier = PkceHelper::generateCodeVerifier();
        $codeChallenge = PkceHelper::generateCodeChallenge($codeVerifier);

        // Store code verifier in session for callback
        $session = $this->requestStack->getSession();
        $session->set(self::CODE_VERIFIER_SESSION_KEY . '_' . $transport->getId(), $codeVerifier);

        $oauthClientId = $transport->getOauthClientId();
        if (!$oauthClientId) {
            throw new \RuntimeException('OAuth client id is required for OAuth authorization type.');
        }

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $oauthClientId,
            'redirect_uri' => $this->callbackUrlProvider->getCallbackUrl(),
            'scope' => Scope::fromTransport($transport)->value,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        $zendeskUrl = rtrim($transport->getUrl(), '/');

        return $zendeskUrl . self::AUTHORIZE_ENDPOINT . '?' . $params;
    }

    /**
     * Exchange authorization code for access and refresh tokens using PKCE
     */
    #[\Override]
    public function exchangeAuthorizationCode(ZendeskRestTransport $transport, string $code): void
    {
        $session = $this->requestStack->getSession();
        $codeVerifier = $session->get(self::CODE_VERIFIER_SESSION_KEY . '_' . $transport->getId());

        if (!$codeVerifier) {
            throw new \RuntimeException('Code verifier not found in session. OAuth flow may have expired.');
        }

        $session->remove(self::CODE_VERIFIER_SESSION_KEY . '_' . $transport->getId());

        $this->tokenManager->exchangeAuthorizationCode(
            $transport,
            $code,
            $codeVerifier
        );
    }
}
