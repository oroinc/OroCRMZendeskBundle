<?php

namespace Oro\Bundle\ZendeskBundle\EventListener\Doctrine;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * This class is responsible for scheduling sync job of integration entity of Zendesk related to Oro entity.
 */
abstract class AbstractSyncSchedulerListener implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var array */
    private $scheduledSyncMap;

    /** @var EntityManager */
    protected $entityManager;

    public function __construct(ContainerInterface $container, TokenAccessorInterface $tokenAccessor)
    {
        $this->container = $container;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * Schedules sync jobs for entities
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->scheduledSyncMap = [];
        $this->entityManager = $event->getEntityManager();

        // check for logged user is for confidence that data changes mes from UI, not from sync process.
        if (!$this->tokenAccessor->hasUser()) {
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
     * @throws \InvalidArgumentException If entity doesn't have an association with the integration channel
     */
    protected function isIntegrationEntityValid($entity)
    {
        if (!EntityPropertyInfo::methodExists($entity, 'getChannel')) {
            throw new \InvalidArgumentException(
                sprintf(
                    '$entity is instance of %s expect to have "getChannel" method.',
                    ClassUtils::getClass($entity)
                )
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
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_integration.sync_scheduler' => SyncScheduler::class
        ];
    }

    /**
     * @return SyncScheduler
     */
    protected function getSyncScheduler()
    {
        return $this->container->get('oro_integration.sync_scheduler');
    }
}
