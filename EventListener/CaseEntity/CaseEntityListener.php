<?php

namespace OroCRM\Bundle\ZendeskBundle\EventListener\CaseEntity;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\CaseBundle\Event\FormHandlerEvent;
use OroCRM\Bundle\CaseBundle\Event\Events;
use OroCRM\Bundle\ZendeskBundle\Form\Extension\SyncWithZendeskExtension;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Model\SyncManager;

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

    /**
     * @param SyncManager       $syncManager
     * @param OroEntityProvider $oroEntityProvider
     */
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

    /**
     * @param FormHandlerEvent $formHandlerEvent
     */
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
