<?php

namespace Oro\Bundle\ZendeskBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Oro\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
use Oro\Bundle\ZendeskBundle\Provider\TicketConnector;

class SyncManager
{
    /**
     * @var SyncScheduler
     */
    protected $syncScheduler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskEntityProvider;

    public function __construct(
        SyncScheduler $syncScheduler,
        ManagerRegistry $registry,
        ZendeskEntityProvider $zendeskEntityProvider
    ) {
        $this->syncScheduler         = $syncScheduler;
        $this->registry              = $registry;
        $this->zendeskEntityProvider = $zendeskEntityProvider;
    }

    /**
     * @param CaseEntity $caseEntity
     * @param Channel    $channel
     * @param bool       $flush
     *
     * @return bool
     */
    public function syncCase(CaseEntity $caseEntity, Channel $channel, $flush = false)
    {
        if ($this->zendeskEntityProvider->getTicketByCase($caseEntity) || !$this->isTwoWaySyncEnabled($channel)) {
            return false;
        }

        $em = $this->registry->getManager();

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

        $em->persist($ticket);
        $em->flush($ticket);

        $this->syncScheduler->schedule($channel->getId(), TicketConnector::TYPE, array('id' => $ticket->getId()));

        return true;
    }

    /**
     * @param CaseComment $caseComment
     *
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
        $em      = $this->registry->getManager();
        $channel = $ticket->getChannel();

        $ticketComment = new TicketComment();
        $ticketComment->setChannel($channel);
        $ticketComment->setRelatedComment($caseComment);
        $ticketComment->setTicket($ticket);

        $em->persist($ticketComment);
        $em->flush($ticketComment);

        if ($this->isTwoWaySyncEnabled($channel)) {
            $this->syncScheduler->schedule(
                $channel->getId(),
                TicketCommentConnector::TYPE,
                array('id' => $ticketComment->getId())
            );
        }

        return true;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function reverseSyncChannel(Channel $channel)
    {
        if (!$this->isTwoWaySyncEnabled($channel)) {
            return false;
        }

        $ticketComments = $this->zendeskEntityProvider->getNotSyncedTicketComments($channel);
        $ids            = array();

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
                    $channel->getId(),
                    TicketCommentConnector::TYPE,
                    // @TODO: change key id to ids more see in ticket CRM-8405
                    array('id' => $ids)
                );
                $ids = array();
            }
        }

        return true;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    protected function isTwoWaySyncEnabled(Channel $channel)
    {
        $isTwoWaySyncEnabled = $channel->getSynchronizationSettings()
            ->offsetGetOr('isTwoWaySyncEnabled', false);

        return $isTwoWaySyncEnabled;
    }
}
