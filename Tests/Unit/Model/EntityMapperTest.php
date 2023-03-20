<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ZendeskBundle\Model\EntityMapper;

class EntityMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->entityManager);
    }

    public function testGetData()
    {
        $expected = [
            'status'   => [
                ['case' => 'open', 'ticket' => 'new'],
                ['case' => 'open', 'ticket' => 'open'],
                ['case' => 'in_progress', 'ticket' => 'pending'],
                ['case' => 'open', 'ticket' => 'hold'],
                ['case' => 'resolved', 'ticket' => 'solved'],
                ['case' => 'closed', 'ticket' => 'closed']
            ],
            'priority' => [
                ['case' => 'low', 'ticket' => 'low'],
                ['case' => 'normal', 'ticket' => 'normal'],
                ['case' => 'high', 'ticket' => 'high'],
                ['case' => 'high', 'ticket' => 'urgent'],
            ]
        ];

        $mapper = $this->getMapper($expected);
        $map = $mapper->getMap();
        $this->assertEquals($expected, $map);
    }

    public function testGetCaseStatusName()
    {
        $ticketStatus = 'search ticket status';
        $expected = 'case status';
        $map = [
            EntityMapper::STATUS_KEY => [
                [EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'],
                [EntityMapper::CASE_KEY => $expected, EntityMapper::TICKET_KEY => $ticketStatus],
                [EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'],
            ]
        ];
        $mapper = $this->getMapper($map);
        $actual = $mapper->getCaseStatusName($ticketStatus);
        $this->assertEquals($expected, $actual);
    }

    public function testGetCaseStatusNameReturnNullIfNotFound()
    {
        $ticketStatus = 'search ticket status';
        $map = [
            EntityMapper::STATUS_KEY => [
                [EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'],
                [EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'],
            ]
        ];
        $mapper = $this->getMapper($map);
        $actual = $mapper->getCaseStatusName($ticketStatus);
        $this->assertNull($actual);
    }

    public function testGetTicketStatusName()
    {
        $status = 'search case status';
        $expected = 'ticket status';
        $map = [
            EntityMapper::STATUS_KEY => [
                [EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'],
                [EntityMapper::CASE_KEY => $status, EntityMapper::TICKET_KEY => $expected],
                [EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'],
            ]
        ];
        $mapper = $this->getMapper($map);
        $actual = $mapper->getTicketStatusName($status);
        $this->assertEquals($expected, $actual);
    }

    public function testGetTicketPriorityName()
    {
        $priority = 'search case priority';
        $expected = 'ticket priority';
        $map = [
            EntityMapper::PRIORITY_KEY => [
                [EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'],
                [EntityMapper::CASE_KEY => $priority, EntityMapper::TICKET_KEY => $expected],
                [EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'],
            ]
        ];
        $mapper = $this->getMapper($map);
        $actual = $mapper->getTicketPriorityName($priority);
        $this->assertEquals($expected, $actual);
    }

    public function testGetCasePriorityName()
    {
        $priority = 'search ticket priority';
        $expected = 'case priority';
        $map = [
            EntityMapper::PRIORITY_KEY => [
                [EntityMapper::CASE_KEY => 'case test', EntityMapper::TICKET_KEY => 'ticket test'],
                [EntityMapper::CASE_KEY => $expected, EntityMapper::TICKET_KEY => $priority],
                [EntityMapper::CASE_KEY => 'case test 1', EntityMapper::TICKET_KEY => 'ticket test 1'],
            ]
        ];
        $mapper = $this->getMapper($map);
        $actual = $mapper->getCasePriorityName($priority);
        $this->assertEquals($expected, $actual);
    }

    private function getMapper(array $map = []): EntityMapper
    {
        return new EntityMapper($this->registry, $map);
    }
}
