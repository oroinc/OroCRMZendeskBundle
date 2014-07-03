<?php

namespace OroCRM\Bundle\ZendeskBundle\Model\SyncHelper;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;

class TicketCommentSyncHelper extends AbstractSyncHelper
{
    /**
     * {@inheritdoc}
     */
    public function findEntity($ticketComment, Channel $channel)
    {
        return $this->zendeskProvider->getTicketComment($ticketComment, $channel);
    }

    /**
     * {@inheritdoc}
     */
    public function syncEntities($targetTicketComment, $sourceTicketComment)
    {
        $this->syncProperties(
            $targetTicketComment,
            $sourceTicketComment,
            ['id', 'originId', 'ticket', 'relatedComment', 'updatedAtLocked', 'createdAt', 'updatedAt']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function refreshEntity($ticketComment, Channel $channel)
    {
        $this->refreshTicket($ticketComment, $channel);
    }

    /**
     * @param TicketComment $entity
     * @param Channel $channel
     */
    protected function refreshTicket(TicketComment $entity, Channel $channel)
    {
        if ($entity->getTicket()) {
            $entity->setTicket($this->zendeskProvider->getTicket($entity->getTicket(), $channel));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function syncRelatedEntities($entity, Channel $channel)
    {
        $this->channel = $channel;

        $this->syncAuthor($entity);
        $this->syncRelatedComment($entity);

        $this->channel = null;
    }

    /**
     * @param TicketComment $entity
     */
    protected function syncAuthor(TicketComment $entity)
    {
        if ($entity->getAuthor()) {
            $entity->setAuthor($this->zendeskProvider->getUser($entity->getAuthor(), $this->channel, true));
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
            $defaultUser = $this->oroProvider->getDefaultUser($this->channel);
            $caseComment->setOwner($defaultUser);
        }
    }
}
