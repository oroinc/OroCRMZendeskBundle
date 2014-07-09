<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Writer;

use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\TicketCommentSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;

class TicketCommentExportWriter extends AbstractExportWriter
{
    /**
     * @var TicketCommentSyncHelper
     */
    protected $ticketCommentHelper;

    /**
     * @param TicketCommentSyncHelper $ticketCommentHelper
     */
    public function __construct(TicketCommentSyncHelper $ticketCommentHelper)
    {
        $this->ticketCommentHelper = $ticketCommentHelper;
    }

    /**
     * @param TicketComment $ticketComment
     */
    protected function writeItem($ticketComment)
    {
        $this->getLogger()->setMessagePrefix("Zendesk Ticket Comment [id={$ticketComment->getId()}]: ");

        $this->syncTicketCommentRelations($ticketComment);
        if (!$ticketComment->getOriginId()) {
            $this->createTicketComment($ticketComment);
        } else {
            $this->getLogger()->error(
                sprintf(
                    'Can\'t update ticket comment [id={%d}][origin_id=%d] in Zendesk API. Operation is prohibited.',
                    $ticketComment->getId(),
                    $ticketComment->getOriginId()
                )
            );
            $this->getContext()->incrementErrorEntriesCount();
            return;
        }

        $this->getLogger()->setMessagePrefix('');
    }

    /**
     * @param TicketComment $ticketComment
     */
    protected function syncTicketCommentRelations(TicketComment $ticketComment)
    {
        if ($ticketComment->getAuthor() && !$ticketComment->getAuthor()->getOriginId()) {
            $this->getLogger()->info('Try to sync ticket comment author.');
            $this->createUser($ticketComment->getAuthor());
            if (!$ticketComment->getAuthor()->getOriginId()) {
                $this->getLogger()->warning('Set default user as author.');
                $ticketComment->setAuthor($this->userHelper->findDefaultUser($this->getChannel()));
            }
        }
    }

    /**
     * @param TicketComment $ticketComment
     */
    protected function createTicketComment(TicketComment $ticketComment)
    {
        $this->getLogger()->info("Create ticket comment in Zendesk API.");

        $createdTicketComment = $this->transport->addTicketComment($ticketComment);

        $this->getLogger()->info("Created ticket comment [origin_id={$createdTicketComment->getOriginId()}].");

        $this->getLogger()->info('Update ticket comment by response data.');
        $this->ticketCommentHelper->refreshTicketComment($createdTicketComment, $this->getChannel());
        $this->ticketCommentHelper->copyEntityProperties($ticketComment, $createdTicketComment);

        $this->getLogger()->info('Update related comment.');
        $this->ticketCommentHelper->syncRelatedEntities($ticketComment, $this->getChannel());

        $this->getContext()->incrementUpdateCount();
    }
}
