<?php

namespace OroCRM\Bundle\ZendeskBundle\EventListener\Channel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\ZendeskBundle\Provider\ChannelType;
use OroCRM\Bundle\ZendeskBundle\Model\SyncManager;
use Oro\Bundle\IntegrationBundle\Event\IntegrationUpdateEvent;

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
            IntegrationUpdateEvent::NAME => 'onUpdate'
        ];
    }

    /**
     * @param IntegrationUpdateEvent $event
     */
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
