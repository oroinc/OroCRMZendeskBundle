<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Entity;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

class ZendeskRestTransportTest extends \PHPUnit\Framework\TestCase
{
    private ZendeskRestTransport $target;

    protected function setUp(): void
    {
        $this->target = new ZendeskRestTransport();
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(string $property, string $value)
    {
        $method = 'set' . ucfirst($property);
        $result = $this->target->$method($value);

        $this->assertInstanceOf(get_class($this->target), $result);
        $this->assertEquals($value, $this->target->{'get' . $property}());
    }

    public function testGetSettingsBag()
    {
        $email = 'test@mail.com';
        $url = 'test_url.com';
        $token = 'test_token';

        $this->target->setEmail($email);
        $this->target->setUrl($url);
        $this->target->setToken($token);

        $result = $this->target->getSettingsBag();
        $this->assertEquals($result->get('email'), $email);
        $this->assertEquals($result->get('token'), $token);
        $this->assertEquals($result->get('url'), $url);
    }

    public function settersAndGettersDataProvider(): array
    {
        return [
            ['url', 'test_url.com'],
            ['token', 'test_token'],
            ['email', 'test@mail.com'],
            ['zendeskUserEmail', 'zendesk_test@mail.com']
        ];
    }
}
