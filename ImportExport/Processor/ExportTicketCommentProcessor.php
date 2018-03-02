<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Processor;

use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\User;

class ExportTicketCommentProcessor extends AbstractExportProcessor
{
    /**
     * {@inheritdoc}
     */
    public function process($ticketComment)
    {
        if (!$ticketComment instanceof TicketComment) {
            throw new InvalidArgumentException(
                sprintf(
                    'Imported entity must be instance of Oro\Bundle\ZendeskBundle\Entity\TicketComment, %s given.',
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
        $isTicketValid = $this->isTicketValid($ticketComment);
        if (!$isTicketValid) {
            $this->getContext()->incrementErrorEntriesCount();
            return null;
        }

        $comment = $ticketComment->getRelatedComment();

        if (!$comment) {
            $this->getLogger()->error('Comment not found.');
            $this->getContext()->incrementErrorEntriesCount();
            return null;
        }

        if ($ticketComment->getOriginId()) {
            $this->getLogger()->error('Only new comments can be synced.');
            $this->getContext()->incrementErrorEntriesCount();
            return null;
        }

        $author = $this->getAuthor($comment);

        if (!$author) {
            $this->getLogger()->error('Author and default user not found.');
            $this->getContext()->incrementErrorEntriesCount();
            return null;
        }

        $ticketComment->setAuthor($author);

        $ticketComment->setBody($comment->getMessage());
        $ticketComment->setPublic($comment->isPublic());

        return $ticketComment;
    }

    /**
     * @param CaseComment $comment
     * @return null|User
     */
    protected function getAuthor(CaseComment $comment)
    {
        $owner = $comment->getOwner();
        $author = null;
        if ($comment->getContact()) {
            $author = $this->zendeskProvider->getUserByContact($comment->getContact(), $this->getChannel());
        }
        if (!$author) {
            $author = $this->zendeskProvider->getUserByOroUser($owner, $this->getChannel(), true);
        }

        return $author;
    }

    /**
     * @param TicketComment $ticketComment
     * @return bool
     */
    protected function isTicketValid(TicketComment $ticketComment)
    {
        $ticket = $ticketComment->getTicket();

        if (null === $ticket) {
            $this->getLogger()->error(sprintf('Ticket for comment "%s" not found', $ticketComment->getBody()));
            return false;
        }

        if ($ticket->getStatus()->getName() === TicketStatus::STATUS_CLOSED) {
            $this->getLogger()->error(
                sprintf(
                    'Comment for ticket "%s" not synced because ticket is closed.',
                    $ticket->getSubject()
                )
            );
            return false;
        }

        return true;
    }
}
