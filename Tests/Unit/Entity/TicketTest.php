<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Entity;

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
        $comment = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\TicketComment')
            ->disableOriginalConstructor()
            ->getMock();
        $ticket = new Ticket();
        $collaborators = new ArrayCollection(array($zendeskUser));
        $comments = new ArrayCollection(array($comment));

        $channel = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        return array(
            array('originId', 123456789),
            array('url', 'test.com'),
            array('subject', 'test subject'),
            array('description', 'test description'),
            array('recipient', 'test@mail.com'),
            array('type', $ticketType),
            array('status', $ticketStatus),
            array('priority', $ticketPriority),
            array('createdAt', new \DateTime()),
            array('updatedAt', new \DateTime()),
            array('originCreatedAt', new \DateTime()),
            array('originUpdatedAt', new \DateTime()),
            array('dueAt', new \DateTime()),
            array('requester', $zendeskUser),
            array('assignee', $zendeskUser),
            array('submitter', $zendeskUser),
            array('relatedCase', $case),
            array('externalId', uniqid()),
            array('problem', $ticket),
            array('channel', $channel),
            array('collaborators', $collaborators),
            array('hasIncidents', true),
            array('comments', $comments),
        );
    }
}
