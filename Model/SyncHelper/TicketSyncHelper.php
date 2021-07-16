<?php

namespace Oro\Bundle\ZendeskBundle\Model\SyncHelper;

use Oro\Bundle\CaseBundle\Model\CaseEntityManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Model\EntityMapper;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet\ChangeSet;

/**
 * Contains ticket sync logic that is used in both import and export.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TicketSyncHelper extends AbstractSyncHelper
{
    /**
     * @var null
     */
    protected $syncStrategy = null;

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

    protected function refreshProblem(Ticket $entity, Channel $channel)
    {
        if ($entity->getProblem()) {
            $entity->setProblem($this->zendeskProvider->getTicket($entity->getProblem(), $channel));
        }
    }

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

    protected function refreshRequester(Ticket $entity, Channel $channel)
    {
        if ($entity->getRequester()) {
            $entity->setRequester($this->zendeskProvider->getUser($entity->getRequester(), $channel, true));
        }
    }

    protected function refreshSubmitter(Ticket $entity, Channel $channel)
    {
        if ($entity->getSubmitter()) {
            $entity->setSubmitter($this->zendeskProvider->getUser($entity->getSubmitter(), $channel, true));
        }
    }

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
            $relatedCase->setReportedAt($ticket->getOriginCreatedAt());
            if ($ticket->getStatus() &&
                $ticket->getStatus()->getName() == TicketStatus::STATUS_CLOSED
            ) {
                $relatedCase->setClosedAt($ticket->getOriginUpdatedAt());
            }
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
        $this->addCaseStatusChanges($changeSet, $ticket);
        $this->addCasePriorityChanges($changeSet, $ticket);

        return $changeSet;
    }

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

        // set case organization
        $changeSet->add('organization', ['value' => $this->getRefreshedChannelOrganization($channel)], 'id');
    }

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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function addCaseRelatedContactChanges(ChangeSet $changeSet, Ticket $ticket, Channel $channel)
    {
        $channelOrganizationId = $this->getRefreshedChannelOrganization($channel)->getId();
        if ($ticket->getRequester()
            && $ticket->getRequester()->getRelatedContact()
            && $this->checkObjectOrganizationId($ticket->getRequester()->getRelatedContact(), $channelOrganizationId)
        ) {
            $changeSet->add('relatedContact', ['property' => 'requester', 'path' => 'requester.relatedContact'], 'id');
        } elseif ($ticket->getSubmitter()
            && $ticket->getSubmitter()->getRelatedContact()
            && $this->checkObjectOrganizationId($ticket->getSubmitter()->getRelatedContact(), $channelOrganizationId)
        ) {
            $changeSet->add('relatedContact', ['property' => 'submitter', 'path' => 'submitter.relatedContact'], 'id');
        } elseif ($ticket->getAssignee()
            && $ticket->getAssignee()->getRelatedContact()
            && $this->checkObjectOrganizationId($ticket->getAssignee()->getRelatedContact(), $channelOrganizationId)
        ) {
            $changeSet->add('relatedContact', ['property' => 'assignee', 'path' => 'assignee.relatedContact'], 'id');
        }
    }

    /**
     * Check if given object organization is the same as channel organization id
     *
     * @param object $object
     * @param int $channelOrganizationId
     * @return bool
     */
    protected function checkObjectOrganizationId($object, $channelOrganizationId)
    {
        return $object->getOrganization()
            && $object->getOrganization()->getId() == $channelOrganizationId;
    }

    protected function addCaseStatusChanges(ChangeSet $changeSet, Ticket $ticket)
    {
        if ($ticket->getStatus()) {
            $name = $ticket->getStatus()->getName();
            $value = $this->entityMapper->getCaseStatus($name);
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

    protected function addCasePriorityChanges(ChangeSet $changeSet, Ticket $ticket)
    {
        if ($ticket->getPriority()) {
            $name = $ticket->getPriority()->getName();
            $value = $this->entityMapper->getCasePriority($name);
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
