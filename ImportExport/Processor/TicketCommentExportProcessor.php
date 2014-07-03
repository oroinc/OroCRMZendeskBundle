<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Processor;

use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

class TicketCommentExportProcessor extends AbstractExportProcessor
{
    /**
     * {@inheritdoc}
     */
    public function process($ticketComment)
    {
        if (!$ticketComment instanceof TicketComment) {
            throw new InvalidArgumentException(
                sprintf(
                    'Imported entity must be instance of OroCRM\Bundle\ZendeskBundle\Entity\TicketComment, %s given.',
                    is_object($ticketComment) ? get_class($ticketComment) : gettype($ticketComment)
                )
            );
        }

        $this->getLogger()->setMessagePrefix("Zendesk Ticket Comment [id={$ticketComment->getId()}]: ");

        return $this->syncComment($ticketComment);
    }

    /**
     * @param TicketComment $ticketComment
     * @return null|TicketComment
     * @throws \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     */
    protected function syncComment(TicketComment $ticketComment)
    {
        $comment = $ticketComment->getRelatedComment();

        if (!$comment) {
            throw new InvalidArgumentException("Ticket Comment must have related Comment");
        }

        if ($ticketComment->getOriginId()) {
            $this->getLogger()->error('Only new comments can be synced.');
            $this->getContext()->incrementErrorEntriesCount();
            return null;
        }

        $body = $comment->getMessage();
        $comment->getCreatedAt();
        $owner = $comment->getOwner();
        $author = $this->zendeskProvider->getUserByOroUser($owner, $this->getChannel(), true);
        $ticketComment->setBody($body);
        $ticketComment->setPublic($comment->isPublic());
        $ticketComment->setAuthor($author);

        return $ticketComment;
    }
}
