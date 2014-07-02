<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\CaseBundle\Model\CaseEntityManager;

use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;

class TicketCommentSyncStrategy extends AbstractSyncStrategy
{
    /**
     * @var CaseEntityManager
     */
    protected $caseEntityManager;

    /**
     * @param CaseEntityManager $caseEntityManager
     */
    public function __construct(
        CaseEntityManager $caseEntityManager
    ) {
        $this->caseEntityManager = $caseEntityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof TicketComment) {
            throw new InvalidArgumentException(
                sprintf(
                    'Imported entity must be instance of OroCRM\Bundle\ZendeskBundle\Entity\TicketComment, %s given.',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        if (!$this->validateOriginId($entity)) {
            return null;
        }

        $this->getLogger()->setMessagePrefix("Zendesk Ticket Comment [id={$entity->getOriginId()}]: ");

        if ($entity->getTicket()) {
            $ticket = $this->zendeskProvider->getTicket($entity->getTicket(), $this->getChannel());
            if (!$ticket) {
                $this->addError("Ticket not found [id={$entity->getTicket()->getOriginId()}].");
                return null;
            }
            $entity->setTicket($ticket);
        } else {
            $this->addError("Comment Ticket required.");
            return null;
        }

        $existingComment = $this->zendeskProvider->getTicketComment($entity, $this->getChannel());

        if ($existingComment) {
            $this->syncProperties(
                $existingComment,
                $entity,
                array('id', 'originId', 'ticket', 'relatedComment', 'updatedAtLocked', 'createdAt', 'updatedAt')
            );
            $entity = $existingComment;

            $this->getLogger()->debug("Update found Zendesk ticket comment.");
            $this->getContext()->incrementUpdateCount();
        } else {
            $this->getLogger()->debug("Add new Zendesk ticket comment.");
            $this->getContext()->incrementAddCount();
        }

        $this->syncRelatedEntities($entity);

        return $entity;
    }

    /**
     * @param TicketComment $entity
     */
    protected function syncRelatedEntities(TicketComment $entity)
    {
        $this->syncAuthor($entity);
        $this->syncRelatedComment($entity);
    }

    /**
     * @param TicketComment $entity
     */
    protected function syncAuthor(TicketComment $entity)
    {
        if ($entity->getAuthor()) {
            $entity->setAuthor($this->zendeskProvider->getUser($entity->getAuthor(), $this->getChannel()));
        } else {
            $entity->setAuthor(null);
        }
    }

    /**
     * @param TicketComment $entity
     */
    protected function syncRelatedComment(TicketComment $entity)
    {
        $relatedComment = $entity->getRelatedComment();
        if (!$relatedComment) {
            $relatedComment = $this->caseEntityManager->createComment($entity->getTicket()->getRelatedCase());
            $entity->setRelatedComment($relatedComment);
        }
        $this->syncCaseCommentFields($relatedComment, $entity);
    }

    /**
     * @param CaseComment $caseComment
     * @param TicketComment $ticketComment
     */
    protected function syncCaseCommentFields(CaseComment $caseComment, TicketComment $ticketComment)
    {
        $caseComment->setPublic($ticketComment->getPublic());
        $caseComment->setCreatedAt($ticketComment->getOriginCreatedAt());
        $caseComment->setMessage($ticketComment->getBody());
        $this->syncCaseCommentOwnerAndUser($caseComment, $ticketComment);
    }

    /**
     * @param CaseComment $caseComment
     * @param TicketComment $ticketComment
     */
    protected function syncCaseCommentOwnerAndUser(CaseComment $caseComment, TicketComment $ticketComment)
    {
        if ($ticketComment->getAuthor()) {
            if ($ticketComment->getAuthor()->getRelatedContact()) {
                $caseComment->setContact($ticketComment->getAuthor()->getRelatedContact());
            }
            if ($ticketComment->getAuthor()->getRelatedUser()) {
                $caseComment->setOwner($ticketComment->getAuthor()->getRelatedUser());
            }
        }

        if (!$caseComment->getOwner()) {
            $defaultUser = $this->oroEntityProvider->getDefaultUser($this->getChannel());
            $caseComment->setOwner($defaultUser);
        }
    }

    /**
     * @param $message
     */
    protected function addError($message)
    {
        $this->getContext()
            ->addError($message);
        $this->getLogger()
            ->error($message);
        $this->getContext()
            ->incrementErrorEntriesCount();
    }
}
