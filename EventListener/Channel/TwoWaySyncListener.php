<?php

namespace OroCRM\Bundle\ZendeskBundle\EventListener\Channel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use OroCRM\Bundle\ZendeskBundle\Model\SyncManager;
use Oro\Bundle\IntegrationBundle\Event\TwoWaySyncEnableEvent;

class TwoWaySyncListener implements EventSubscriberInterface
{
    /**
     * @var
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
            TwoWaySyncEnableEvent::NAME => 'setOn'
        ];
    }

    public function setOn(TwoWaySyncEnableEvent $event)
    {
        $this->syncManager->reverseSyncChannel($event->getChannel());
    }
}
