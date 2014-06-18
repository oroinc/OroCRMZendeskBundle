<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Entity;

use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;

class TicketPriorityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var TicketPriority
     */
    protected $target;

    public function setUp()
    {
        $this->name = TicketPriority::PRIORITY_HIGH;
        $this->target = new TicketPriority($this->name);
    }

    public function testName()
    {
        $actual = $this->target->getName();
        $this->assertEquals($actual, $this->name);
    }

    public function testIsEqualTo()
    {
        $other = new TicketPriority(TicketPriority::PRIORITY_LOW);
        $this->assertFalse($this->target->isEqualTo($other));
        $other = new TicketPriority(TicketPriority::PRIORITY_HIGH);
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
