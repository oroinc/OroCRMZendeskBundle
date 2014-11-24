<?php

namespace OroCRM\Bundle\ZendeskBundle\EventListener\Doctrine;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * This class is responsible for scheduling sync job of integration entity of Zendesk related to OroCRM entity.
 */
abstract class AbstractSyncSchedulerListener implements EventSubscriber
{
    /**
     * @var ServiceLink
     */
    private $syncScheduler;

    /**
     * @var ServiceLink
     */
    private $securityFacade;

    /**
     * @var array
     */
    private $scheduledSyncMap;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param ServiceLink $securityFacadeLink
     * @param ServiceLink $schedulerServiceLink
     */
    public function __construct(ServiceLink $securityFacadeLink, ServiceLink $schedulerServiceLink)
    {
        $this->syncScheduler = $schedulerServiceLink;
        $this->securityFacade = $securityFacadeLink;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::onFlush];
    }

    /**
     * Schedules sync jobs for entities
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->scheduledSyncMap = [];
        $this->entityManager = $event->getEntityManager();

        // check for logged user is for confidence that data changes mes from UI, not from sync process.
        if (!$this->getSecurityFacade()->hasLoggedUser()) {
            return;
        }

        $this->scheduleEntitiesSync($this->getEntitiesToSync());

        $this->scheduledSyncMap = [];
        $this->entityManager = null;
    }

    /**
     * Get entities to sync
     *
     * @return array
     */
    abstract protected function getEntitiesToSync();

    /**
     * Schedule sync for entities
     *
     * @param array $entities
     */
    protected function scheduleEntitiesSync(array $entities)
    {
        foreach ($entities as $entity) {
            if (!$this->isSyncRequired($entity)) {
                continue;
            }

            $entityToSync = $this->getIntegrationEntityToSync($entity);
            if ($entityToSync && $this->isIntegrationEntityValid($entityToSync)) {
                $this->tryScheduleSync($entityToSync);
            }
        }
    }

    /**
     * Checks if entity sync is required.
     *
     * @param mixed $entity
     * @return bool
     */
    abstract protected function isSyncRequired($entity);

    /**
     * Get integration entity to sync
     *
     * @param mixed $entity
     * @return mixed|null Class that has Oro\Bundle\IntegrationBundle\Model\IntegrationEntityTrait
     */
    abstract protected function getIntegrationEntityToSync($entity);

    /**
     * Checks integration entity is valid and has channel
     *
     * @param mixed $entity
     * @return bool
     * @throws \InvalidArgumentException If entity doesn't use Oro\Bundle\IntegrationBundle\Model\IntegrationEntityTrait
     */
    protected function isIntegrationEntityValid($entity)
    {
        $class = ClassUtils::getRealClass($entity);
        $integrationTrait = 'Oro\\Bundle\\IntegrationBundle\\Model\\IntegrationEntityTrait';
        if (!in_array($integrationTrait, class_uses($class))) {
            throw new \InvalidArgumentException(
                sprintf('$entity is instance of %s expect to use %s trait.', $class, $integrationTrait)
            );
        }

        return null !== $entity->getChannel();
    }

    /**
     * Attempts to schedule sync if it's possible
     *
     * @param mixed $entity
     */
    protected function tryScheduleSync($entity)
    {
        $key = spl_object_hash($entity);

        if (isset($this->scheduledSyncMap[$key])) {
            return;
        }

        $this->scheduledSyncMap[$key] = true;

        if ($this->isTwoWaySyncEnabled($entity)) {
            $this->scheduleSync($entity);
        }
    }

    /**
     * Is channel configured with two way sync option
     *
     * @param mixed $entity
     * @return bool
     */
    protected function isTwoWaySyncEnabled($entity)
    {
        $channel = $entity->getChannel();
        return $channel && $channel->getSynchronizationSettings()->offsetGetOr('isTwoWaySyncEnabled', false)
            && $channel->isEnabled();
    }

    /**
     * Schedule sync
     *
     * @param mixed $entity
     * @return mixed
     */
    abstract protected function scheduleSync($entity);

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->securityFacade->getService();
    }

    /**
     * @return SyncScheduler
     */
    protected function getSyncScheduler()
    {
        return $this->syncScheduler->getService();
    }
}
