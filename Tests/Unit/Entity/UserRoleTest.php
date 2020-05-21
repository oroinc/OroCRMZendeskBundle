<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Entity;

use Oro\Bundle\ZendeskBundle\Entity\UserRole;

class UserRoleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var UserRole
     */
    protected $target;

    protected function setUp(): void
    {
        $this->name = UserRole::ROLE_ADMIN;
        $this->target = new UserRole($this->name);
    }

    public function testName()
    {
        $actual = $this->target->getName();
        $this->assertEquals($actual, $this->name);
    }

    public function testIsEqualTo()
    {
        $other = new UserRole(UserRole::ROLE_AGENT);
        $this->assertFalse($this->target->isEqualTo($other));
        $other = new UserRole(UserRole::ROLE_ADMIN);
        $this->assertTrue($this->target->isEqualTo($other));
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
        return array(
            array('label', 'test label'),
            array('locale', 'test locale')
        );
    }
}
