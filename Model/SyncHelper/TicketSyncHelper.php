<?php

namespace OroCRM\Bundle\ZendeskBundle\Model\SyncHelper;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\CaseBundle\Model\CaseEntityManager;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Model\EntityMapper;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet\ChangeSet;

class TicketSyncHelper extends AbstractSyncHelper
{
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
     * Try to get managed Ticket entity
     *
     * @param Ticket $ticket
     * @param Channel $channel
     * @return null|Ticket
     */
    public function findTicket(Ticket $ticket, Channel $channel)
    {
        return $this->zendeskProvider->getTicket($ticket, $channel);
    }

    /**
     * Returns change set for tickets
     *
     * @param Ticket $targetTicket
     * @param Ticket $sourceTicket
     * @return ChangeSet
     */
    public function calculateTicketsChanges(Ticket $targetTicket, Ticket $sourceTicket)
    {
        $result = new ChangeSet($targetTicket, $sourceTicket);

        $result->add('originId');
        $result->add('url');
        $result->add('subject');
        $result->add('description');
        $result->add('externalId');
        $result->add('problem', null, 'originId');
        $result->add('collaborators', null, null, true);
        $result->add('type', null, 'name');
        $result->add('status', null, 'name');
        $result->add('priority', null, 'name');
        $result->add('recipient');
        $result->add('assignee', null, 'originId');
        $result->add('requester', null, 'originId');
        $result->add('submitter', null, 'originId');
        $result->add('hasIncidents');
        $result->add('dueAt');
        $result->add('originCreatedAt');
        $result->add('originUpdatedAt');
        $result->add('comments', null, null, true);

        return $result;
    }

    /**
     * Refreshes all relation entities of Ticket entity
     *
     * @param Ticket $ticket
     * @param Channel $channel
     * @return null|Ticket
     */
    public function refreshTicket(Ticket $ticket, Channel $channel)
    {
        $this->refreshChannel($ticket, $channel);

        $this->refreshDictionaryField($ticket, 'status', 'ticketStatus', true);
        $this->refreshDictionaryField($ticket, 'priority', 'ticketPriority');
        $this->refreshDictionaryField($ticket, 'type', 'ticketType');

        $this->refreshProblem($ticket, $channel);
        $this->refreshCollaborators($ticket, $channel);
        $this->refreshRequester($ticket, $channel);
        $this->refreshSubmitter($ticket, $channel);
        $this->refreshAssignee($ticket, $channel);
    }

    /**
     * @param Ticket $entity
     * @param Channel $channel
     */
    protected function refreshProblem(Ticket $entity, Channel $channel)
    {
        if ($entity->getProblem()) {
            $entity->setProblem($this->zendeskProvider->getTicket($entity->getProblem(), $channel));
        }
    }

    /**
     * @param Ticket $entity
     * @param Channel $channel
     */
    protected function refreshCollaborators(Ticket $entity, Channel $channel)
    {
        $collaborators = $entity->getCollaborators()->getValues();
        $entity->getCollaborators()->clear();
        foreach ($collaborators as $value) {
            $user = $this->zendeskProvider->getUser($value, $channel);
            if ($user) {
                $entity->addCollaborator($user);
            }
        }
    }

    /**
     * @param Ticket $entity
     * @param Channel $channel
     */
    protected function refreshRequester(Ticket $entity, Channel $channel)
    {
        if ($entity->getRequester()) {
            $entity->setRequester($this->zendeskProvider->getUser($entity->getRequester(), $channel, true));
        }
    }

    /**
     * @param Ticket $entity
     * @param Channel $channel
     */
    protected function refreshSubmitter(Ticket $entity, Channel $channel)
    {
        if ($entity->getSubmitter()) {
            $entity->setSubmitter($this->zendeskProvider->getUser($entity->getSubmitter(), $channel, true));
        }
    }

    /**
     * @param Ticket $entity
     * @param Channel $channel
     */
    protected function refreshAssignee(Ticket $entity, Channel $channel)
    {
        if ($entity->getAssignee()) {
            $entity->setAssignee($this->zendeskProvider->getUser($entity->getAssignee(), $channel, true));
        }
    }

    /**
     * Get change set of syncing related case of ticket.
     *
     * @param Ticket $ticket
     * @param Channel $channel
     * @return ChangeSet Change set of related case
     */
    public function calculateRelatedCaseChanges(Ticket $ticket, Channel $channel)
    {
        $relatedCase = $ticket->getRelatedCase();
        if (!$relatedCase) {
            $relatedCase = $this->caseEntityManager->createCase();
            $ticket->setRelatedCase($relatedCase);
        }

        if (!$ticket->getSubject()) {
            $ticket->setSubject('N\A');
        }

        $changeSet = new ChangeSet($relatedCase, $ticket);

        $changeSet->add('subject', 'subject');
        $changeSet->add('description', 'description');

        $this->addCaseOwnerChanges($changeSet, $ticket, $channel);
        $this->addCaseAssignedToChanges($changeSet, $ticket, $channel);
        $this->addCaseRelatedContactChanges($changeSet, $ticket, $channel);
        $this->addCaseStatusChanges($changeSet, $ticket, $channel);
        $this->addCasePriorityChanges($changeSet, $ticket, $channel);

        return $changeSet;
    }

