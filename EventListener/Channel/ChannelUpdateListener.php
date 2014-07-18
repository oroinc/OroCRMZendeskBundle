<?php

namespace OroCRM\Bundle\ZendeskBundle\EventListener\Channel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\ZendeskBundle\Provider\ChannelType;
use OroCRM\Bundle\ZendeskBundle\Model\SyncManager;
use Oro\Bundle\IntegrationBundle\Event\ChannelUpdateEvent;

class ChannelUpdateListener implements EventSubscriberInterface
{
    /**
     * @var SyncManager
     */
    protected $syncManager;

    /**
     * @param SyncManager $syncManager
     */
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
            ChannelUpdateEvent::NAME => 'onUpdate'
        ];
    }

    /**
     * @param ChannelUpdateEvent $event
     */
    public function onUpdate(ChannelUpdateEvent $event)
    {
        $channel = $event->getChannel();

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
        $alreadySynced = $this->isTwoWaySyncEnabled($oldState) && $oldState->getEnabled();

        if (!$this->isTwoWaySyncEnabled($channel) || !$channel->getEnabled() || $alreadySynced) {
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
