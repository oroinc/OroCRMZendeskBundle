<?php

namespace Oro\Bundle\ZendeskBundle\EventListener\CaseEntity;

use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\CaseBundle\Event\Events;
use Oro\Bundle\CaseBundle\Event\FormHandlerEvent;
use Oro\Bundle\ZendeskBundle\Form\Extension\SyncWithZendeskExtension;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\SyncManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CaseEntityListener implements EventSubscriberInterface
{
    /**
     * @var SyncManager
     */
    protected $syncManager;

    /**
     * @var OroEntityProvider
     */
    protected $oroEntityProvider;

    public function __construct(SyncManager $syncManager, OroEntityProvider $oroEntityProvider)
    {
        $this->syncManager = $syncManager;
        $this->oroEntityProvider = $oroEntityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::BEFORE_SAVE => 'beforeSave'
        ];
    }

    public function beforeSave(FormHandlerEvent $formHandlerEvent)
    {
        $entity = $formHandlerEvent->getEntity();
        $form = $formHandlerEvent->getForm();

        if ($entity instanceof CaseEntity) {
            $channelFieldName = SyncWithZendeskExtension::ZENDESK_CHANNEL_FIELD;
            if (!$form->has($channelFieldName)) {
                return;
            }

            $channelField = $form->get($channelFieldName);

            $channelId = $channelField->getData();
            if ($channelId) {
                $channel = $this->oroEntityProvider->getChannelById($channelId);
                $this->syncManager->syncCase($entity, $channel);
            }
        }

        if ($entity instanceof CaseComment) {
            $this->syncManager->syncComment($entity);
        }
    }
}
