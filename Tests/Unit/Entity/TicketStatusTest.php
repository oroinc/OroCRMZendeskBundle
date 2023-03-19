<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Entity;

use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;

class TicketStatusTest extends \PHPUnit\Framework\TestCase
{
    private string $name;
    private TicketStatus $target;

    protected function setUp(): void
    {
        $this->name = TicketStatus::STATUS_CLOSED;
        $this->target = new TicketStatus($this->name);
    }

    public function testName()
    {
        $actual = $this->target->getName();
        $this->assertEquals($actual, $this->name);
    }

    public function testIsEqualTo()
    {
        $other = new TicketStatus(TicketStatus::STATUS_HOLD);
        $this->assertFalse($this->target->isEqualTo($other));
        $other = new TicketStatus(TicketStatus::STATUS_CLOSED);
        $this->assertTrue($this->target->isEqualTo($other));
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

    public function settersAndGettersDataProvider(): array
    {
        return [
            ['label', 'test label'],
            ['locale', 'test locale']
        ];
    }
}
