<?php

namespace OroCRM\Bundle\ZendeskBundle\Model\SyncHelper;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\CaseBundle\Model\CaseEntityManager;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Model\EntityMapper;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;

class TicketSyncHelper extends AbstractSyncHelper
{
    /**
     * @param ZendeskEntityProvider $zendeskProvider
     * @param OroEntityProvider $oroEntityProvider
     * @param CaseEntityManager $caseEntityManager
     * @param EntityMapper $entityMapper
     */
    public function __construct(
        ZendeskEntityProvider $zendeskProvider,
        OroEntityProvider $oroEntityProvider,
        CaseEntityManager $caseEntityManager,
        EntityMapper $entityMapper
    ) {
        parent::__construct($zendeskProvider, $oroEntityProvider, $caseEntityManager);
        $this->entityMapper = $entityMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function findEntity($ticket, Channel $channel)
    {
        return $this->zendeskProvider->getTicket($ticket, $channel);
    }

    /**
     * {@inheritdoc}
     */
    public function syncEntities($targetTicket, $sourceTicket)
    {
        $this->syncProperties(
            $targetTicket,
            $sourceTicket,
            ['id', 'relatedCase', 'updatedAtLocked', 'createdAt', 'updatedAt']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function refreshEntity($ticket, Channel $channel)
    {
        $this->channel = $channel;

        $this->refreshDictionaryField($ticket, 'status', 'ticketStatus', true);
        $this->refreshDictionaryField($ticket, 'priority', 'ticketPriority');
        $this->refreshDictionaryField($ticket, 'type', 'ticketType');

        $this->refreshProblem($ticket);
        $this->refreshCollaborators($ticket);
        $this->refreshRequester($ticket);
        $this->refreshSubmitter($ticket);
        $this->refreshAssignee($ticket);

        $this->channel = null;
    }

    /**
     * @param Ticket $entity
     */
    protected function refreshProblem(Ticket $entity)
    {
        if ($entity->getProblem()) {
            $entity->setProblem($this->zendeskProvider->getTicket($entity->getProblem(), $this->channel));
        }
    }

    /**
     * @param Ticket $entity
     */
    protected function refreshCollaborators(Ticket $entity)
    {
        $collaborators = $entity->getCollaborators()->getValues();
        $entity->getCollaborators()->clear();
        foreach ($collaborators as $value) {
            $user = $this->zendeskProvider->getUser($value, $this->channel);
            if ($user) {
                $entity->addCollaborator($user);
            }
        }
    }

    /**
     * @param Ticket $entity
     */
    protected function refreshRequester(Ticket $entity)
    {
        if ($entity->getRequester()) {
            $entity->setRequester($this->zendeskProvider->getUser($entity->getRequester(), $this->channel, true));
        }
    }

    /**
     * @param Ticket $entity
     */
    protected function refreshSubmitter(Ticket $entity)
    {
        if ($entity->getSubmitter()) {
            $entity->setSubmitter($this->zendeskProvider->getUser($entity->getSubmitter(), $this->channel, true));
        }
    }

    /**
     * @param Ticket $entity
     */
    protected function refreshAssignee(Ticket $entity)
    {
        if ($entity->getAssignee()) {
            $entity->setAssignee($this->zendeskProvider->getUser($entity->getAssignee(), $this->channel, true));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function syncRelatedEntities($entity, Channel $channel)
    {
        $this->channel = $channel;

        $this->syncRelatedCase($entity, $channel);

        $this->channel = $channel;
    }

    /**
     * @param Ticket $entity
     */
    protected function syncRelatedCase(Ticket $entity)
    {
        $relatedCase = $entity->getRelatedCase();
        if (!$relatedCase) {
            $relatedCase = $this->caseEntityManager->createCase();
            $entity->setRelatedCase($relatedCase);
        }
        $this->syncCaseFields($relatedCase, $entity);
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $entity
     */
    protected function syncCaseFields(CaseEntity $case, Ticket $entity)
    {
        $case->setSubject($entity->getSubject());
        $case->setDescription($entity->getDescription());
        $this->syncCaseOwner($case, $entity);
        $this->syncCaseAssignedTo($case, $entity);
        $this->syncCaseRelatedContact($case, $entity);
        $this->syncCaseStatus($case, $entity);
        $this->syncCasePriority($case, $entity);
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $ticket
     */
    protected function syncCaseOwner(CaseEntity $case, Ticket $ticket)
    {
        if ($ticket->getSubmitter() && $ticket->getSubmitter()->getRelatedUser()) {
            $owner = $ticket->getSubmitter()->getRelatedUser();
        } elseif ($ticket->getRequester() && $ticket->getRequester()->getRelatedUser()) {
            $owner = $ticket->getRequester()->getRelatedUser();
        } elseif ($ticket->getAssignee() && $ticket->getAssignee()->getRelatedUser()) {
            $owner = $ticket->getAssignee()->getRelatedUser();
        } else {
            $owner = $this->oroProvider->getDefaultUser($this->channel);
        }
        $case->setOwner($owner);
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $ticket
     */
    protected function syncCaseAssignedTo(CaseEntity $case, Ticket $ticket)
    {
        if ($ticket->getAssignee() && $ticket->getAssignee()->getRelatedUser()) {
            $assignedTo = $ticket->getAssignee()->getRelatedUser();
        } elseif ($ticket->getSubmitter() && $ticket->getSubmitter()->getRelatedUser()) {
            $assignedTo = $ticket->getSubmitter()->getRelatedUser();
        } elseif ($ticket->getRequester() && $ticket->getRequester()->getRelatedUser()) {
            $assignedTo = $ticket->getRequester()->getRelatedUser();
        } else {
            $assignedTo = $this->oroProvider->getDefaultUser($this->channel);
        }
        $case->setAssignedTo($assignedTo);
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $ticket
     */
    protected function syncCaseRelatedContact(CaseEntity $case, Ticket $ticket)
    {
        if ($ticket->getSubmitter() && $ticket->getSubmitter()->getRelatedContact()) {
            $case->setRelatedContact($ticket->getSubmitter()->getRelatedContact());
        } elseif ($ticket->getRequester() && $ticket->getRequester()->getRelatedContact()) {
            $case->setRelatedContact($ticket->getRequester()->getRelatedContact());
        } elseif ($ticket->getAssignee() && $ticket->getAssignee()->getRelatedContact()) {
            $case->setRelatedContact($ticket->getAssignee()->getRelatedContact());
        }
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $ticket
     */
    protected function syncCaseStatus(CaseEntity $case, Ticket $ticket)
    {
        if ($ticket->getStatus()) {
            $name = $ticket->getStatus()->getName();
            $value = $this->entityMapper->getCaseStatus($name, $this->channel);
            if (!$value) {
                $this->getLogger()->error("Can't convert Zendesk status [name=$name]");
            } else {
                $case->setStatus($value);
            }
        }
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $ticket
     */
    protected function syncCasePriority(CaseEntity $case, Ticket $ticket)
    {
        if ($ticket->getPriority()) {
            $name = $ticket->getPriority()->getName();
            $value = $this->entityMapper->getCasePriority($name, $this->channel);
            if (!$value) {
                $this->getLogger()->error("Can't convert Zendesk priority [name=$name]");
            } else {
                $case->setPriority($value);
            }
        }
    }
}
