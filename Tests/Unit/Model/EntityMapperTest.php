<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Model;

use OroCRM\Bundle\ZendeskBundle\Model\EntityMapper;

class EntityMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\\ORM\\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())->method('getManager')
            ->willReturn($this->entityManager);
    }

    public function testGetData()
    {
        $expected = array(
            'status'   => array(
                array('case' => 'open', 'ticket' => 'new'),
                array('case' => 'open', 'ticket' => 'open'),
                array('case' => 'in_progress', 'ticket' => 'pending'),
                array('case' => 'open', 'ticket' => 'hold'),
                array('case' => 'resolved', 'ticket' => 'solved'),
                array('case' => 'closed', 'ticket' => 'closed')
            ),
            'priority' => array(
                array('case' => 'low', 'ticket' => 'low'),
                array('case' => 'normal', 'ticket' => 'normal'),
                array('case' => 'high', 'ticket' => 'high'),
                array('case' => 'high', 'ticket' => 'urgent'),
            )
        );

        $mapper = $this->getMapper($expected);
        $map = $mapper->getMap();
        $this->assertEquals($expected, $map);
    }

    public function testGetCaseStatusName()
    {
        $ticketStatus = 'search ticket status';
        $expected = 'case status';
        $map = array(
            EntityMapper::STATUS_KEY => array(
                array(EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'),
                array(EntityMapper::CASE_KEY => $expected, EntityMapper::TICKET_KEY => $ticketStatus),
                array(EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getCaseStatusName($ticketStatus);
        $this->assertEquals($expected, $actual);
    }

    public function testGetCaseStatusNameReturnNullIfNotFound()
    {
        $ticketStatus = 'search ticket status';
        $map = array(
            EntityMapper::STATUS_KEY => array(
                array(EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'),
                array(EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getCaseStatusName($ticketStatus);
        $this->assertNull($actual);
    }

    public function testGetTicketStatusName()
    {
        $status = 'search case status';
        $expected = 'ticket status';
        $map = array(
            EntityMapper::STATUS_KEY => array(
                array(EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'),
                array(EntityMapper::CASE_KEY => $status, EntityMapper::TICKET_KEY => $expected),
                array(EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getTicketStatusName($status);
        $this->assertEquals($expected, $actual);
    }

    public function testGetTicketPriorityName()
    {
        $priority = 'search case priority';
        $expected = 'ticket priority';
        $map = array(
            EntityMapper::PRIORITY_KEY => array(
                array(EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'),
                array(EntityMapper::CASE_KEY => $priority, EntityMapper::TICKET_KEY => $expected),
                array(EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getTicketPriorityName($priority);
        $this->assertEquals($expected, $actual);
    }

    public function testGetCasePriorityName()
    {
        $priority = 'search ticket priority';
        $expected = 'case priority';
        $map = array(
            EntityMapper::PRIORITY_KEY => array(
                array(EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'),
                array(EntityMapper::CASE_KEY => $expected, EntityMapper::TICKET_KEY => $priority),
                array(EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getCasePriorityName($priority);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param array $map
     * @return EntityMapper
     */
    protected function getMapper($map = array())
    {
        return new EntityMapper($this->registry, $map);
    }
}
