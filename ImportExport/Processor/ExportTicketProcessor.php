<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Processor;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Model\EntityMapper;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet\ChangeSet;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet\ChangeValue;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\TicketSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;

class ExportTicketProcessor extends AbstractExportProcessor
{
    /**
     * @var ZendeskTransportInterface
     */
    protected $transport;

    /**
     * @var TicketSyncHelper
     */
    protected $ticketHelper;

    /**
     * @var EntityMapper
     */
    protected $entityMapper;

    /**
     * @param ZendeskTransportInterface $transport
     * @param TicketSyncHelper $ticketHelper
     * @param EntityMapper $entityMapper
     */
    public function __construct(
        ZendeskTransportInterface $transport,
        TicketSyncHelper $ticketHelper,
        EntityMapper $entityMapper
    ) {
        $this->transport = $transport;
        $this->ticketHelper = $ticketHelper;
        $this->entityMapper = $entityMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function process($ticket)
    {
        $this->transport->init($this->getChannel()->getTransport());

        if (!$ticket instanceof Ticket) {
            throw new InvalidArgumentException(
                sprintf(
                    'Imported entity must be instance of OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket, %s given.',
                    is_object($ticket) ? get_class($ticket) : gettype($ticket)
                )
            );
        }

        $this->getLogger()->setMessagePrefix("Zendesk Ticket [origin_id={$ticket->getOriginId()}]: ");

        $ticketLocalChanges = $this->calculateTicketLocalChanges($ticket);

        if (!$ticketLocalChanges) {
            return null;
        }

        if ($this->getSyncPriority() == TwoWaySyncConnectorInterface::REMOTE_WINS && $ticket->getOriginId()) {
            $ticketRemoteChanges = $this->calculateTicketRemoteChanges($ticket);

            /** @var ChangeValue $changeValue */
            foreach ($ticketRemoteChanges as $targetProperty => $changeValue) {
                unset($ticketLocalChanges[$targetProperty]);
                $ticketLocalChanges->add(
                    $changeValue->getTargetProperty(),
                    ['value' => $changeValue->getSourceValue()],
                    null,
                    true
                );
            }

            $ticketLocalChanges->apply();
        } else {
            $ticketLocalChanges->apply();
        }

        return $ticketLocalChanges->getTarget();
    }

    /**
     * @param Ticket $ticket
     * @return ChangeSet
     */
    protected function calculateTicketRemoteChanges(Ticket $ticket)
    {
        $remoteTicket = $this->transport->getTicket($ticket->getOriginId());

        $this->ticketHelper->refreshTicket($remoteTicket, $this->getChannel());

        return $this->ticketHelper->calculateTicketsChanges($ticket, $remoteTicket);
    }

    /**
     * @param Ticket $ticket
     * @return ChangeSet
     */
    protected function calculateTicketLocalChanges(Ticket $ticket)
    {
        $relatedCase = $ticket->getRelatedCase();

        if (!$relatedCase) {
            $this->getLogger()->error('Ticket must have related Case.');
            $this->getContext()->incrementErrorEntriesCount();
            return null;
        }

        $changeSet = new ChangeSet($ticket, $relatedCase);

        $changeSet->add('subject');
        if ($relatedCase->getDescription()) {
            $changeSet->add('description');
        } else {
            $changeSet->add('description', 'subject');
        }

        $this->addStatusChanges($changeSet, $relatedCase);
        $this->addPriorityChanges($changeSet, $relatedCase);
        $this->addRelatedContactChanges($changeSet, $relatedCase, !$ticket->getOriginId());
        $this->addAssignedToChanges($changeSet, $relatedCase);
        $this->addRequesterChanges($changeSet, $relatedCase, $ticket);

        return $changeSet;
    }

    /**
     * @param ChangeSet $changeSet
     * @param CaseEntity $case
     */
    protected function addStatusChanges(ChangeSet $changeSet, CaseEntity $case)
    {
        $statusName = $case->getStatus()->getName();

        $ticketStatus = $this->entityMapper->getTicketStatus($statusName);

        if (!$ticketStatus) {
            $this->getLogger()->error("Can't convert Zendesk status [name=$statusName]");
        } else {
            $changeSet->add('status', ['property' => 'status', 'value' => $ticketStatus]);
        }
    }

    /**
     * @param ChangeSet $changeSet
     * @param CaseEntity $case
     */
    protected function addPriorityChanges(ChangeSet $changeSet, CaseEntity $case)
    {
        $priority = $case->getPriority();
        if ($priority) {
            $name = $priority->getName();
            $value = $this->entityMapper->getTicketPriority($name);
            if (!$value) {
                $this->getLogger()->error("Can't convert Zendesk priority [name=$name]");
            } else {
                $changeSet->add('priority', ['property' => 'priority', 'value' => $value]);
            }
        }
    }

    /**
     * @param ChangeSet $changeSet
     * @param CaseEntity $case
     * @param bool $isNew
     */
    protected function addRelatedContactChanges(ChangeSet $changeSet, CaseEntity $case, $isNew)
    {
        $relatedContact = $case->getRelatedContact();
        if (!$relatedContact) {
            return;
        }

        $relatedUser = $this->zendeskProvider->getUserByContact($relatedContact, $this->getChannel());
        if (!$relatedUser) {
            $this->getLogger()->error("Can't sync contact [id={$relatedContact->getId()}]");
            return;
        }
        if ($isNew) {
            $changeSet->add('submitter', ['value' => $relatedUser]);
            $changeSet->add('requester', ['value' => $relatedUser]);
        } else {
            $changeSet->add('requester', ['value' => $relatedUser]);
        }
    }

    /**
     * @param ChangeSet $changeSet
     * @param CaseEntity $case
     */
    protected function addAssignedToChanges(ChangeSet $changeSet, CaseEntity $case)
    {
        $assignedTo = $case->getAssignedTo();

        $assignee = null;
        if ($assignedTo) {
            $assignee = $this->zendeskProvider->getUserByOroUser($assignedTo, $this->getChannel(), true);
        }

        $changeSet->add('assignee', ['value' => $assignee]);
    }

    /**
     * @param ChangeSet $changeSet
     * @param CaseEntity $case
     * @param Ticket $ticket
     */
    protected function addRequesterChanges(ChangeSet $changeSet, CaseEntity $case, Ticket $ticket)
    {
        if (!$ticket->getRequester() && $case->getOwner()) {
            $requester = $this->zendeskProvider->getUserByOroUser($case->getOwner(), $this->getChannel(), true);
            $changeSet->add('requester', ['value' => $requester]);
        }
    }

    /**
     * @return string
     */
    protected function getSyncPriority()
    {
        return $this->getChannel()->getSynchronizationSettings()->offsetGetOr('syncPriority');
    }
}
