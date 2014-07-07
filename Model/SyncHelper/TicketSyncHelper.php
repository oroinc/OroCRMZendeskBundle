<?php

namespace OroCRM\Bundle\ZendeskBundle\Model\SyncHelper;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

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
     * @var array
     */
    protected $changeSet = [];

    /**
     * @var null
     */
    protected $syncStrategy = null;

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
     * @param Ticket $targetTicket
     * @param Ticket $sourceTicket
     * @param Channel $channel
     * @param string $syncPriority
     */
    public function mergeTickets(Ticket $targetTicket, Ticket $sourceTicket, Channel $channel, $syncPriority)
    {
        switch ($syncPriority) {
            case TwoWaySyncConnectorInterface::LOCAL_WINS:
                $this->changeSet = $this->copyEntityProperties($targetTicket, $sourceTicket);
                $this->syncStrategy = TwoWaySyncConnectorInterface::LOCAL_WINS;
                $this->syncRelatedEntities($targetTicket, $channel);
                $this->syncStrategy = null;
                $this->changeSet = [];
                break;
            default:
            case TwoWaySyncConnectorInterface::REMOTE_WINS:
                // Don't care about change set, override all data from remote
                $this->copyEntityProperties($targetTicket, $sourceTicket);
                $this->syncRelatedEntities($targetTicket, $channel);
                break;
        }
    }

    /**
     * @param CaseEntity $targetCase
     * @param string $targetField
     * @param mixed $sourceValue
     * @param string $sourceField
     */
    protected function syncField(CaseEntity $targetCase, $targetField, $sourceValue, $sourceField)
    {
       if (!$this->hasConflictingChanges($targetCase, $targetField, $sourceValue, $sourceField)
           || $this->syncStrategy !== TwoWaySyncConnectorInterface::LOCAL_WINS) {
           self::getPropertyAccessor()->setValue($targetCase, $targetField, $sourceValue);
       }
    }

    protected function hasConflictingChanges(CaseEntity $targetCase, $targetField, $sourceValue, $sourceField)
    {
        $oldValue = self::getPropertyAccessor()->getValue($targetCase, $targetField);
        return $oldValue != $sourceValue && isset($this->changeSet[$sourceField]);
    }

    /**
     * {@inheritdoc}
     */
    public function copyEntityProperties($targetTicket, $sourceTicket)
    {
        return $this->syncProperties(
            $targetTicket,
            $sourceTicket,
            ['id', 'channel', 'relatedCase', 'updatedAtLocked', 'createdAt', 'updatedAt']
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
     * @param Ticket $ticket
     */
    protected function syncCaseFields(CaseEntity $case, Ticket $ticket)
    {
        $this->syncField($case, 'subject', $ticket->getSubject(), 'subject');
        $this->syncField($case, 'description', $ticket->getDescription(), 'description');
        $this->syncCaseOwner($case, $ticket);
        $this->syncCaseAssignedTo($case, $ticket);
        $this->syncCaseRelatedContact($case, $ticket);
        $this->syncCaseStatus($case, $ticket);
        $this->syncCasePriority($case, $ticket);
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $ticket
     */
    protected function syncCaseOwner(CaseEntity $case, Ticket $ticket)
    {
        if ($ticket->getSubmitter() && $ticket->getSubmitter()->getRelatedUser()) {
            $owner = $ticket->getSubmitter()->getRelatedUser();
            $this->syncField($case, 'owner', $owner, 'submitter');
        } elseif ($ticket->getRequester() && $ticket->getRequester()->getRelatedUser()) {
            $owner = $ticket->getRequester()->getRelatedUser();
            $this->syncField($case, 'owner', $owner, 'requester');
        } elseif ($ticket->getAssignee() && $ticket->getAssignee()->getRelatedUser()) {
            $owner = $ticket->getAssignee()->getRelatedUser();
            $this->syncField($case, 'owner', $owner, 'assignee');
        } else {
            $owner = $this->oroProvider->getDefaultUser($this->channel);
            $case->setOwner($owner);
        }
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $ticket
     */
    protected function syncCaseAssignedTo(CaseEntity $case, Ticket $ticket)
    {
        if ($ticket->getAssignee() && $ticket->getAssignee()->getRelatedUser()) {
            $assignedTo = $ticket->getAssignee()->getRelatedUser();
            $this->syncField($case, 'assignedTo', $assignedTo, 'assignee');
        } elseif ($ticket->getSubmitter() && $ticket->getSubmitter()->getRelatedUser()) {
            $assignedTo = $ticket->getSubmitter()->getRelatedUser();
            $this->syncField($case, 'assignedTo', $assignedTo, 'submitter');
        } elseif ($ticket->getRequester() && $ticket->getRequester()->getRelatedUser()) {
            $assignedTo = $ticket->getRequester()->getRelatedUser();
            $this->syncField($case, 'assignedTo', $assignedTo, 'requester');
        } else {
            $assignedTo = $this->oroProvider->getDefaultUser($this->channel);
            $case->setAssignedTo($assignedTo);
        }
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $ticket
     */
    protected function syncCaseRelatedContact(CaseEntity $case, Ticket $ticket)
    {
        if ($ticket->getRequester() && $ticket->getRequester()->getRelatedContact()) {
            $relatedContact = $ticket->getRequester()->getRelatedContact();
            $this->syncField($case, 'relatedContact', $relatedContact, 'requester');
        } elseif ($ticket->getSubmitter() && $ticket->getSubmitter()->getRelatedContact()) {
            $relatedContact = $ticket->getSubmitter()->getRelatedContact();
            $this->syncField($case, 'relatedContact', $relatedContact, 'submitter');
        } elseif ($ticket->getAssignee() && $ticket->getAssignee()->getRelatedContact()) {
            $relatedContact = $ticket->getAssignee()->getRelatedContact();
            $this->syncField($case, 'relatedContact', $relatedContact, 'assignee');
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
                $this->syncField($case, 'status', $value, 'status');
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
                $this->syncField($case, 'priority', $value, 'priority');
            }
        }
    }
}
