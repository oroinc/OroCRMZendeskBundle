<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider;

use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Provider\UserPhoneProvider;

class UserPhoneProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var UserPhoneProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new UserPhoneProvider();
    }

    public function testGetPhoneNumber()
    {
        $entity = new User();

        $this->assertNull(
            $this->provider->getPhoneNumber($entity)
        );

        $entity->setPhone('123-123');
        $this->assertEquals(
            '123-123',
            $this->provider->getPhoneNumber($entity)
        );
    }

    public function testGetPhoneNumbers()
    {
        $entity = new User();

        $this->assertSame(
            [],
            $this->provider->getPhoneNumbers($entity)
        );

        $entity->setPhone('123-123');
        $this->assertEquals(
            [
                ['123-123', $entity]
            ],
            $this->provider->getPhoneNumbers($entity)
        );
    }
}
