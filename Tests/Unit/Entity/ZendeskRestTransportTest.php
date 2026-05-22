<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Entity;

use DateTime;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class ZendeskRestTransportTest extends TestCase
{
    private ZendeskRestTransport $target;

    protected function setUp(): void
    {
        $this->target = new ZendeskRestTransport();
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value): void
    {
        $setter = 'set' . ucfirst($property);
        $getter = 'get' . ucfirst($property);

        $result = $this->target->$setter($value);

        $this->assertInstanceOf(ZendeskRestTransport::class, $result);
        $this->assertEquals($value, $this->target->$getter());
    }

    public function settersAndGettersDataProvider(): array
    {
        $dateTime = new DateTime('2024-01-15 10:30:00');

        return [
            'url' => ['url', 'https://example.zendesk.com'],
            'email' => ['email', 'agent@example.com'],
            'token' => ['token', 'api-token-123'],
            'zendeskUserEmail' => ['zendeskUserEmail', 'user@example.com'],
            'authorizationType' => ['authorizationType', AuthorizationType::OAUTH],
            'oauthClientId' => ['oauthClientId', 'oauth-client-id-123'],
            'accessToken' => ['accessToken', 'access-token-123'],
            'refreshToken' => ['refreshToken', 'refresh-token-456'],
            'oauthLastRefreshAt' => ['oauthLastRefreshAt', $dateTime],
            'oauthLastRefreshAt null' => ['oauthLastRefreshAt', null],
        ];
    }

    public function testSetAuthorizationTypeDefaultsToDefaultWhenNullPassed(): void
    {
        $result = $this->target->setAuthorizationType(null);

        $this->assertInstanceOf(ZendeskRestTransport::class, $result);
        $this->assertSame(AuthorizationType::DEFAULT, $this->target->getAuthorizationType());
    }

    public function testGetSettingsBag(): void
    {
        $url = 'https://example.zendesk.com';
        $email = 'agent@example.com';
        $token = 'api-token-123';
        $zendeskUserEmail = 'zendesk@example.com';
        $oauthClientId = 'oauth-client-id-123';
        $accessToken = 'access-token-123';
        $refreshToken = 'refresh-token-456';
        $dateTime = new DateTime('2024-01-15 10:30:00');

        $this->target->setUrl($url);
        $this->target->setEmail($email);
        $this->target->setToken($token);
        $this->target->setZendeskUserEmail($zendeskUserEmail);
        $this->target->setAuthorizationType(AuthorizationType::OAUTH);
        $this->target->setOauthClientId($oauthClientId);
        $this->target->setAccessToken($accessToken);
        $this->target->setRefreshToken($refreshToken);
        $this->target->setOauthLastRefreshAt($dateTime);

        $result = $this->target->getSettingsBag();

        $this->assertInstanceOf(ParameterBag::class, $result);
        $this->assertEquals($url, $result->get('url'));
        $this->assertEquals($email, $result->get('email'));
        $this->assertEquals($token, $result->get('token'));
        $this->assertEquals($zendeskUserEmail, $result->get('zendeskUserEmail'));
        $this->assertEquals(AuthorizationType::OAUTH->value, $result->get('authorizationType'));
        $this->assertEquals($oauthClientId, $result->get('oauthClientId'));
        $this->assertEquals($accessToken, $result->get('accessToken'));
        $this->assertEquals($refreshToken, $result->get('refreshToken'));
        $this->assertEquals($dateTime, $result->get('oauthLastRefreshAt'));
    }

    public function testGetSettingsBagWithNullValues(): void
    {
        $this->target->setEmail('agent@example.com');
        $this->target->setToken('api-token-123');
        $this->target->setUrl('https://test.zendesk.com');
        $this->target->setZendeskUserEmail('test@example.com');

        $result = $this->target->getSettingsBag();

        $this->assertInstanceOf(ParameterBag::class, $result);
        $this->assertSame(AuthorizationType::DEFAULT->value, $result->get('authorizationType'));
        $this->assertNull($result->get('oauthClientId'));
        $this->assertNull($result->get('accessToken'));
        $this->assertNull($result->get('refreshToken'));
        $this->assertNull($result->get('oauthLastRefreshAt'));
    }

    public function testClearSettings(): void
    {
        $this->target->setUrl('https://test.zendesk.com');
        $this->target->setZendeskUserEmail('test@example.com');
        $this->target->setAccessToken('token-123');

        $settingsBag1 = $this->target->getSettingsBag();
        $this->assertEquals('https://test.zendesk.com', $settingsBag1->get('url'));
        $this->assertEquals('token-123', $settingsBag1->get('accessToken'));

        // Update property and verify cache is still old
        $this->target->setUrl('https://updated.zendesk.com');
        $settingsBag2 = $this->target->getSettingsBag();
        $this->assertEquals('https://test.zendesk.com', $settingsBag2->get('url'));

        // Clear settings and verify new values are reflected
        $this->target->clearSettings();
        $settingsBag3 = $this->target->getSettingsBag();
        $this->assertEquals('https://updated.zendesk.com', $settingsBag3->get('url'));
        $this->assertEquals('token-123', $settingsBag3->get('accessToken'));
    }

    public function testSettingsBagCaching(): void
    {
        $this->target->setUrl('https://test.zendesk.com');

        $settingsBag1 = $this->target->getSettingsBag();
        $settingsBag2 = $this->target->getSettingsBag();

        $this->assertSame($settingsBag1, $settingsBag2);
    }
}
