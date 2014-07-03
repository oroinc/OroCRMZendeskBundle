<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketConnector;

class SyncManager
{
    /**
     * @var SyncScheduler
     */
    protected $syncScheduler;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskEntityProvider;

    /**
     * @param SyncScheduler         $syncScheduler
     * @param EntityManager         $entityManager
     * @param ZendeskEntityProvider $zendeskEntityProvider
     */
    public function __construct(
        SyncScheduler $syncScheduler,
        EntityManager $entityManager,
        ZendeskEntityProvider $zendeskEntityProvider
    ) {
        $this->syncScheduler = $syncScheduler;
        $this->entityManager = $entityManager;
        $this->zendeskEntityProvider = $zendeskEntityProvider;
    }

    /**
     * @param CaseEntity $caseEntity
     * @param Channel    $channel
     */
    public function syncCase(CaseEntity $caseEntity, Channel $channel)
    {
        if ($this->zendeskEntityProvider->getTicketByCase($caseEntity)) {
            return;
        }

        $ticket = new Ticket();
        $ticket->setChannel($channel);
        $ticket->setRelatedCase($caseEntity);

        $comments = $caseEntity->getComments();

        foreach ($comments as $comment) {
            $ticketComment = new TicketComment();
            $ticketComment->setChannel($channel);
            $ticketComment->setRelatedComment($comment);

            $ticket->addComment($ticketComment);
        }

        $this->entityManager->persist($ticket);
        $this->entityManager->flush($ticket);

        $this->syncScheduler->schedule($channel, TicketConnector::TYPE, array('id' => $ticket->getId()), false);
        foreach ($ticket->getComments() as $comment) {
            $this->scheduleCommentJob($channel, $comment);
        }
    }

    public function syncComment(CaseComment $caseComment)
    {
        if ($caseComment->getId()) {
            return;
        }

        $ticket = $this->zendeskEntityProvider->getTicketByCase($caseComment->getCase());

        if (!$ticket) {
            return;
        }

        $ticketComment = new TicketComment();
        $ticketComment->setChannel($ticket->getChannel());
        $ticketComment->setRelatedComment($caseComment);
        $ticket->addComment($ticketComment);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush($ticket);

        $this->scheduleCommentJob($ticket->getChannel(), $ticketComment);
    }

    /**
     * @param Channel $channel
     * @param TicketComment $comment
     */
    protected function scheduleCommentJob(Channel $channel, TicketComment $comment)
    {
        $this->syncScheduler->schedule($channel, TicketConnector::TYPE, array('id' => $comment->getId()), false);
    }
}
