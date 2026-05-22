<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;

/**
 * Listens for changes to the two-way sync setting on Zendesk channels.
 */
class TwoWaySyncChangeListener
{
    public function onFlush(OnFlushEventArgs $event): void
    {
        $em  = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Channel || ChannelType::TYPE !== $entity->getType()) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            if (!$this->twoWaySyncStateChanged($changeSet)) {
                continue;
            }

            $transport = $entity->getTransport();
            if (!$transport instanceof ZendeskRestTransport) {
                continue;
            }

            if (false === $transport->getAuthorizationType()?->isOAuth()) {
                continue;
            }

            $this->clearTransportOAuthTokens($em, $transport);
        }
    }

    private function twoWaySyncStateChanged(array $changeSet): bool
    {
        if (!isset($changeSet['synchronizationSettings'])) {
            return false;
        }

        [$oldSettings, $newSettings] = $changeSet['synchronizationSettings'];

        $oldSyncEnabled = (bool) $oldSettings->offsetGetOr('isTwoWaySyncEnabled', false);
        $newSyncEnabled = (bool) $newSettings->offsetGetOr('isTwoWaySyncEnabled', false);

        return $oldSyncEnabled !== $newSyncEnabled;
    }

    private function clearTransportOAuthTokens(ObjectManager $em, ZendeskRestTransport $transport): void
    {
        $repository = $em->getRepository(ZendeskRestTransport::class);
        $repository->clearOAuthTokensById((int) $transport->getId());
    }
}
