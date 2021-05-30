<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\SyncManager;
use Oro\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
use Oro\Component\Config\Common\ConfigObject;
use Oro\Component\Testing\ReflectionUtil;

class SyncManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SyncScheduler|\PHPUnit\Framework\MockObject\MockObject */
    private $scheduler;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ZendeskEntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $zendeskEntityProvider;

    /** @var SyncManager */
    private $syncManager;

    protected function setUp(): void
    {
        $this->scheduler = $this->createMock(SyncScheduler::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->zendeskEntityProvider = $this->createMock(ZendeskEntityProvider::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->entityManager);

        $this->syncManager = new SyncManager($this->scheduler, $doctrine, $this->zendeskEntityProvider);
    }

    public function testSyncCommentSyncOnlyNewComments()
    {
        $existComment = $this->createMock(CaseComment::class);
        $existComment->expects($this->once())
            ->method('getId')
            ->willReturn(42);
        $this->zendeskEntityProvider->expects($this->never())
            ->method('getTicketByCase');
        $this->assertFalse($this->syncManager->syncComment($existComment));
    }

    public function testSyncCommentSyncOnlyIfChannelHasTwoWaySyncEnabled()
    {
        $case = $this->createMock(CaseEntity::class);
        $comment = $this->createMock(CaseComment::class);
        $comment->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $comment->expects($this->once())
            ->method('getCase')
            ->willReturn($case);
        $ticket = $this->createMock(Ticket::class);
        $channel = $this->getChannel(false);
        $ticket->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);
        $this->zendeskEntityProvider->expects($this->once())
            ->method('getTicketByCase')
            ->willReturn($ticket);

        $this->scheduler->expects($this->never())
            ->method('schedule');

        $this->assertTrue($this->syncManager->syncComment($comment));
    }

    public function testSyncCommentSync()
    {
        $comment = $this->createMock(CaseComment::class);
        $case = $this->createMock(CaseEntity::class);
        $comment->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $comment->expects($this->once())
            ->method('getCase')
            ->willReturn($case);
        $ticket = $this->createMock(Ticket::class);
        $channel = $this->getChannel(true);
        $ticket->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);

        $this->zendeskEntityProvider->expects($this->once())
            ->method('getTicketByCase')
            ->willReturn($ticket);
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
        $this->assertTrue($this->syncManager->syncComment($comment));
    }

    public function testSyncCaseSyncOnlyIfChannelHasTwoWaySyncEnabled()
    {
        $case = $this->createMock(CaseEntity::class);
        $channel = $this->getChannel(false);

        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');
        $this->scheduler->expects($this->never())
            ->method('schedule');

        $this->assertFalse($this->syncManager->syncCase($case, $channel));
    }

    public function testSyncCase()
    {
        $case = $this->createMock(CaseEntity::class);
        $firstComment = $this->createMock(CaseComment::class);
        $secondComment = $this->createMock(CaseComment::class);
        $comments = [
            $firstComment,
            $secondComment
        ];
        $case->expects($this->once())
            ->method('getComments')
            ->willReturn($comments);
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
        $this->syncManager->syncCase($case, $channel);
    }

    public function testReverseSyncChannel()
    {
        $channel = $this->getChannel(false);
        $this->assertFalse($this->syncManager->reverseSyncChannel($channel));
        $comments = [];
        $expectedIds = [];
        for ($i = 1; $i < 102; $i++) {
            $comment = new TicketComment();
            ReflectionUtil::setId($comment, $i);
            $comments[] = $comment;
            $expectedIds[]  = $i;
        }
        unset($expectedIds[100]);
        $channel = $this->getChannel(true);

        $this->zendeskEntityProvider->expects($this->once())
            ->method('getNotSyncedTicketComments')
            ->with($channel)
            ->willReturn(new \ArrayIterator($comments));
        $this->scheduler->expects($this->exactly(2))
            ->method('schedule')
            ->withConsecutive(
                [$channel->getId(), TicketCommentConnector::TYPE, ['id' => $expectedIds]],
                [$channel->getId(), TicketCommentConnector::TYPE, ['id' => [101]]]
            );

        $this->assertTrue($this->syncManager->reverseSyncChannel($channel));
    }

    private function getChannel(bool $isTwoWaySyncEnabled): Channel
    {
        $synchronizationSettings = $this->createMock(ConfigObject::class);
        $synchronizationSettings->expects($this->once())
            ->method('offsetGetOr')
            ->with('isTwoWaySyncEnabled', false)
            ->willReturn($isTwoWaySyncEnabled);

        $channel = new Channel();
        ReflectionUtil::setId($channel, 123);
        $channel->setSynchronizationSettings($synchronizationSettings);

        return $channel;
    }
}
