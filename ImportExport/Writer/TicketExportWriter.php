<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Writer;

use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\TicketSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\TicketCommentSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\UserSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketCommentConnector;

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
     * @var UserSyncHelper
     */
    protected $userSyncHelper;

    /**
     * @var Ticket[]
     */
    protected $newTickets = [];

    /**
     * @param SyncScheduler $syncScheduler
     * @param TicketSyncHelper $ticketHelper
     * @param TicketCommentSyncHelper $ticketCommentHelper
     * @param UserSyncHelper $userSyncHelper
     */
    public function __construct(
        SyncScheduler $syncScheduler,
        TicketSyncHelper $ticketHelper,
        TicketCommentSyncHelper $ticketCommentHelper,
        UserSyncHelper $userSyncHelper
    ) {
        $this->syncScheduler = $syncScheduler;
        $this->ticketHelper = $ticketHelper;
        $this->ticketCommentHelper = $ticketCommentHelper;
        $this->userSyncHelper = $userSyncHelper;
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

        $data = $this->transport->updateTicket(
            $this->serializer->serialize($ticket, null)
        );

        $updatedTicket = $this->serializer->deserialize(
            $data,
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
            null,
            ['channel' => $ticket->getChannel()->getId()]
        );

        $this->getLogger()->info('Update ticket by response data.');
        $this->ticketHelper->refreshEntity($updatedTicket, $ticket->getChannel());
        $this->ticketHelper->copyEntityProperties($ticket, $updatedTicket);

        $this->getLogger()->info('Update related case.');
        $this->ticketHelper->syncRelatedEntities($ticket, $ticket->getChannel());

        $this->getContext()->incrementUpdateCount();
    }

    /**
     * @param Ticket $ticket
     * @return object
     */
    protected function createTicket(Ticket $ticket)
    {
        $this->getLogger()->info("Create ticket in Zendesk API.");

        $data = $this->transport->createTicket($this->serializer->serialize($ticket, null));

        $createdTicket = $this->serializer->deserialize(
            $data['ticket'],
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
            null,
            ['channel' => $ticket->getChannel()->getId()]
        );

        $this->getLogger()->info("Created ticket [origin_id={$createdTicket->getOriginId()}].");

        $this->getLogger()->info('Update ticket by response data.');
        $this->ticketHelper->refreshEntity($createdTicket, $ticket->getChannel());
        $this->ticketHelper->copyEntityProperties($ticket, $createdTicket);

        $this->getLogger()->info('Update related case.');
        $this->ticketHelper->syncRelatedEntities($ticket, $ticket->getChannel());

        $this->getContext()->incrementUpdateCount();

        if ($data['comment']) {
            $createdComment = $this->serializer->deserialize(
                $data['comment'],
                'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment',
                null,
                ['channel' => $ticket->getChannel()->getId()]
            );
            $createdComment->setChannel($ticket->getChannel());

            $this->getLogger()->info("Created ticket comment [origin_id={$createdComment->getOriginId()}].");

            $this->ticketCommentHelper->refreshEntity($createdComment, $ticket->getChannel());
            $ticket->addComment($createdComment);

            $this->entityManager->persist($createdComment);
            $this->getContext()->incrementAddCount();

            $this->getLogger()->info('Update related case comment.');
            $this->ticketCommentHelper->syncRelatedEntities($createdComment, $ticket->getChannel());
            $this->getContext()->incrementAddCount();
        }
    }

    /**
     * @param Ticket $ticket
     */
    protected function syncTicketRelations(Ticket $ticket)
    {
        if ($ticket->getRequester() && !$ticket->getRequester()->getOriginId()) {
            $this->getLogger()->info('Try to sync ticket requester.');
            $this->createUser($ticket->getRequester(), $ticket->getChannel());
        }

        if ($ticket->getAssignee() && !$ticket->getAssignee()->getOriginId()) {
            $this->getLogger()->info('Try to sync ticket assignee.');
            $this->createUser($ticket->getAssignee(), $ticket->getChannel());
        }

        if ($ticket->getSubmitter() && !$ticket->getSubmitter()->getOriginId()) {
            $this->getLogger()->info('Try to sync ticket sumitter.');
            $this->createUser($ticket->getSubmitter(), $ticket->getChannel());
        }
    }

    /**
     * @param User $user
     * @param Channel $channel
     */
    protected function createUser(User $user, Channel $channel)
    {
        $this->getLogger()->info(sprintf('Create user in Zendesk API [id=%d].', $user->getId()));

        if (!$user->isRoleEqual(UserRole::ROLE_END_USER)) {
            $this->getLogger()->error("Not allowed to create user [role={$user->getRole()}] in Zendesk.");
            return;
        }

        try {
            $data = $this->transport->createUser($this->serializer->serialize($user, null));

            $createdUser = $this->serializer->deserialize(
                $data,
                'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
                null,
                ['channel' => $channel->getId()]
            );

            $this->getLogger()->info("Created user [origin_id={$createdUser->getOriginId()}].");
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                "Can't create user [id={$user->getId()}] in Zendesk API.",
                ['exception' => $exception]
            );
            return;
        }

        $user->setChannel($channel);
        $this->entityManager->persist($user);

        $this->userSyncHelper->refreshEntity($createdUser, $channel);
        $this->userSyncHelper->copyEntityProperties($user, $createdUser);
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
        foreach ($tickets as $ticket) {
            $case = $ticket->getRelatedCase();
            if (!$case) {
                continue;
            }

            $this->entityManager->refresh($case);

            /** @var TicketComment $comment */
            foreach ($ticket->getComments() as $comment) {
                if (!$comment->getOriginId()) {
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

                $this->entityManager->persist($ticketComment);
            }
        }

        if (!$ticketComments) {
            return;
        }

        $this->entityManager->flush($ticketComments);

        foreach ($ticketComments as $ticketComment) {
            $ids[] = $ticketComment->getId();
        }

        $this->getLogger()->info(
            sprintf('Schedule job to sync existing ticket comments [ids=%s].', implode(', ', $ids))
        );

        $this->syncScheduler->schedule(
            $this->getChannel(),
            TicketCommentConnector::TYPE,
            ['id' => $ids]
        );
    }
}
