<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Model;

use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Model\SyncManager;
use Oro\Bundle\ZendeskBundle\Provider\TicketCommentConnector;

class SyncManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SyncManager
     */
    protected $target;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $scheduler;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $zendeskEntityProvider;

    protected function setUp(): void
    {
        $this->scheduler = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\SyncScheduler')
            ->disableOriginalConstructor()->getMock();
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())->method('getManager')
            ->willReturn($this->entityManager);
        $this->zendeskEntityProvider = $this->getMockBuilder(
            'Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider'
        )
            ->disableOriginalConstructor()->getMock();

        $this->target = new SyncManager($this->scheduler, $this->registry, $this->zendeskEntityProvider);
    }

    public function testSyncCommentSyncOnlyNewComments()
    {
        $existComment = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseComment');
        $existComment->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(42));
        $this->zendeskEntityProvider->expects($this->never())
            ->method('getTicketByCase');
        $this->assertFalse($this->target->syncComment($existComment));
    }

    public function testSyncCommentSyncOnlyIfChannelHasTwoWaySyncEnabled()
    {
        $case = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseEntity');
        $comment = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseComment');
        $comment->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));
        $comment->expects($this->once())
            ->method('getCase')
            ->will($this->returnValue($case));
        $ticket = $this->createMock('Oro\Bundle\ZendeskBundle\Entity\Ticket');
        $channel = $this->getChannel(false);
        $ticket->expects($this->once())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $this->zendeskEntityProvider->expects($this->once())
            ->method('getTicketByCase')
            ->will($this->returnValue($ticket));

        $this->scheduler->expects($this->never())
            ->method('schedule');

        $this->assertTrue($this->target->syncComment($comment));
    }

    public function testSyncCommentSync()
    {
        $comment = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseComment');
        $case = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseEntity');
        $comment->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));
        $comment->expects($this->once())
            ->method('getCase')
            ->will($this->returnValue($case));
        $ticket = $this->createMock('Oro\Bundle\ZendeskBundle\Entity\Ticket');
        $channel = $this->getChannel(true);
        $ticket->expects($this->once())
            ->method('getChannel')
            ->will($this->returnValue($channel));

        $this->zendeskEntityProvider->expects($this->once())
            ->method('getTicketByCase')
            ->will($this->returnValue($ticket));
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with(self::isInstanceOf(TicketComment::class))
            ->willReturnCallback(function (TicketComment $ticketComment) use ($channel, $ticket, $comment) {
                $this->assertEquals($channel, $ticketComment->getChannel());
                $this->assertEquals($ticket, $ticketComment->getTicket());
                $this->assertEquals($comment, $ticketComment->getRelatedComment());

                return true;
            });
        $this->scheduler->expects($this->once())
            ->method('schedule')
            ->with($channel->getId(), TicketCommentConnector::TYPE, $this->arrayHasKey('id'));
        $this->assertTrue($this->target->syncComment($comment));
    }

    public function testSyncCaseSyncOnlyIfChannelHasTwoWaySyncEnabled()
    {
        $case = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseEntity');
        $channel = $this->getChannel(false);

        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');
        $this->scheduler->expects($this->never())
            ->method('schedule');

        $this->assertFalse($this->target->syncCase($case, $channel));
    }

    public function testSyncCase()
    {
        $case = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseEntity');
        $firstComment = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseComment');
        $secondComment = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseComment');
        $comments = [
            $firstComment,
            $secondComment
        ];
        $case->expects($this->once())
            ->method('getComments')
            ->will($this->returnValue($comments));
        $channel = $this->getChannel(true);
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function (Ticket $ticket) use ($channel, $comments, $case) {
                        $this->assertEquals($channel, $ticket->getChannel());
                        $this->assertEquals($case, $ticket->getRelatedCase());
                        $this->assertCount(count($comments), $ticket->getComments());
                        foreach ($ticket->getComments() as $ticketComment) {
                            $this->assertEquals(current($comments), $ticketComment->getRelatedComment());
                            $this->assertEquals($channel, $ticketComment->getChannel());
                            next($comments);
                        }
                        return true;
                    }
                )
            );
        $this->target->syncCase($case, $channel);
    }

    public function testReverseSyncChannel()
    {
        $channel = $this->getChannel(false);
        $this->assertFalse($this->target->reverseSyncChannel($channel));
        $comments = [];
        $expectedIds = [];
        $comment = $this->createMock('Oro\Bundle\ZendeskBundle\Entity\TicketComment');
        for ($i=1; $i< 102; $i++) {
            $comment->expects($this->at($i-1))
                ->method('getId')
                ->will($this->returnValue($i));
            $comments[] = $comment;
            $expectedIds[]  = $i;
        }
        unset($expectedIds[100]);
        $channel = $this->getChannel(true);

        $this->zendeskEntityProvider->expects($this->once())
            ->method('getNotSyncedTicketComments')
            ->with($channel)
            ->will($this->returnValue(new \ArrayIterator($comments)));
        $this->scheduler->expects($this->at(0))
            ->method('schedule')
            ->with($channel->getId(), TicketCommentConnector::TYPE, ['id' => $expectedIds]);
        $this->scheduler->expects($this->at(1))
            ->method('schedule')
            ->with($channel->getId(), TicketCommentConnector::TYPE, ['id' => [101]]);
        $this->assertTrue($this->target->reverseSyncChannel($channel));
    }

    /**
     * @param bool $isTwoWaySyncEnabled
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getChannel($isTwoWaySyncEnabled)
    {
        $channel = $this->createMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $channel
            ->expects($this->any())
            ->method('getId')
            ->willReturn(123)
        ;

        $synchronizationSettings = $this->getMockBuilder('Oro\Component\Config\Common\ConfigObject')
            ->disableOriginalConstructor()
            ->getMock();
        $synchronizationSettings->expects($this->once())
            ->method('offsetGetOr')
            ->with('isTwoWaySyncEnabled', false)
            ->will($this->returnValue($isTwoWaySyncEnabled));
        $channel->expects($this->once())
            ->method('getSynchronizationSettings')
            ->will($this->returnValue($synchronizationSettings));
        return $channel;
    }
}
