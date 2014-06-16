<?php

namespace Unit\Entity;

use OroCRM\Bundle\ZendeskBundle\Entity\User;

class UserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var User
     */
    protected $target;

    public function setUp()
    {
        $this->target = new User();
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
        $role = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\UserRole')
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
            array('lastLoginAt', new \DateTime()),
            array('role', $role),
            array('user', $user),
            array('externalId', uniqid()),
            array('details', 'details'),
            array('notes', 'notes'),
            array('alias', 'alias'),
            array('onlyPrivateComments', true),
            array('verified', true),
            array('active', true),
            array('contact', $contact),
        );
    }
}
