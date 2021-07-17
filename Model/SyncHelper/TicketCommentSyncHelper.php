<?php

namespace Oro\Bundle\ZendeskBundle\Model\SyncHelper;

use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;

class TicketCommentSyncHelper extends AbstractSyncHelper
{
    /**
     * @param TicketComment $ticketComment
     * @param Channel $channel
     * @return null|TicketComment
     */
    public function findTicketComment($ticketComment, Channel $channel)
    {
        return $this->zendeskProvider->getTicketComment($ticketComment, $channel);
    }

    /**
     * @param CaseComment $caseComment
     * @return null|TicketComment
     */
    public function findByCaseComment(CaseComment $caseComment)
    {
        return $this->zendeskProvider->getTicketCommentByCaseComment($caseComment);
    }

    /**
     * @param TicketComment $targetTicketComment
     * @param TicketComment $sourceTicketComment
     */
    public function copyEntityProperties($targetTicketComment, $sourceTicketComment)
    {
        $this->syncProperties(
            $targetTicketComment,
            $sourceTicketComment,
            ['id', 'channel', 'ticket', 'relatedComment', 'updatedAtLocked', 'createdAt', 'updatedAt']
        );
    }

    /**
     * @param TicketComment $ticketComment
     * @param Channel $channel
     */
    public function refreshTicketComment($ticketComment, Channel $channel)
    {
        $this->refreshChannel($ticketComment, $channel);
        $this->refreshTicket($ticketComment, $channel);
    }

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

    protected function syncAuthor(TicketComment $entity)
    {
        if ($entity->getAuthor()) {
            $entity->setAuthor($this->zendeskProvider->getUser($entity->getAuthor(), $this->channel, true));
        } else {
            $entity->setAuthor(null);
        }
    }

    protected function syncRelatedComment(TicketComment $entity)
    {
        $relatedComment = $entity->getRelatedComment();
        if (!$relatedComment) {
            $relatedComment = $this->caseEntityManager->createComment($entity->getTicket()->getRelatedCase());
            $entity->setRelatedComment($relatedComment);
        }
        $this->syncCaseCommentFields($relatedComment, $entity);
    }

    protected function syncCaseCommentFields(CaseComment $caseComment, TicketComment $ticketComment)
    {
        $caseComment->setPublic($ticketComment->getPublic());
        if ($ticketComment->getOriginCreatedAt()) {
            $caseComment->setCreatedAt($ticketComment->getOriginCreatedAt());
        }
        $caseComment->setMessage($ticketComment->getBody());
        $this->syncCaseCommentOwnerAndUser($caseComment, $ticketComment);
    }

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

        // set case organization
        $caseComment->setOrganization($this->getRefreshedChannelOrganization($this->channel));
    }
}
