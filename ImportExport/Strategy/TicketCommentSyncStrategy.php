<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\CaseBundle\Model\CaseEntityManager;

use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\OroEntityProvider;

class TicketCommentSyncStrategy extends AbstractSyncStrategy
{
    /**
     * @var CaseEntityManager
     */
    protected $caseEntityManager;

    /**
     * @var OroEntityProvider
     */
    protected $oroEntityProvider;

    /**
     * @param CaseEntityManager $caseEntityManager
     * @param OroEntityProvider $oroEntityProvider
     */
    public function __construct(
        CaseEntityManager $caseEntityManager,
        OroEntityProvider $oroEntityProvider
    ) {
        $this->caseEntityManager = $caseEntityManager;
        $this->oroEntityProvider = $oroEntityProvider;
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

        $ticketId = $this->getContext()->getOption('ticketId');
        if (!$ticketId) {
            throw new InvalidArgumentException('Option "ticketId" must be set.');
        }

        $this->getLogger()->setMessagePrefix("Zendesk Ticket Comment [id={$entity->getOriginId()}]: ");

        $ticket = $this->zendeskProvider->getTicketByOriginId($ticketId);
        if (!$ticket) {
            $message = "Ticket not found [id={$entity->getTicket()->getOriginId()}].";
            $this->getContext()->addError($message);
            $this->getLogger()->error($message);
            $this->getContext()->incrementErrorEntriesCount();
            return null;
        } else {
            $entity->setTicket($ticket);
        }

        $existingComment = $this->zendeskProvider->getTicketComment($entity);

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
            $entity->setAuthor($this->zendeskProvider->getUser($entity->getAuthor()));
        }
        $entity->setAuthor(null);
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
        $caseComment->setCreatedAt($ticketComment->getCreatedAt());
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
            $caseComment->setOwner($this->oroEntityProvider->getDefaultUser());
        }
    }
}
