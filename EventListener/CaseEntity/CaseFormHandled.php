<?php

namespace OroCRM\Bundle\ZendeskBundle\EventListener\CaseEntity;

use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\CaseBundle\Event\FormHandlerEvent;
use OroCRM\Bundle\ZendeskBundle\Form\Extension\SyncWithZendeskExtension;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\OroEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Model\SyncManager;

class CaseFormHandled
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

    public function onSuccess(FormHandlerEvent $formHandlerEvent)
    {
        $entity = $formHandlerEvent->getEntity();
        if ($entity instanceof CaseEntity) {
            $form = $formHandlerEvent->getForm();
            $channelField = $form->get(SyncWithZendeskExtension::SYNC_WITH_ZENDESK_FIELD);
            if (!$channelField) {
                return;
            }
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
