<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Writer;

use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Model\SyncHelper\TicketCommentSyncHelper;
use Oro\Bundle\ZendeskBundle\Model\SyncHelper\TicketSyncHelper;
use Oro\Bundle\ZendeskBundle\Provider\TicketCommentConnector;

/**
 * Export writer for tickets.
 */
class TicketExportWriter extends AbstractExportWriter
{
    /**
     * @var SyncScheduler
     */
    protected $syncScheduler;

    /**
     * @var TicketSyncHelper
     */
    protected $ticketHelper;

    /**
     * @var TicketCommentSyncHelper
     */
    protected $ticketCommentHelper;

    /**
     * @var Ticket[]
     */
    protected $newTickets = [];

    public function __construct(
        SyncScheduler $syncScheduler,
        TicketSyncHelper $ticketHelper,
        TicketCommentSyncHelper $ticketCommentHelper
    ) {
        $this->syncScheduler = $syncScheduler;
        $this->ticketHelper = $ticketHelper;
        $this->ticketCommentHelper = $ticketCommentHelper;
    }

    /**
     * @param Ticket $ticket
     */
    protected function writeItem($ticket)
    {
        $this->getLogger()->setMessagePrefix("Zendesk Ticket [id={$ticket->getId()}]: ");

        $this->syncTicketRelations($ticket);

        if ($ticket->getOriginId()) {
            $this->updateTicket($ticket);
        } else {
            $this->createTicket($ticket);
            $this->newTickets[] = $ticket;
        }

        $this->getLogger()->setMessagePrefix('');
    }

    /**
     * @param Ticket $ticket
     * @return object
     */
    protected function updateTicket(Ticket $ticket)
    {
        $this->getLogger()->info("Update ticket in Zendesk API [origin_id={$ticket->getOriginId()}].");

        $updatedTicket = $this->transport->updateTicket($ticket);

        $this->getLogger()->info('Update ticket by response data.');
        $this->ticketHelper->refreshTicket($updatedTicket, $this->getChannel());
        $changes = $this->ticketHelper->calculateTicketsChanges($ticket, $updatedTicket);
        $changes->apply();

        $this->getLogger()->info('Update related case.');
        $changes = $this->ticketHelper->calculateRelatedCaseChanges($ticket, $this->getChannel());
        $changes->apply();

        $this->getContext()->incrementUpdateCount();
    }

    /**
     * @param Ticket $ticket
     * @return object
     */
    protected function createTicket(Ticket $ticket)
    {
        $this->getLogger()->info("Create ticket in Zendesk API.");

        $data = $this->transport->createTicket($ticket);

        /** @var Ticket $createdTicket */
        $createdTicket = $data['ticket'];

        $this->getLogger()->info("Created ticket [origin_id={$createdTicket->getOriginId()}].");

        $this->getLogger()->info('Update ticket by response data.');
        $this->ticketHelper->refreshTicket($createdTicket, $this->getChannel());
        $changes = $this->ticketHelper->calculateTicketsChanges($ticket, $createdTicket);
        $changes->apply();

        $this->getLogger()->info('Update related case.');
        $changes = $this->ticketHelper->calculateRelatedCaseChanges($ticket, $this->getChannel());
        $changes->apply();

        $this->getContext()->incrementUpdateCount();

        if ($data['comment']) {
            /** @var TicketComment $createdComment */
            $createdComment = $data['comment'];
            $createdComment->setChannel($this->getChannel());

            $this->getLogger()->info("Created ticket comment [origin_id={$createdComment->getOriginId()}].");

            $this->ticketCommentHelper->refreshTicketComment($createdComment, $this->getChannel());
            $ticket->addComment($createdComment);

            $this->registry->getManager()->persist($createdComment);
            $this->getContext()->incrementAddCount();

            $this->getLogger()->info('Update related case comment.');
            $this->ticketCommentHelper->syncRelatedEntities($createdComment, $this->getChannel());
            $this->getContext()->incrementAddCount();
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function syncTicketRelations(Ticket $ticket)
    {
        if ($ticket->getRequester() && !$ticket->getRequester()->getOriginId()) {
            $this->getLogger()->info('Try to sync ticket requester.');
            $this->createUser($ticket->getRequester());
            if (!$ticket->getRequester()->getOriginId()) {
                $this->getLogger()->warning('Set default user as requester.');
                $ticket->setRequester($this->userHelper->findDefaultUser($this->getChannel()));
            }
        }

        if ($ticket->getAssignee() && !$ticket->getAssignee()->getOriginId()) {
            $this->getLogger()->info('Try to sync ticket assignee.');
            $this->createUser($ticket->getAssignee());
            if (!$ticket->getAssignee()->getOriginId()) {
                $this->getLogger()->warning('Set default user as assignee.');
                $ticket->setAssignee($this->userHelper->findDefaultUser($this->getChannel()));
            }
        }

        if ($ticket->getSubmitter() && !$ticket->getSubmitter()->getOriginId()) {
            $this->getLogger()->info('Try to sync ticket submitter.');
            $this->createUser($ticket->getSubmitter());
            if (!$ticket->getSubmitter()->getOriginId()) {
                $this->getLogger()->warning('Set default user as submitter.');
                $ticket->setSubmitter($this->userHelper->findDefaultUser($this->getChannel()));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function postFlush()
    {
        $this->createNewTicketComments($this->newTickets);
        $this->newTickets = [];
    }

    /**
     * When existing case is synced with Zendesk at first time, we need to create corresponding ticket comments for
     * each of it comment and schedule a job for syncing them with Zendesk API.
     *
     * @param Ticket[] $tickets
     */
    protected function createNewTicketComments(array $tickets)
    {
        /** @var TicketComment[] $ticketComments */
        $ticketComments = [];
        $em = $this->registry->getManager();
        foreach ($tickets as $ticket) {
            $case = $ticket->getRelatedCase();
            if (!$case) {
                continue;
            }

            $em->refresh($case);

            /** @var TicketComment $comment */
            foreach ($ticket->getComments() as $comment) {
                if ($comment->getOriginId()) {
                    continue;
                }
                $ticketComments[] = $comment;
            }

            /** @var CaseComment $comment */
            foreach ($case->getComments() as $comment) {
                if ($this->ticketCommentHelper->findByCaseComment($comment)) {
                    continue;
                }
                $this->getLogger()->info("Create ticket comment for case comment [id={$comment->getId()}].");
                $ticketComment = new TicketComment();
                $ticket->addComment($ticketComment);
                $ticketComment->setRelatedComment($comment);
                $ticketComments[] = $ticketComment;

                $em->persist($ticketComment);
            }
        }

        if (!$ticketComments) {
            return;
        }

        $em->flush($ticketComments);

        foreach ($ticketComments as $ticketComment) {
            $ids[] = $ticketComment->getId();
        }

        $this->getLogger()->info(
            sprintf('Schedule job to sync existing ticket comments [ids=%s].', implode(', ', $ids))
        );

        $this->syncScheduler->schedule(
            $this->getChannel()->getId(),
            TicketCommentConnector::TYPE,
            ['id' => $ids]
        );
    }

    /**
     * @return string
     */
    protected function getSyncPriority()
    {
        return $this->getChannel()->getSynchronizationSettings()->offsetGetOr('syncPriority');
    }
}
