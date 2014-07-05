<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Placeholder\Filter;

use OroCRM\Bundle\ZendeskBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlaceholderFilter
     */
    protected $filter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $oroProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $zendeskProvider;

    protected function setUp()
    {
        $this->oroProvider = $this->getMockBuilder(
            'OroCRM\\Bundle\\ZendeskBundle\\Model\\EntityProvider\\OroEntityProvider'
        )->disableOriginalConstructor()->getMock();

        $this->zendeskProvider = $this->getMockBuilder(
            'OroCRM\\Bundle\\ZendeskBundle\\Model\\EntityProvider\\ZendeskEntityProvider'
        )->disableOriginalConstructor()->getMock();

        $this->filter = new PlaceholderFilter($this->oroProvider, $this->zendeskProvider);
    }

    public function testTicketAvailableIgnoreNotInstanceOrCaseEntity()
    {
        $entity = new \StdClass();

        $this->zendeskProvider->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->filter->isTicketAvailable($entity));
    }

    public function testTicketAvailable()
    {
        $entity = $this->getMock('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseEntity');
        $ticket = $this->getMock('OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket');

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->will($this->returnValue($ticket));

        $this->assertTrue($this->filter->isTicketAvailable($entity));
    }

    public function testTicketNotAvailable()
    {
        $entity = $this->getMock('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseEntity');

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->will($this->returnValue(null));

        $this->assertFalse($this->filter->isTicketAvailable($entity));
    }

    public function testSyncApplicableIgnoreNotInstanceOrCaseEntity()
    {
        $entity = new \StdClass();

        $this->zendeskProvider->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->filter->isSyncApplicableForCaseEntity($entity));
    }

    public function testSyncApplicable()
    {
        $entity = $this->getMock('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseEntity');
        $channel = $this->getMock('Oro\\Bundle\\IntegrationBundle\\Entity\\Channel');

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->will($this->returnValue(null));

        $this->oroProvider->expects($this->once())
            ->method('getEnabledTwoWaySyncChannels')
            ->will($this->returnValue([$channel]));

        $this->assertTrue($this->filter->isSyncApplicableForCaseEntity($entity));
    }

    public function testSyncNotApplicable()
    {
        $entity = $this->getMock('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseEntity');

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->will($this->returnValue(null));

        $this->oroProvider->expects($this->once())
            ->method('getEnabledTwoWaySyncChannels')
            ->will($this->returnValue([]));

        $this->assertFalse($this->filter->isSyncApplicableForCaseEntity($entity));
    }

    public function testSyncNotApplicableForExistingTicket()
    {
        $entity = $this->getMock('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseEntity');
        $ticket = $this->getMock('OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket');

        $this->zendeskProvider->expects($this->once())
            ->method('getTicketByCase')
            ->with($entity)
            ->will($this->returnValue($ticket));

        $this->oroProvider->expects($this->never())->method($this->anything());

        $this->assertFalse($this->filter->isSyncApplicableForCaseEntity($entity));
    }
}
