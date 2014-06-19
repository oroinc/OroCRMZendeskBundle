<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\CaseBundle\Model\CaseEntityManager;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;

class TicketSyncStrategy extends AbstractSyncStrategy
{
    /**
     * @var CaseEntityManager
     */
    protected $caseEntityManager;

    /**
     * @param CaseEntityManager $caseEntityManager
     */
    public function __construct(CaseEntityManager $caseEntityManager)
    {
        $this->caseEntityManager = $caseEntityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof Ticket) {
            throw new InvalidArgumentException('Imported entity must be instance of Zendesk Ticket');
        }

        if (!$this->validateOriginId($entity)) {
            return null;
        }

        $this->refreshDictionaryField($entity, 'status', 'ticketStatus');
        $this->refreshDictionaryField($entity, 'priority', 'ticketPriority');
        $this->refreshDictionaryField($entity, 'type', 'ticketType');

        $existingTicket = $this->zendeskProvider->getTicket($entity);
        if ($existingTicket) {
            $this->syncProperties($existingTicket, $entity, array('relatedCase', 'id'));
            $entity = $existingTicket;

            $this->getLogger()->debug($this->buildMessage("Update found record.", $entity));
            $this->getContext()->incrementUpdateCount();
        } else {
            $this->getLogger()->debug($this->buildMessage("Add new record.", $entity));
            $this->getContext()->incrementAddCount();
        }

        $this->syncRelatedEntities($entity);

        return $entity;
    }

    /**
     * @param Ticket $entity
     */
    protected function syncRelatedEntities(Ticket $entity)
    {
        $this->syncRelatedCase($entity);
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
        $this->syncCaseByTicket($entity, $relatedCase);
    }

    /**
     * @param CaseEntity $case
     * @param Ticket $ticket
     * @return CaseEntity
     */
    protected function syncCaseByTicket(CaseEntity $case, Ticket $ticket)
    {
        $case->setPriority();
    }
}
