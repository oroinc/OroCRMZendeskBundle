<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Placeholder\Filter;

use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Oro\Bundle\ZendeskBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var PlaceholderFilter */
    private $filter;

    /** @var OroEntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $oroProvider;

    /** @var ZendeskEntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $zendeskProvider;

    protected function setUp(): void
    {
        $this->oroProvider = $this->createMock(OroEntityProvider::class);
        $this->zendeskProvider = $this->createMock(ZendeskEntityProvider::class);

        $this->filter = new PlaceholderFilter($this->oroProvider, $this->zendeskProvider);
    }

    public function testTicketAvailableIgnoreNotInstanceOrCaseEntity()
    {
        $entity = new \stdClass();

        $this->zendeskProvider->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->filter->isTicketAvailable($entity));
    }

    public function testTicketAvailable()
    {
        $entity = $this->createMock(CaseEntity::class);
        $ticket = $this->createMock(Ticket::class);

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->willReturn($ticket);

        $this->assertTrue($this->filter->isTicketAvailable($entity));
    }

    public function testTicketNotAvailable()
    {
        $entity = $this->createMock(CaseEntity::class);

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->willReturn(null);

        $this->assertFalse($this->filter->isTicketAvailable($entity));
    }

    public function testSyncApplicableIgnoreNotInstanceOrCaseEntity()
    {
        $entity = new \stdClass();

        $this->zendeskProvider->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->filter->isSyncApplicableForCaseEntity($entity));
    }

    public function testSyncApplicable()
    {
        $entity = $this->createMock(CaseEntity::class);
        $channel = $this->createMock(Channel::class);

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->willReturn(null);

        $this->oroProvider->expects($this->once())
            ->method('getEnabledTwoWaySyncChannels')
            ->willReturn([$channel]);

        $this->assertTrue($this->filter->isSyncApplicableForCaseEntity($entity));
    }

    public function testSyncNotApplicable()
    {
        $entity = $this->createMock(CaseEntity::class);

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->willReturn(null);

        $this->oroProvider->expects($this->once())
            ->method('getEnabledTwoWaySyncChannels')
            ->willReturn([]);

        $this->assertFalse($this->filter->isSyncApplicableForCaseEntity($entity));
    }

    public function testSyncNotApplicableForExistingTicket()
    {
        $entity = $this->createMock(CaseEntity::class);
        $ticket = $this->createMock(Ticket::class);

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->willReturn($ticket);

        $this->oroProvider->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->filter->isSyncApplicableForCaseEntity($entity));
    }
}
