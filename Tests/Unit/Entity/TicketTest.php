<?php

namespace Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;

class TicketTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Ticket
     */
    protected $target;

    public function setUp()
    {
        $this->target = new Ticket();
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
        $zendeskUser = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $ticketType = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\TicketType')
            ->disableOriginalConstructor()
            ->getMock();
        $ticketStatus = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus')
            ->disableOriginalConstructor()
            ->getMock();
        $ticketPriority = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority')
            ->disableOriginalConstructor()
            ->getMock();
        $case = $this->getMockBuilder('OroCRM\Bundle\CaseBundle\Entity\CaseEntity')
            ->disableOriginalConstructor()
            ->getMock();
        $ticket = new Ticket();
        $collaborators = new ArrayCollection(array($zendeskUser));
        return array(
            array('url', 'test.com'),
            array('subject', 'test subject'),
            array('description', 'test description'),
            array('recipient', 'test@mail.com'),
            array('type', $ticketType),
            array('status', $ticketStatus),
            array('priority', $ticketPriority),
            array('createdAt', new \DateTime()),
            array('updatedAt', new \DateTime()),
            array('dueAt', new \DateTime()),
            array('requester', $zendeskUser),
            array('assignee', $zendeskUser),
            array('submitter', $zendeskUser),
            array('case', $case),
            array('externalId', uniqid()),
            array('problem', $ticket),
            array('collaborators', $collaborators),
            array('hasIncidents', true)
        );
    }
}