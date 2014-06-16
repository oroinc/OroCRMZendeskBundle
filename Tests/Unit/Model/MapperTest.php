<?php

namespace Unit\Model;

use OroCRM\Bundle\ZendeskBundle\Model\Mapper;

class MapperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetData()
    {
        $expected = array(
            'status_map'   => array(
                array('case' => 'open', 'ticket' => 'new'),
                array('case' => 'open', 'ticket' => 'open'),
                array('case' => 'in_progress', 'ticket' => 'pending'),
                array('case' => 'open', 'ticket' => 'hold'),
                array('case' => 'resolved', 'ticket' => 'solved'),
                array('case' => 'closed', 'ticket' => 'closed')
            ),
            'priority_map' => array(
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

    public function testGetCaseStatus()
    {
        $ticketStatus = 'search ticket status';
        $expected = 'case status';
        $map = array(
            Mapper::STATUS_BRANCH => array(
                array(Mapper::CASE_VALUE_KEY => 'case test', Mapper::TICKET_VALUE_KEY => 'ticket test'),
                array(Mapper::CASE_VALUE_KEY => $expected, Mapper::TICKET_VALUE_KEY => $ticketStatus),
                array(Mapper::CASE_VALUE_KEY => 'case test 1', Mapper::TICKET_VALUE_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getCaseStatus($ticketStatus);
        $this->assertEquals($expected, $actual);
    }

    public function testGetCaseStatusReturnNullIfNotFound()
    {
        $ticketStatus = 'search ticket status';
        $map = array(
            Mapper::STATUS_BRANCH => array(
                array(Mapper::CASE_VALUE_KEY => 'case test', Mapper::TICKET_VALUE_KEY => 'ticket test'),
                array(Mapper::CASE_VALUE_KEY => 'case test 1', Mapper::TICKET_VALUE_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getCaseStatus($ticketStatus);
        $this->assertNull($actual);
    }

    public function testGetTicketStatus()
    {
        $status = 'search case status';
        $expected = 'ticket status';
        $map = array(
            Mapper::STATUS_BRANCH => array(
                array(Mapper::CASE_VALUE_KEY => 'case test', Mapper::TICKET_VALUE_KEY => 'ticket test'),
                array(Mapper::CASE_VALUE_KEY => $status, Mapper::TICKET_VALUE_KEY => $expected),
                array(Mapper::CASE_VALUE_KEY => 'case test 1', Mapper::TICKET_VALUE_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getTicketStatus($status);
        $this->assertEquals($expected, $actual);
    }

    public function testGetTicketPriority()
    {
        $priority = 'search case priority';
        $expected = 'ticket priority';
        $map = array(
            Mapper::PRIORITY_BRANCH => array(
                array(Mapper::CASE_VALUE_KEY => 'case test', Mapper::TICKET_VALUE_KEY => 'ticket test'),
                array(Mapper::CASE_VALUE_KEY => $priority, Mapper::TICKET_VALUE_KEY => $expected),
                array(Mapper::CASE_VALUE_KEY => 'case test 1', Mapper::TICKET_VALUE_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getTicketPriority($priority);
        $this->assertEquals($expected, $actual);
    }

    public function testGetCasePriority()
    {
        $priority = 'search ticket priority';
        $expected = 'case priority';
        $map = array(
            Mapper::PRIORITY_BRANCH => array(
                array(Mapper::CASE_VALUE_KEY => 'case test', Mapper::TICKET_VALUE_KEY => 'ticket test'),
                array(Mapper::CASE_VALUE_KEY => $expected, Mapper::TICKET_VALUE_KEY => $priority),
                array(Mapper::CASE_VALUE_KEY => 'case test 1', Mapper::TICKET_VALUE_KEY => 'ticket test 1'),
            )
        );
        $mapper = $this->getMapper($map);
        $actual = $mapper->getCasePriority($priority);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param array $map
     *
     * @return Mapper
     */
    protected function getMapper($map = array())
    {
        return new Mapper($map);
    }
}
