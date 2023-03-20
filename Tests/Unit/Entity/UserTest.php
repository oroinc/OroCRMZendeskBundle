<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Entity;

use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;

class UserTest extends \PHPUnit\Framework\TestCase
{
    private User $target;

    protected function setUp(): void
    {
        $this->target = new User();
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
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

        $this->assertInstanceOf(\DateTime::class, $this->target->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $this->target->getUpdatedAt());

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
        $this->assertInstanceOf(\DateTime::class, $this->target->getUpdatedAt());
    }

    public function settersAndGettersDataProvider(): array
    {
        $role = $this->createMock(UserRole::class);
        $user = $this->createMock(\Oro\Bundle\UserBundle\Entity\User::class);
        $contact = $this->createMock(Contact::class);
        $channel = $this->createMock(Channel::class);

        return [
            ['name', 'test name'],
            ['url', 'test.com'],
            ['email', 'test@mail.com'],
            ['phone', '123456789'],
            ['timeZone', 'UTC'],
            ['locale', 'en'],
            ['url', 'tes.com'],
            ['createdAt', new \DateTime()],
            ['originCreatedAt', new \DateTime()],
            ['originUpdatedAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['lastLoginAt', new \DateTime()],
            ['role', $role],
            ['relatedUser', $user],
            ['externalId', 'test_external_id'],
            ['details', 'details'],
            ['notes', 'notes'],
            ['alias', 'alias'],
            ['onlyPrivateComments', true],
            ['verified', true],
            ['active', true],
            ['channel', $channel],
            ['relatedContact', $contact],
        ];
    }
}
