<?php

namespace Oro\Bundle\ZendeskBundle\EventListener\Channel;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\IntegrationUpdateEvent;
use Oro\Bundle\ZendeskBundle\Model\SyncManager;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChannelUpdateListener implements EventSubscriberInterface
{
    /**
     * @var SyncManager
     */
    protected $syncManager;

    public function __construct(SyncManager $syncManager)
    {
        $this->syncManager = $syncManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            IntegrationUpdateEvent::NAME => 'onUpdate'
        ];
    }

    public function onUpdate(IntegrationUpdateEvent $event)
    {
        $channel = $event->getIntegration();

        if ($channel->getType() == ChannelType::TYPE && $this->isNotSynced($channel, $event->getOldState())) {
            $this->syncManager->reverseSyncChannel($channel);
        }
    }

    /**
     * @param Channel $channel
     * @param Channel $oldState
     * @return bool
     */
    protected function isNotSynced(Channel $channel, Channel $oldState)
    {
        $alreadySynced = $this->isTwoWaySyncEnabled($oldState) && $oldState->isEnabled();

        if (!$this->isTwoWaySyncEnabled($channel) || !$channel->isEnabled() || $alreadySynced) {
            return false;
        }

        return true;
    }

    /**
     * @param Channel $channel
     * @return bool
     */
    protected function isTwoWaySyncEnabled(Channel $channel)
    {
        $isTwoWaySyncEnabled = $channel->getSynchronizationSettings()
            ->offsetGetOr('isTwoWaySyncEnabled', false);
        return $isTwoWaySyncEnabled;
    }
}
