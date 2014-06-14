<?php

namespace Unit\Entity;

use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskUser;

class ZendeskUserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZendeskUser
     */
    protected $target;

    public function setUp()
    {
        $this->target = new ZendeskUser();
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

    /**
     * @return array
     */
    public function settersAndGettersDataProvider()
    {
        $role = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\ZendeskUserRole')
            ->disableOriginalConstructor()
            ->getMock();

        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $contact = $this->getMockBuilder('OroCRM\Bundle\ContactBundle\Entity\Contact')
            ->disableOriginalConstructor()
            ->getMock();

        return array(
            array('name', 'test name'),
            array('url', 'test.com'),
            array('email', 'test@mail.com'),
            array('phone', '123456789'),
            array('timeZone', 'UTC'),
            array('locale', 'en'),
            array('url', 'tes.com'),
            array('createdAt', new \DateTime()),
            array('updatedAt', new \DateTime()),
            array('role', $role),
            array('user', $user),
            array('owner', $user),
            array('contact', $contact),
        );
    }
}
