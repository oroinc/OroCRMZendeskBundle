<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Test\FakeRestClientFactory;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\OAuth\TranslationKey;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTransportData;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class OAuthControllerTest extends WebTestCase
{
    private const ZENDESK_TRANSPORT_FIRST_TEST_TRANSPORT = 'zendesk_transport:first_test_transport';
    private const TEST_OAUTH_CLIENT_ID = 'oauth-client-id-functional';
    private int $transportId;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], array_merge(self::generateBasicAuthHeader()));
        $this->loadFixtures([LoadTransportData::class]);

        $this->transportId = $this->getReference(self::ZENDESK_TRANSPORT_FIRST_TEST_TRANSPORT)
            ->getId();
    }

    public function testCallbackActionSuccess(): void
    {
        $this->configureTransportForOAuth();

        $doctrine = $this->getContainer()->get(ManagerRegistry::class);
        $repository = $doctrine->getRepository(ZendeskRestTransport::class);
        $em = $doctrine->getManagerForClass(ZendeskRestTransport::class);
        $transport = $repository->find($this->transportId);

        self::assertNotNull($transport);
        self::assertNull($transport->getAccessToken());
        self::assertNull($transport->getRefreshToken());

        /** @var FakeRestClientFactory $restClientFactory */
        $restClientFactory = $this->getContainer()->get('oro_zendesk.transport.rest.guzzle.client_factory');
        $restClientFactory->setFixtureFile(__DIR__ . '/response/oauth_tokens_success.yml');

        $this->client->request(
            'GET',
            $this->getUrl('oro_zendesk_oauth_callback', ['code' => 'test_code', 'state' => (string)$this->transportId])
        );

        $response = $this->client->getResponse();
        $content = (string)$response->getContent();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('success: true', $content);
        self::assertStringContainsString(
            $this->getTranslator()->trans(TranslationKey::SUCCESS_CONNECTED->value),
            $content
        );

        $em->clear();
        $updatedTransport = $repository->find($this->transportId);
        self::assertNotNull($updatedTransport);
        self::assertNotNull($updatedTransport->getAccessToken());
        self::assertNotNull($updatedTransport->getRefreshToken());
        self::assertInstanceOf(\DateTimeInterface::class, $updatedTransport->getOauthLastRefreshAt());
    }

    public function testCallbackActionWhenUserDeniedAuthorization(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_zendesk_oauth_callback',
                ['error' => 'access_denied', 'error_description' => 'user denied']
            )
        );
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('success: false', (string)$response->getContent());
        self::assertStringContainsString(
            $this->getTranslator()->trans(TranslationKey::USER_DENIED->value, ['%error%' => 'user denied']),
            (string)$response->getContent()
        );
    }

    public function testCallbackActionWhenCodeIsMissing(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_zendesk_oauth_callback', ['state' => (string)$this->transportId])
        );
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('success: false', (string)$response->getContent());
        self::assertStringContainsString(
            $this->getTranslator()->trans(TranslationKey::INVALID_REQUEST->value),
            (string)$response->getContent()
        );
    }

    public function testCallbackActionWhenStateIsMissing(): void
    {
        $this->client->request('GET', $this->getUrl('oro_zendesk_oauth_callback', ['code' => 'test_code']));
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('success: false', (string)$response->getContent());
        self::assertStringContainsString(
            $this->getTranslator()->trans(TranslationKey::INVALID_REQUEST->value),
            (string)$response->getContent()
        );
    }

    public function testCallbackActionWhenStateIsInvalid(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_zendesk_oauth_callback', ['code' => 'test_code', 'state' => 'invalid'])
        );
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('success: false', (string)$response->getContent());
        self::assertStringContainsString(
            $this->getTranslator()->trans(TranslationKey::INVALID_REQUEST->value),
            (string)$response->getContent()
        );
    }

    public function testAuthorizeActionSuccess(): void
    {
        $this->configureTransportForOAuth();

        $this->client->request('GET', $this->getUrl('oro_zendesk_oauth_authorize', ['id' => $this->transportId]));
        $response = $this->client->getResponse();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertResponseStatusCodeEquals($response, 302);

        $redirectUrl = $response->getTargetUrl();
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);

        self::assertSame(self::TEST_OAUTH_CLIENT_ID, $params['client_id']);
        self::assertSame('code', $params['response_type']);
        self::assertSame((string)$this->transportId, $params['state']);
        self::assertSame('S256', $params['code_challenge_method']);
        self::assertNotEmpty($params['code_challenge']);
        self::assertStringEndsWith('/zendesk/oauth/callback', $params['redirect_uri']);
    }

    public function testAuthorizeActionWhenTransportNotFound(): void
    {
        $this->client->request('GET', $this->getUrl('oro_zendesk_oauth_authorize', ['id' => 999999]));
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('success: false', (string)$response->getContent());
        self::assertStringContainsString(
            $this->getTranslator()->trans(TranslationKey::TRANSPORT_NOT_FOUND->value),
            (string)$response->getContent()
        );
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->getContainer()->get(TranslatorInterface::class);
    }

    private function configureTransportForOAuth(): void
    {
        $doctrine = $this->getContainer()->get(ManagerRegistry::class);
        $em = $doctrine->getManagerForClass(ZendeskRestTransport::class);
        $transport = $doctrine->getRepository(ZendeskRestTransport::class)
            ->find($this->transportId);

        self::assertNotNull($transport);

        $transport->setAuthorizationType(AuthorizationType::OAUTH);
        $transport->setOauthClientId(self::TEST_OAUTH_CLIENT_ID);

        $em->flush();
        $em->clear();
    }
}
