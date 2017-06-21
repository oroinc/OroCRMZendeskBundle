<?php

namespace Oro\Bundle\ZendeskBundle\EventListener\Doctrine;

use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\ZendeskBundle\Provider\TicketConnector;

/**
 * This class is responsible for scheduling sync job for Zendesk ticket related to updated case entity.
 */
class SyncUpdateCaseListener extends AbstractSyncSchedulerListener
{
    /**
     * @var array
     */
    protected $syncFields = [
        'subject',
        'description',
        'status',
        'priority',
        'relatedContact',
        'assignedTo',
        'owner',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getEntitiesToSync()
    {
        $entities = $this->entityManager->getUnitOfWork()->getScheduledEntityUpdates();

        $result = [];

        foreach ($entities as $entity) {
            if ($entity instanceof CaseEntity) {
                $result[] = $entity;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function isSyncRequired($entity)
    {
        /** @var CaseEntity $entity */
        $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($entity);

        foreach (array_keys($changeSet) as $fieldName) {
            if (in_array($fieldName, $this->syncFields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIntegrationEntityToSync($entity)
    {
        $result = null;
        /** @var CaseEntity $entity */
        if ($entity->getId()) {
            $result = $this->entityManager->getRepository('OroZendeskBundle:Ticket')
                ->findOneBy(['relatedCase' => $entity]);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function scheduleSync($entity)
    {
        $this->getSyncScheduler()->schedule($entity->getChannel()->getId(), TicketConnector::TYPE, [
            'id' => $entity->getId()
        ]);
    }
}
