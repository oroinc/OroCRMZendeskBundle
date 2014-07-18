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
use OroCRM\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
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
     * @param bool       $flush
     * @return bool
     */
    public function syncCase(CaseEntity $caseEntity, Channel $channel, $flush = false)
    {
        if ($this->zendeskEntityProvider->getTicketByCase($caseEntity) || !$this->isTwoWaySyncEnabled($channel)) {
            return false;
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

        $this->syncScheduler->schedule($channel, TicketConnector::TYPE, array('id' => $ticket->getId()), $flush);

        return true;
    }

    /**
     * @param CaseComment $caseComment
     * @return bool
     */
    public function syncComment(CaseComment $caseComment)
    {
        if ($caseComment->getId()) {
            return false;
        }

        $ticket = $this->zendeskEntityProvider->getTicketByCase($caseComment->getCase());

        if (!$ticket) {
            return false;
        }
        $channel = $ticket->getChannel();

        $ticketComment = new TicketComment();
        $ticketComment->setChannel($channel);
        $ticketComment->setRelatedComment($caseComment);
        $ticketComment->setTicket($ticket);

        $this->entityManager->persist($ticketComment);
        $this->entityManager->flush($ticketComment);

        if ($this->isTwoWaySyncEnabled($channel)) {
            $this->syncScheduler->schedule(
                $channel,
                TicketCommentConnector::TYPE,
                array('id' => $ticketComment->getId()),
                false
            );
        }

        return true;
    }

    /**
     * @param Channel $channel
     * @return bool
     */
    public function reverseSyncChannel(Channel $channel)
    {
        if (!$this->isTwoWaySyncEnabled($channel)) {
            return false;
        }

        $ticketComments = $this->zendeskEntityProvider->getNotSyncedTicketComments($channel);
        $ids = array();

        $ticketComments->rewind();
        while ($ticketComments->valid()) {
            /**
             * @var TicketComment $ticketComment
             */
            $ticketComment = $ticketComments->current();
            $ticketComments->next();
            $ids[] = $ticketComment->getId();

            if (!$ticketComments->valid() || count($ids) == 100) {
                $this->syncScheduler->schedule(
                    $channel,
                    TicketCommentConnector::TYPE,
                    array('id' => $ids),
                    true
                );
                $ids = array();
            }
        }

        return true;
    }

    /**
     * @param Channel $channel
     * @return bool
     */
    protected function isTwoWaySyncEnabled(Channel $channel)
    {
        $isTwoWaySyncEnabled = $channel->getSynchronizationSettings()
            ->offsetGetOr('isTwoWaySyncEnabled', false);
        return $isTwoWaySyncEnabled;
    }
}
