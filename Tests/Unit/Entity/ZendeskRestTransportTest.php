<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Entity;

use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

class ZendeskRestTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZendeskRestTransport
     */
    protected $target;

    protected function setUp()
    {
        $this->target = new ZendeskRestTransport();
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters($property, $value)
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
        $token = uniqid();

        $this->target->setEmail($email);
        $this->target->setUrl($url);
        $this->target->setToken($token);

        $result = $this->target->getSettingsBag();
        $this->assertEquals($result->get('email'), $email);
        $this->assertEquals($result->get('token'), $token);
        $this->assertEquals($result->get('url'), $url);
    }

    /**
     * @return array
     */
    public function settersAndGettersDataProvider()
    {
        return array(
            array('url', 'test_url.com'),
            array('token', uniqid()),
            array('email', 'test@mail.com'),
            array('zendeskUserEmail', 'zendesk_test@mail.com')
        );
    }
}
