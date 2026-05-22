<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\OAuth;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException as TransportRestException;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\OAuth\GrantType;
use Oro\Bundle\ZendeskBundle\Exception\ConfigurationException;
use Oro\Bundle\ZendeskBundle\Exception\InvalidRefreshTokenException;
use Oro\Bundle\ZendeskBundle\Exception\OAuthAuthorizationException;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;
use Oro\Bundle\ZendeskBundle\Model\OAuth\OAuthCallbackUrlProviderInterface;
use Oro\Bundle\ZendeskBundle\Model\OAuth\TokenResponse;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenProvider;
use Oro\Component\Config\Common\ConfigObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class AccessTokenProviderTest extends TestCase
{
    private const TEST_ZENDESK_URL = 'https://test.zendesk.com';
    private const TEST_OAUTH_CODE = 'test-authorization-code-123';
    private const TEST_CODE_VERIFIER = 'test-pkce-verifier-456';
    private const TEST_REFRESH_TOKEN = 'test-refresh-token-789';
    private const TEST_ACCESS_TOKEN = 'test-access-token-abc';
    private const TEST_CALLBACK_URL = 'https://app.example.com/oauth/callback';
    private const TEST_OAUTH_CLIENT_ID = 'oauth-client-id-123';
    private const ACCESS_TOKEN_SECONDS = 3600;
    private const REFRESH_TOKEN_SECONDS = 7776000;
    private const TOKEN_REQUEST_URI = '/oauth/tokens';
    private const TOKEN_REQUEST_CONTENT_TYPE = 'application/json';
    private const SCOPE_READ_WRITE = 'read write';

    private RestClientFactoryInterface&MockObject $clientFactory;
    private RestClientInterface&MockObject $client;
    private OAuthCallbackUrlProviderInterface&MockObject $callbackUrlProvider;
    private SymmetricCrypterInterface&MockObject $crypter;
    private LoggerInterface&MockObject $logger;
    private AccessTokenProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->clientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->client = $this->createMock(RestClientInterface::class);
        $this->callbackUrlProvider = $this->createMock(OAuthCallbackUrlProviderInterface::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new AccessTokenProvider(
            $this->clientFactory,
            $this->callbackUrlProvider,
            $this->crypter,
            self::ACCESS_TOKEN_SECONDS,
            self::REFRESH_TOKEN_SECONDS,
            $this->logger,
        );
    }

    public function testGetAccessTokenByAuthorizationCodeSuccess(): void
    {
        $transport = $this->transport();
        $this->expectCallbackUrl();
        $this->expectRestClientCreation();

        $response = $this->createSuccessResponse();
        $this->client->expects(self::once())
            ->method('post')
            ->with(
                self::TOKEN_REQUEST_URI,
                self::bodyContains(sprintf('"grant_type":"%s"', GrantType::AUTHORIZATION_CODE->value)),
                ['Content-Type' => self::TOKEN_REQUEST_CONTENT_TYPE]
            )
            ->willReturn($response);

        $this->expectSuccessLog('Zendesk OAuth tokens obtained successfully via authorization code');

        $result = $this->provider->getAccessTokenByAuthorizationCode(
            $transport,
            self::TEST_OAUTH_CODE,
            self::TEST_CODE_VERIFIER
        );

        self::assertInstanceOf(TokenResponse::class, $result);
        self::assertSame(self::TEST_ACCESS_TOKEN, $result->getAccessToken());
        self::assertSame(self::TEST_REFRESH_TOKEN, $result->getRefreshToken());
    }

    public function testGetAccessTokenByAuthorizationCodeCapturesTokenRestException(): void
    {
        $transport = $this->transport();
        $this->expectCallbackUrl();
        $this->expectRestClientCreation();

        $exception = new TransportRestException('Server error', 500);
        $this->client->expects(self::once())
            ->method('post')
            ->willThrowException($exception);

        $this->expectErrorLog('Failed to exchange authorization code for tokens');

        self::expectException(OAuthAuthorizationException::class);
        self::expectExceptionMessage('Server error');

        $this->provider->getAccessTokenByAuthorizationCode(
            $transport,
            self::TEST_OAUTH_CODE,
            self::TEST_CODE_VERIFIER
        );
    }

    public function testGetAccessTokenByAuthorizationCodeHandlesInvalidResponse(): void
    {
        $transport = $this->transport();
        $this->expectCallbackUrl();
        $this->expectRestClientCreation();

        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::once())
            ->method('json')
            ->willReturn(['invalid' => 'response']);

        $this->client->expects(self::once())
            ->method('post')
            ->willReturn($response);

        $this->expectErrorLog('Invalid OAuth token response structure');

        self::expectException(OAuthAuthorizationException::class);

        $this->provider->getAccessTokenByAuthorizationCode(
            $transport,
            self::TEST_OAUTH_CODE,
            self::TEST_CODE_VERIFIER
        );
    }

    public function testGetAccessTokenByAuthorizationCodeThrowsWhenOauthClientIdMissing(): void
    {
        $transport = $this->transport(oauthClientId: null);

        $this->expectRestClientCreation();
        $this->callbackUrlProvider->expects(self::never())
            ->method('getCallbackUrl');

        self::expectException(ConfigurationException::class);
        self::expectExceptionMessage('OAuth client id is required for OAuth authorization type.');

        $this->provider->getAccessTokenByAuthorizationCode(
            $transport,
            self::TEST_OAUTH_CODE,
            self::TEST_CODE_VERIFIER
        );
    }

    public function testRefreshAccessTokenThrowsWhenMissingRefreshToken(): void
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::once())
            ->method('getRefreshToken')
            ->willReturn(null);

        self::expectException(ConfigurationException::class);
        self::expectExceptionMessage('Refresh token is not available');

        $this->provider->refreshAccessToken($transport);
    }

    public function testRefreshAccessTokenSucceedsOnFirstAttempt(): void
    {
        $transport = $this->transport(self::TEST_REFRESH_TOKEN);
        $this->expectRestClientCreation();
        $this->expectDecryptRefreshToken();

        $response = $this->createSuccessResponse();
        $response->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->client->expects(self::once())
            ->method('post')
            ->with(
                self::TOKEN_REQUEST_URI,
                self::bodyContains(sprintf('"grant_type":"%s"', GrantType::REFRESH_TOKEN->value)),
                ['Content-Type' => self::TOKEN_REQUEST_CONTENT_TYPE]
            )
            ->willReturn($response);

        $this->logger->expects(self::once())
            ->method('info')
            ->with(
                'Zendesk access token fetched successfully',
                self::isType('array')
            );

        $result = $this->provider->refreshAccessToken($transport);

        self::assertInstanceOf(TokenResponse::class, $result);
        self::assertSame(self::TEST_ACCESS_TOKEN, $result->getAccessToken());
    }

    public function testRefreshAccessTokenRetriesOnTransientErrorThenFails(): void
    {
        $transport = $this->transport(self::TEST_REFRESH_TOKEN);
        $this->expectRestClientCreation();
        $this->expectDecryptRefreshToken(3);

        $exception = new TransportRestException('Service unavailable', Response::HTTP_SERVICE_UNAVAILABLE);
        $this->client->expects(self::exactly(3))
            ->method('post')
            ->willThrowException($exception);

        $this->logger->expects(self::exactly(3))
            ->method('warning');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Zendesk access token fetch failed after max attempts',
                self::isType('array')
            );

        self::expectException(TokenRefreshException::class);
        self::expectExceptionMessage('Failed to refresh access token after 3 attempts');

        $this->provider->refreshAccessToken($transport);
    }

    public function testRefreshAccessTokenThrowsImmediatelyOnInvalidRefreshToken(): void
    {
        $transport = $this->transport(self::TEST_REFRESH_TOKEN);
        $this->expectRestClientCreation();
        $this->expectDecryptRefreshToken();

        $exception = new TransportRestException('Unauthorized', Response::HTTP_UNAUTHORIZED);
        $this->client->expects(self::once())
            ->method('post')
            ->willThrowException($exception);

        $this->expectErrorLog('Zendesk refresh token is invalid or expired');

        self::expectException(InvalidRefreshTokenException::class);
        self::expectExceptionMessage('Refresh token is invalid or expired');

        $this->provider->refreshAccessToken($transport);
    }

    /**
     * @dataProvider refreshAccessTokenResponseValidationProvider
     */
    public function testRefreshAccessTokenHandlesInvalidResponse($scenario, $responseData): void
    {
        $transport = $this->transport(self::TEST_REFRESH_TOKEN);
        $this->expectRestClientCreation();
        $this->expectDecryptRefreshToken();

        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::once())
            ->method('json')
            ->willReturn($responseData);

        $this->client->expects(self::once())
            ->method('post')
            ->willReturn($response);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Zendesk access token response missing required fields',
                self::logContextWith('attempt', 'error')
            );

        self::expectException(TokenRefreshException::class);

        $this->provider->refreshAccessToken($transport);
    }

    public static function refreshAccessTokenResponseValidationProvider(): \Iterator
    {
        yield ['missing access_token', ['refresh_token' => 'token']];
        yield ['missing refresh_token', ['access_token' => 'token']];
        yield ['empty response', []];
    }

    private function transport(
        ?string $refreshToken = null,
        ?string $oauthClientId = self::TEST_OAUTH_CLIENT_ID
    ): ZendeskRestTransport&MockObject {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::any())
            ->method('getUrl')
            ->willReturn(self::TEST_ZENDESK_URL);
        $transport->expects(self::any())
            ->method('getRefreshToken')
            ->willReturn($refreshToken);
        $transport->expects(self::any())
            ->method('getId')
            ->willReturn(1);
        $transport->expects(self::any())
            ->method('getOauthClientId')
            ->willReturn($oauthClientId);

        $channel = $this->createMock(Channel::class);
        $settings = ConfigObject::create(['isTwoWaySyncEnabled' => false]);

        $channel->expects(self::any())
            ->method('getSynchronizationSettings')
            ->willReturn($settings);

        $transport->expects(self::any())
            ->method('getChannel')
            ->willReturn($channel);

        return $transport;
    }

    private static function logContextWith(string ...$keys): mixed
    {
        return self::callback(static function (array $context) use ($keys): bool {
            return array_reduce(
                $keys,
                static fn (string $carry, string $key) => $carry && isset($context[$key]),
                true
            );
        });
    }

    private static function bodyContains(string $substring): mixed
    {
        return self::callback(static function (string $value) use ($substring): bool {
            return str_contains($value, $substring);
        });
    }

    private function expectCallbackUrl(): void
    {
        $this->callbackUrlProvider->expects(self::once())
            ->method('getCallbackUrl')
            ->willReturn(self::TEST_CALLBACK_URL);
    }

    private function expectRestClientCreation(): void
    {
        $this->clientFactory->expects(self::once())
            ->method('createRestClient')
            ->willReturn($this->client);
    }

    private function expectSuccessLog(string $message): void
    {
        $this->logger->expects(self::once())
            ->method('info')
            ->with(
                $message,
                self::logContextWith('transport_id')
            );
    }

    private function expectErrorLog(string $message): void
    {
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                $message,
                self::logContextWith('transport_id', 'error')
            );
    }

    private function createSuccessResponse(): RestResponseInterface&MockObject
    {
        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::once())
            ->method('json')
            ->willReturn([
                'access_token' => self::TEST_ACCESS_TOKEN,
                'refresh_token' => self::TEST_REFRESH_TOKEN,
                'expires_in' => self::ACCESS_TOKEN_SECONDS,
                'token_type' => 'bearer',
                'scope' => self::SCOPE_READ_WRITE,
            ]);

        return $response;
    }

    private function expectDecryptRefreshToken(int $times = 1): void
    {
        $this->crypter->expects(self::exactly($times))
            ->method('decryptData')
            ->willReturn(self::TEST_REFRESH_TOKEN);
    }
}
