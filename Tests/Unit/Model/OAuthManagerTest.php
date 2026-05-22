<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Model;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Model\OAuth\OAuthCallbackUrlProviderInterface;
use Oro\Bundle\ZendeskBundle\Model\OAuthManager;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenManagerInterface;
use Oro\Component\Config\Common\ConfigObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class OAuthManagerTest extends TestCase
{
    private const TEST_ZENDESK_URL = 'https://test.zendesk.com';
    private const TEST_STATE = 'random-state-value-123';
    private const TEST_OAUTH_CODE = 'test-oauth-code-456';
    private const TEST_CALLBACK_URL = 'https://app.example.com/oauth/callback';
    private const TEST_OAUTH_CLIENT_ID = 'oauth-client-id-123';
    private const AUTHORIZE_ENDPOINT = '/oauth/authorizations/new';
    private const CODE_VERIFIER_SESSION_KEY = 'zendesk_oauth_code_verifier';
    private const DUMMY_ID = 123;

    private RequestStack&MockObject $requestStack;
    private AccessTokenManagerInterface&MockObject $tokenManager;
    private OAuthCallbackUrlProviderInterface&MockObject $callbackUrlProvider;
    private SessionInterface&MockObject $session;
    private OAuthManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->tokenManager = $this->createMock(AccessTokenManagerInterface::class);
        $this->callbackUrlProvider = $this->createMock(OAuthCallbackUrlProviderInterface::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->requestStack->expects(self::any())
            ->method('getSession')
            ->willReturn($this->session);

        $this->manager = new OAuthManager(
            $this->requestStack,
            $this->tokenManager,
            $this->callbackUrlProvider,
        );
    }

    public function testGenerateAuthorizeUrlGeneratesPkceChallenge(): void
    {
        $transport = $this->transport(id: self::DUMMY_ID);

        $this->callbackUrlProvider->expects(self::once())
            ->method('getCallbackUrl')
            ->willReturn(self::TEST_CALLBACK_URL);

        $this->session->expects(self::once())
            ->method('set')
            ->with(
                self::CODE_VERIFIER_SESSION_KEY . '_' . self::DUMMY_ID,
                self::callback(static function (string $verifier): bool {
                    return $verifier !== '' && !str_contains($verifier, '+') && !str_contains($verifier, '/');
                })
            );

        $url = $this->manager->generateAuthorizeUrl($transport, self::TEST_STATE);

        self::assertStringContainsString(self::TEST_ZENDESK_URL, $url);
        self::assertStringContainsString(self::AUTHORIZE_ENDPOINT, $url);
        self::assertStringContainsString('response_type=code', $url);
        self::assertStringContainsString('client_id=' . urlencode(self::TEST_OAUTH_CLIENT_ID), $url);
        self::assertStringContainsString('redirect_uri=' . urlencode(self::TEST_CALLBACK_URL), $url);
        self::assertStringContainsString('scope=read', $url);
        self::assertStringContainsString('state=' . self::TEST_STATE, $url);
        self::assertStringContainsString('code_challenge=', $url);
        self::assertStringContainsString('code_challenge_method=S256', $url);
    }

    public function testGenerateAuthorizeUrlUsesTransportSpecificSessionKey(): void
    {
        $transport = $this->transport(id: self::DUMMY_ID);

        $this->callbackUrlProvider->expects(self::once())
            ->method('getCallbackUrl')
            ->willReturn(self::TEST_CALLBACK_URL);

        $this->session->expects(self::once())
            ->method('set')
            ->with(
                self::CODE_VERIFIER_SESSION_KEY . '_' . self::DUMMY_ID,
                self::callback(static function (string $v): bool {
                    return $v !== '';
                })
            );

        $this->manager->generateAuthorizeUrl($transport, self::TEST_STATE);
    }

    public function testGenerateAuthorizeUrlThrowsWhenOauthClientIdMissing(): void
    {
        $transport = $this->transport(id: self::DUMMY_ID, oauthClientId: null);

        $this->session->expects(self::once())
            ->method('set')
            ->with(
                self::CODE_VERIFIER_SESSION_KEY . '_' . self::DUMMY_ID,
                self::isType('string')
            );

        $this->callbackUrlProvider->expects(self::never())
            ->method('getCallbackUrl');

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('OAuth client id is required for OAuth authorization type.');

        $this->manager->generateAuthorizeUrl($transport, self::TEST_STATE);
    }

    public function testExchangeAuthorizationCodeRetrievesVerifierFromSession(): void
    {
        $transport = $this->transport(id: self::DUMMY_ID);
        $storedVerifier = 'test-code-verifier-xyz';

        $this->session->expects(self::once())
            ->method('get')
            ->with(self::CODE_VERIFIER_SESSION_KEY . '_' . self::DUMMY_ID)
            ->willReturn($storedVerifier);

        $this->session->expects(self::once())
            ->method('remove')
            ->with(self::CODE_VERIFIER_SESSION_KEY . '_' . self::DUMMY_ID);

        $this->tokenManager->expects(self::once())
            ->method('exchangeAuthorizationCode')
            ->with($transport, self::TEST_OAUTH_CODE, $storedVerifier);

        $this->manager->exchangeAuthorizationCode($transport, self::TEST_OAUTH_CODE);
    }

    public function testExchangeAuthorizationCodeThrowsWhenMissingVerifier(): void
    {
        $transport = $this->transport(id: self::DUMMY_ID);

        $this->session->expects(self::once())
            ->method('get')
            ->with(self::CODE_VERIFIER_SESSION_KEY . '_' . self::DUMMY_ID)
            ->willReturn(null);

        $this->tokenManager->expects(self::never())
            ->method('exchangeAuthorizationCode');

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Code verifier not found in session');

        $this->manager->exchangeAuthorizationCode($transport, self::TEST_OAUTH_CODE);
    }

    public function testExchangeAuthorizationCodeRemovesVerifierFromSession(): void
    {
        $transport = $this->transport(id: self::DUMMY_ID);
        $verifier = 'test-verifier-789';

        $this->session->expects(self::once())
            ->method('get')
            ->with(self::CODE_VERIFIER_SESSION_KEY . '_' . self::DUMMY_ID)
            ->willReturn($verifier);

        $this->session->expects(self::once())
            ->method('remove')
            ->with(self::CODE_VERIFIER_SESSION_KEY . '_' . self::DUMMY_ID);

        $this->tokenManager->expects(self::once())
            ->method('exchangeAuthorizationCode');

        $this->manager->exchangeAuthorizationCode($transport, self::TEST_OAUTH_CODE);
    }

    private function transport(
        int $id,
        ?string $url = null,
        ?string $oauthClientId = self::TEST_OAUTH_CLIENT_ID
    ): ZendeskRestTransport&MockObject {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::any())
            ->method('getId')
            ->willReturn($id);
        $transport->expects(self::any())
            ->method('getUrl')
            ->willReturn($url ?? self::TEST_ZENDESK_URL);
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
}
