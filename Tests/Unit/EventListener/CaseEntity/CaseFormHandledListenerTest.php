<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\EventListener\CaseEntity;

use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\CaseBundle\Event\FormHandlerEvent;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\EventListener\CaseEntity\CaseEntityListener;
use Oro\Bundle\ZendeskBundle\Form\Extension\SyncWithZendeskExtension;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\SyncManager;
use Symfony\Component\Form\Form;

class CaseFormHandledListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CaseEntityListener */
    private $listener;

    /** @var SyncManager|\PHPUnit\Framework\MockObject\MockObject */
    private $syncManager;

    /** @var OroEntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $oroEntityProvider;

    protected function setUp(): void
    {
        $this->syncManager = $this->createMock(SyncManager::class);
        $this->oroEntityProvider = $this->createMock(OroEntityProvider::class);

        $this->listener = new CaseEntityListener($this->syncManager, $this->oroEntityProvider);
    }

    public function testBeforeSaveStartCommentJobIfEntityIsComment()
    {
        $formHandlerEvent = $this->createMock(FormHandlerEvent::class);

        $entity = $this->createMock(CaseComment::class);
        $formHandlerEvent->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);
        $this->syncManager->expects($this->once())
            ->method('syncComment')
            ->with($entity);
        $this->listener->beforeSave($formHandlerEvent);
    }

    public function testBeforeSaveStartCommentJobIfEntityIsCase()
    {
        $expectedId = 42;
        $formHandlerEvent = $this->createMock(FormHandlerEvent::class);

        $entity = $this->createMock(CaseEntity::class);
        $form = $this->createMock(Form::class);
        $channelField = $this->createMock(Form::class);
        $expectedChannel = $this->createMock(Channel::class);
        $channelField->expects($this->once())
            ->method('getData')
            ->willReturn($expectedId);
        $form->expects($this->once())
            ->method('has')
            ->with(SyncWithZendeskExtension::ZENDESK_CHANNEL_FIELD)
            ->willReturn(true);
        $form->expects($this->once())
            ->method('get')
            ->with(SyncWithZendeskExtension::ZENDESK_CHANNEL_FIELD)
            ->willReturn($channelField);
        $formHandlerEvent->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);
        $formHandlerEvent->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $this->oroEntityProvider->expects($this->once())
            ->method('getChannelById')
            ->with($expectedId)
            ->willReturn($expectedChannel);
        $this->syncManager->expects($this->once())
            ->method('syncCase')
            ->with($entity, $expectedChannel);
        $this->listener->beforeSave($formHandlerEvent);
    }
}
