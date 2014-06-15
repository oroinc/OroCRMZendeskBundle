<?php

namespace Unit\Entity;

use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskSyncState;

class ZendeskSyncStateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZendeskSyncState
     */
    protected $target;

    /**
     * @var int
     */
    protected $id;

    public function setUp()
    {
        $this->id = 42;
        $this->target = new ZendeskSyncState($this->id);
    }

    public function testId()
    {
        $actual = $this->target->getId();
        $this->assertEquals($actual, $this->id);
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
            array('userSync', new \DateTime()),
            array('ticketSync', new \DateTime())
        );
    }
}