    /**
     * @param ChangeSet $changeSet
     * @param Ticket $ticket
     * @param Channel $channel
     */
    protected function addCaseOwnerChanges(ChangeSet $changeSet, Ticket $ticket, Channel $channel)
    {
        if ($ticket->getSubmitter() && $ticket->getSubmitter()->getRelatedUser()) {
            $changeSet->add('owner', ['property' => 'submitter', 'path' => 'submitter.relatedUser'], 'id');
        } elseif ($ticket->getRequester() && $ticket->getRequester()->getRelatedUser()) {
            $changeSet->add('owner', ['property' => 'requester', 'path' => 'requester.relatedUser'], 'id');
        } elseif ($ticket->getAssignee() && $ticket->getAssignee()->getRelatedUser()) {
            $changeSet->add('owner', ['property' => 'assignee', 'path' => 'assignee.relatedUser'], 'id');
        } else {
            $owner = $this->oroProvider->getDefaultUser($channel);
            $changeSet->add('owner', ['value' => $owner], 'id');
        }
    }

    /**
     * @param ChangeSet $changeSet
     * @param Ticket $ticket
     * @param Channel $channel
     */
    protected function addCaseAssignedToChanges(ChangeSet $changeSet, Ticket $ticket, Channel $channel)
    {
        if ($ticket->getAssignee() && $ticket->getAssignee()->getRelatedUser()) {
            $changeSet->add('assignedTo', ['property' => 'assignee', 'path' => 'assignee.relatedUser'], 'id');
        } elseif ($ticket->getSubmitter() && $ticket->getSubmitter()->getRelatedUser()) {
            $changeSet->add('assignedTo', ['property' => 'submitter', 'path' => 'submitter.relatedUser'], 'id');
        } elseif ($ticket->getRequester() && $ticket->getRequester()->getRelatedUser()) {
            $changeSet->add('assignedTo', ['property' => 'requester', 'path' => 'requester.relatedUser'], 'id');
        } else {
            $owner = $this->oroProvider->getDefaultUser($channel);
            $changeSet->add('assignedTo', ['value' => $owner], 'id');
        }
    }

    /**
     * @param ChangeSet $changeSet
     * @param Ticket $ticket
     * @param Channel $channel
     */
    protected function addCaseRelatedContactChanges(ChangeSet $changeSet, Ticket $ticket, Channel $channel)
    {
        if ($ticket->getRequester() && $ticket->getRequester()->getRelatedContact()) {
            $changeSet->add('relatedContact', ['property' => 'requester', 'path' => 'requester.relatedContact'], 'id');
        } elseif ($ticket->getSubmitter() && $ticket->getSubmitter()->getRelatedContact()) {
            $changeSet->add('relatedContact', ['property' => 'submitter', 'path' => 'submitter.relatedContact'], 'id');
        } elseif ($ticket->getAssignee() && $ticket->getAssignee()->getRelatedContact()) {
            $changeSet->add('relatedContact', ['property' => 'assignee', 'path' => 'assignee.relatedContact'], 'id');
        }
    }

    /**
     * @param ChangeSet $changeSet
     * @param Ticket $ticket
     * @param Channel $channel
     */
    protected function addCaseStatusChanges(ChangeSet $changeSet, Ticket $ticket, Channel $channel)
    {
        if ($ticket->getStatus()) {
            $name = $ticket->getStatus()->getName();
            $value = $this->entityMapper->getCaseStatus($name, $channel);
            if (!$value) {
                $this->getLogger()->error("Can't convert Zendesk status [name=$name]");
            } else {
                $changeSet->add(
                    'status',
                    ['property' => 'status', 'value' => $value],
                    'name'
                );
            }
        }
    }

    /**
     * @param ChangeSet $changeSet
     * @param Ticket $ticket
     * @param Channel $channel
     */
    protected function addCasePriorityChanges(ChangeSet $changeSet, Ticket $ticket, Channel $channel)
    {
        if ($ticket->getPriority()) {
            $name = $ticket->getPriority()->getName();
            $value = $this->entityMapper->getCasePriority($name, $channel);
            if (!$value) {
                $this->getLogger()->error("Can't convert Zendesk priority [name=$name]");
            } else {
                $changeSet->add(
                    'priority',
                    ['property' => 'priority', 'value' => $value],
                    'name'
                );
            }
        }
    }
}
