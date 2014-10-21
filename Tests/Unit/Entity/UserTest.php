<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Entity;

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

    public function testSetUpdatedAtLockedUpdateByLifeCycleCallback()
    {
        $expected = date_create_from_format('Y-m-d', '2012-10-10');
        $this->target->setUpdatedAt($expected);
        $this->target->preUpdate();
        $this->assertSame($expected, $this->target->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $this->assertNull($this->target->getCreatedAt());
        $this->assertNull($this->target->getUpdatedAt());

        $this->target->prePersist();

        $this->assertInstanceOf('\DateTime', $this->target->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $this->target->getUpdatedAt());

        $expectedCreated = $this->target->getCreatedAt();
        $expectedUpdated = $this->target->getUpdatedAt();

        $this->target->prePersist();

        $this->assertSame($expectedCreated, $this->target->getCreatedAt());
        $this->assertSame($expectedUpdated, $this->target->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->target->getUpdatedAt());
        $this->target->preUpdate();
        $this->assertInstanceOf('\DateTime', $this->target->getUpdatedAt());
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

        $channel = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
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
            array('originCreatedAt', new \DateTime()),
            array('originUpdatedAt', new \DateTime()),
            array('updatedAt', new \DateTime()),
            array('lastLoginAt', new \DateTime()),
            array('role', $role),
            array('relatedUser', $user),
            array('externalId', uniqid()),
            array('details', 'details'),
            array('notes', 'notes'),
            array('alias', 'alias'),
            array('onlyPrivateComments', true),
            array('verified', true),
            array('active', true),
            array('channel', $channel),
            array('relatedContact', $contact),
        );
    }
}
