<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\OAuth;

use InvalidArgumentException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException as TransportRestException;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\OAuth\GrantType;
use Oro\Bundle\ZendeskBundle\Enum\OAuth\Scope;
use Oro\Bundle\ZendeskBundle\Exception\ConfigurationException;
use Oro\Bundle\ZendeskBundle\Exception\InvalidRefreshTokenException;
use Oro\Bundle\ZendeskBundle\Exception\OAuthAuthorizationException;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;
use Oro\Bundle\ZendeskBundle\Model\OAuth\OAuthCallbackUrlProviderInterface;
use Oro\Bundle\ZendeskBundle\Model\OAuth\TokenResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * OAuth access token provider for Zendesk with PKCE support
 */
class AccessTokenProvider implements AccessTokenProviderInterface
{
    private const TOKEN_REQUEST_URI = '/oauth/tokens';
    private const TOKEN_REQUEST_CONTENT_TYPE = 'application/json';
    private const MAX_RETRY_ATTEMPTS = 3;
    private const MICROSECONDS_IN_SECOND = 1_000_000;

    public function __construct(
        private readonly RestClientFactoryInterface $clientFactory,
        private readonly OAuthCallbackUrlProviderInterface $callbackUrlProvider,
        private readonly SymmetricCrypterInterface $crypter,
        private readonly int $accessTokenSeconds,
        private readonly int $refreshTokenSeconds,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    #[\Override]
    public function getAccessTokenByAuthorizationCode(
        ZendeskRestTransport $transport,
        string $code,
        string $codeVerifier
    ): TokenResponse {
        $baseUrl = rtrim($transport->getUrl(), '/');
        $client = $this->clientFactory->createRestClient($baseUrl, []);

        $scope = Scope::fromTransport($transport)->value;
        $oauthClientId = $this->getOauthClientId($transport);

        $body = $this->buildTokenRequestBody(
            grantType: GrantType::AUTHORIZATION_CODE,
            oauthClientId: $oauthClientId,
            scope: $scope,
            code: $code,
            codeVerifier: $codeVerifier
        );

        $headers = ['Content-Type' => self::TOKEN_REQUEST_CONTENT_TYPE];

        try {
            $response = $client->post(self::TOKEN_REQUEST_URI, $body, $headers);
            $responseData = $response->json();

            $this->logger?->info('Zendesk OAuth tokens obtained successfully via authorization code', [
                'transport_id' => $transport->getId(),
                'url' => $transport->getUrl(),
            ]);

            return TokenResponse::create($responseData);
        } catch (TransportRestException $e) {
            $this->logger?->error('Failed to exchange authorization code for tokens', [
                'transport_id' => $transport->getId(),
                'error' => $e->getMessage(),
                'status_code' => $e->getCode(),
            ]);

            throw OAuthAuthorizationException::codeExchangeFailed(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('Invalid OAuth token response structure', [
                'transport_id' => $transport->getId(),
                'error' => $e->getMessage(),
            ]);

            throw OAuthAuthorizationException::invalidResponse(
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Refresh access token using existing refresh token
     */
    #[\Override]
    public function refreshAccessToken(
        ZendeskRestTransport $transport
    ): TokenResponse {
        if (null === $transport->getRefreshToken()) {
            throw new ConfigurationException('Refresh token is not available for the Zendesk transport.');
        }

        return $this->fetchTokenWithRetries($transport);
    }

    /**
     * @throws InvalidRefreshTokenException When refresh token is invalid or expired
     * @throws TokenRefreshException When token refresh fails or max retries exceeded
     */
    private function fetchTokenWithRetries(
        ZendeskRestTransport $transport
    ): TokenResponse {
        $attemptNumber = 0;
        $lastResponse = null;
        $lastException = null;

        $baseUrl = rtrim($transport->getUrl(), '/');
        $client = $this->clientFactory->createRestClient($baseUrl, []);

        while ($attemptNumber < self::MAX_RETRY_ATTEMPTS) {
            try {
                $lastResponse = $this->requestAccessToken($client, $transport);
                $responseData = $lastResponse->json();

                $this->logger?->info('Zendesk access token fetched successfully', [
                    'attempt' => $attemptNumber + 1,
                    'status_code' => $lastResponse->getStatusCode(),
                ]);

                return TokenResponse::create($responseData);
            } catch (InvalidRefreshTokenException $e) {
                // Non-retryable: refresh token is invalid or expired
                $this->logger?->error('Zendesk refresh token is invalid or expired', [
                    'transport_id' => $transport->getId(),
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            } catch (TransportRestException $e) {
                $lastException = $e;
                $this->delayBeforeRetry($attemptNumber);

                $this->logger?->warning('Zendesk access token fetch failed, retrying', [
                    'attempt' => $attemptNumber + 1,
                    'status_code' => $e->getCode(),
                    'error' => $e->getMessage(),
                ]);
            } catch (InvalidArgumentException $e) {
                $this->logger?->error('Zendesk access token response missing required fields', [
                    'attempt' => $attemptNumber + 1,
                    'error' => $e->getMessage(),
                ]);

                throw TokenRefreshException::invalidResponse(
                    $e->getMessage(),
                    $e
                );
            }

            $attemptNumber++;
        }

        $this->logger?->error('Zendesk access token fetch failed after max attempts', [
            'attempts' => self::MAX_RETRY_ATTEMPTS,
            'status_code' => $lastResponse?->getStatusCode(),
            'error' => $lastException?->getMessage(),
        ]);

        throw TokenRefreshException::maxRetriesExceeded(
            self::MAX_RETRY_ATTEMPTS,
            $lastException?->getCode() ?? 0,
            $lastException
        );
    }

    /**
     * Apply exponential backoff before retry
     */
    private function delayBeforeRetry(int $attemptNumber): void
    {
        if ($attemptNumber >= self::MAX_RETRY_ATTEMPTS - 1) {
            return;
        }

        usleep((2 ** $attemptNumber) * self::MICROSECONDS_IN_SECOND); // 1s, 2s, 4s
    }

    /**
     * @throws TransportRestException
     * @throws InvalidRefreshTokenException
     */
    private function requestAccessToken(
        RestClientInterface $client,
        ZendeskRestTransport $transport
    ): RestResponseInterface {
        $scope = Scope::fromTransport($transport)->value;
        $oauthClientId = $this->getOauthClientId($transport);

        $body = $this->buildTokenRequestBody(
            grantType: GrantType::REFRESH_TOKEN,
            oauthClientId: $oauthClientId,
            scope: $scope,
            refreshToken: $this->crypter->decryptData($transport->getRefreshToken())
        );

        $headers['Content-Type'] = self::TOKEN_REQUEST_CONTENT_TYPE;

        try {
            $result = $client->post(self::TOKEN_REQUEST_URI, $body, $headers);
        } catch (TransportRestException $e) {
            if (in_array($e->getCode(), [Response::HTTP_UNAUTHORIZED, Response::HTTP_BAD_REQUEST], true)) {
                throw new InvalidRefreshTokenException(
                    'Refresh token is invalid or expired. Please reconnect OAuth.',
                    $e->getCode(),
                    $e
                );
            }

            throw $e;
        }

        return $result;
    }

    /**
     * Build the token request body based on grant type and parameters
     */
    private function buildTokenRequestBody(
        GrantType $grantType,
        string $oauthClientId,
        string $scope,
        ?string $code = null,
        ?string $codeVerifier = null,
        ?string $refreshToken = null
    ): string {
        $data = [
            'grant_type' => $grantType->value,
            'client_id' => $oauthClientId,
            'scope' => $scope,
            'expires_in' => $this->accessTokenSeconds,
            'refresh_token_expires_in' => $this->refreshTokenSeconds,
        ];

        match ($grantType) {
            GrantType::AUTHORIZATION_CODE
                => $data += [
                    'code' => $code,
                    'code_verifier' => $codeVerifier,
                    'redirect_uri' => $this->callbackUrlProvider->getCallbackUrl(),
                ],
            GrantType::REFRESH_TOKEN
                => $data['refresh_token'] = $refreshToken,
        };

        return json_encode($data);
    }

    /**
     * @throws ConfigurationException
     */
    private function getOauthClientId(ZendeskRestTransport $transport): string
    {
        $oauthClientId = $transport->getOauthClientId();
        if (!$oauthClientId) {
            throw new ConfigurationException('OAuth client id is required for OAuth authorization type.');
        }

        return $oauthClientId;
    }
}
