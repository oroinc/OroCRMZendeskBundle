<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Processor;

use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use OroCRM\Bundle\ZendeskBundle\Model\EntityMapper;

class TicketExportProcessor extends AbstractExportProcessor
{
    /**
     * @var EntityMapper
     */
    protected $entityMapper;

    /**
     * @param EntityMapper $entityMapper
     */
    public function __construct(EntityMapper $entityMapper)
    {
        $this->entityMapper = $entityMapper;
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

        $this->getLogger()->setMessagePrefix("Zendesk Ticket [id={$entity->getOriginId()}]: ");

        return $this->syncTicket($entity);
    }

    /**
     * @param Ticket $ticket
     * @return Ticket|null
     * @throws \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     */
    protected function syncTicket(Ticket $ticket)
    {
        $case = $ticket->getRelatedCase();

        if (!$case) {
            $this->getLogger()->error('Ticket must have related Case.');
            $this->getContext()->incrementErrorEntriesCount();
            return null;
        }

        $this->syncFields($ticket, $case);

        $this->syncStatus($ticket, $case);
        $this->syncPriority($ticket, $case);
        $this->syncRelatedContact($ticket, $case);
        $this->syncAssignee($ticket, $case);
        $this->syncRequester($ticket, $case);

        if (!$ticket->getRequester()) {
            $this->getLogger()->error('Requester not found.');
            $this->getContext()->incrementErrorEntriesCount();
            return null;
        }

        return $ticket;
    }

    /**
     * @param Ticket     $ticket
     * @param CaseEntity $case
     */
    protected function syncFields(Ticket $ticket, CaseEntity $case)
    {
        $ticket->setSubject($case->getSubject());
        $ticket->setDescription($case->getDescription());
    }

    /**
     * @param Ticket     $ticket
     * @param CaseEntity $case
     */
    protected function syncStatus(Ticket $ticket, CaseEntity $case)
    {
        $statusName = $case->getStatus()->getName();

        $ticketStatus = $this->entityMapper->getTicketStatus($statusName, $this->getChannel());

        if (!$ticketStatus) {
            $this->getLogger()->error("Can't convert status [name=$statusName]");
        } else {
            $ticket->setStatus($ticketStatus);
        }
    }

    /**
     * @param Ticket     $ticket
     * @param CaseEntity $case
     */
    protected function syncPriority(Ticket $ticket, CaseEntity $case)
    {
        $priority = $case->getPriority();
        if ($priority) {
            $name = $priority->getName();
            $value = $this->entityMapper->getTicketPriority($name, $this->getChannel());
            if (!$value) {
                $this->getLogger()->error("Can't convert priority [name=$name]");
            } else {
                $ticket->setPriority($value);
            }
        }
    }

    /**
     * @param Ticket     $ticket
     * @param CaseEntity $case
     */
    protected function syncRelatedContact(Ticket $ticket, CaseEntity $case)
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
        if (!$ticket->getOriginId()) {
            $ticket->setSubmitter($relatedUser);
            $ticket->setRequester($relatedUser);
        } else {
            $ticket->setRequester($relatedUser);
        }
    }

    /**
     * @param Ticket     $ticket
     * @param CaseEntity $case
     */
    protected function syncAssignee(Ticket $ticket, CaseEntity $case)
    {
        $assignedTo = $case->getAssignedTo();

        $assignee  = null;
        if ($assignedTo) {
            $assignee = $this->zendeskProvider->getUserByOroUser($assignedTo, $this->getChannel(), true);
        }

        $ticket->setAssignee($assignee);
    }

    /**
     * @param Ticket     $ticket
     * @param CaseEntity $case
     */
    protected function syncRequester(Ticket $ticket, CaseEntity $case)
    {
        if (!$ticket->getRequester()) {
            $owner = $case->getOwner();

            $user = $this->zendeskProvider->getUserByOroUser($owner, $this->getChannel(), true);
            $ticket->setRequester($user);
        }
    }
}
