<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\CaseBundle\Model\CaseEntityManager;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\OroEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Model\EntityMapper;

class TicketSyncStrategy extends AbstractSyncStrategy
{
    const COMMENT_TICKETS = 'comment_tickets';

    /**
     * @var CaseEntityManager
     */
    protected $caseEntityManager;

    /**
     * @var EntityMapper
     */
    protected $entityMapper;

    /**
     * @var OroEntityProvider
     */
    protected $oroEntityProvider;

    /**
     * @param CaseEntityManager $caseEntityManager
     * @param EntityMapper $entityMapper
     * @param OroEntityProvider $oroEntityProvider
     */
    public function __construct(
        CaseEntityManager $caseEntityManager,
        EntityMapper $entityMapper,
        OroEntityProvider $oroEntityProvider
    ) {
        $this->caseEntityManager = $caseEntityManager;
        $this->entityMapper = $entityMapper;
        $this->oroEntityProvider = $oroEntityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof Ticket) {
            throw new InvalidArgumentException(
                sprintf(
                    'Imported entity must be instance of OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket, %s given.',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }
        if (!$this->validateOriginId($entity)) {
            return null;
        }
        $this->getLogger()->setMessagePrefix("Zendesk Ticket [id={$entity->getOriginId()}]: ");

        $this->refreshDictionaryField($entity, 'status', 'ticketStatus', true);
        $this->refreshDictionaryField($entity, 'priority', 'ticketPriority');
        $this->refreshDictionaryField($entity, 'type', 'ticketType');

        $existingTicket = $this->zendeskProvider->getTicket($entity);
        if ($existingTicket) {
            if (!$this->needSync($entity)) {
                return null;
            }

            $this->syncProperties(
                $existingTicket,
                $entity,
                array('relatedCase', 'id', 'updatedAtLocked', 'createdAt', 'updatedAt')
            );
            $entity = $existingTicket;

            $this->getLogger()->debug("Update found Zendesk ticket.");
            $this->getContext()->incrementUpdateCount();
        } else {
            $this->getLogger()->debug("Add new Zendesk ticket.");
            $this->getContext()->incrementAddCount();
        }

        $this->syncRelatedEntities($entity);

        $this->saveCommentTickets($entity);

        return $entity;
    }

    /**
     * @param Ticket $entity
     */
    protected function saveCommentTickets(Ticket $entity)
    {
        $value = (array)$this->getContext()->getValue(self::COMMENT_TICKETS);
        $value[] = $entity->getOriginId();
        $this->getContext()->setValue(self::COMMENT_TICKETS, $value);
    }

    /**
     * @param Ticket $entity
     */
    protected function syncRelatedEntities(Ticket $entity)
    {
        $this->syncProblem($entity);
        $this->syncCollaborators($entity);
        $this->syncRequester($entity);
        $this->syncSubmitter($entity);
        $this->syncAssignee($entity);
        $this->syncRelatedCase($entity);
    }

    /**
     * @param Ticket $entity
     */
    protected function syncProblem(Ticket $entity)
    {
        if ($entity->getProblem()) {
            $entity->setProblem($this->zendeskProvider->getTicket($entity->getProblem()));
        }
    }

    /**
     * @param Ticket $entity
     */
    protected function syncCollaborators(Ticket $entity)
    {
        $collaborators = $entity->getCollaborators()->getValues();
        $entity->getCollaborators()->clear();
        foreach ($collaborators as $value) {
            $user = $this->zendeskProvider->getUser($value);
            if ($user) {
                $entity->addCollaborator($user);
            }
        }
    }

    /**
     * @param Ticket $entity
     */
    protected function syncRequester(Ticket $entity)
    {
        if ($entity->getRequester()) {
            $entity->setRequester($this->zendeskProvider->getUser($entity->getRequester()));
        }
    }

    /**
     * @param Ticket $entity
     */
    protected function syncSubmitter(Ticket $entity)
    {
        if ($entity->getSubmitter()) {
            $entity->setSubmitter($this->zendeskProvider->getUser($entity->getSubmitter()));
        }
    }

    /**
     * @param Ticket $entity
     */
    protected function syncAssignee(Ticket $entity)
    {
        if ($entity->getAssignee()) {
            $entity->setAssignee($this->zendeskProvider->getUser($entity->getAssignee()));
        }
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
            $owner = $this->oroEntityProvider->getDefaultUser();
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
            $assignedTo = $this->oroEntityProvider->getDefaultUser();
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
            $value = $this->entityMapper->getCaseStatus($name);
            if (!$value) {
                $message = "Can't convert Zendesk status [name=$name]";
                $this->getLogger()->error($message);
                $this->getContext()->addError($message);
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
            $value = $this->entityMapper->getCasePriority($name);
            if (!$value) {
                $message = "Can't convert Zendesk priority [name=$name]";
                $this->getLogger()->error($message);
                $this->getContext()->addError($message);
            } else {
                $case->setPriority($value);
            }
        }
    }
}
