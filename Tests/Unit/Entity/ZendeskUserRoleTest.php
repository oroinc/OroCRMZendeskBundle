<?php

namespace Unit\Entity;

use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskUserRole;

class ZendeskUserRoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var ZendeskUserRole
     */
    protected $target;

    public function setUp()
    {
        $this->name = ZendeskUserRole::ROLE_ADMIN;
        $this->target = new ZendeskUserRole($this->name);
    }

    public function testName()
    {
        $actual = $this->target->getName();
        $this->assertEquals($actual, $this->name);
    }

    public function testIsEqualTo()
    {
        $other = new ZendeskUserRole(ZendeskUserRole::ROLE_AGENT);
        $this->assertFalse($this->target->isEqualTo($other));
        $other = new ZendeskUserRole(ZendeskUserRole::ROLE_ADMIN);
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
