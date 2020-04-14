<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\EventListener\CaseEntity;

use Oro\Bundle\ZendeskBundle\EventListener\CaseEntity\CaseEntityListener;
use Oro\Bundle\ZendeskBundle\Form\Extension\SyncWithZendeskExtension;

class CaseFormHandledListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CaseEntityListener
     */
    protected $listener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $syncManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $oroEntityProvider;

    protected function setUp(): void
    {
        $this->syncManager = $this->getMockBuilder('Oro\Bundle\ZendeskBundle\Model\SyncManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->oroEntityProvider = $this->getMockBuilder(
            'Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new CaseEntityListener($this->syncManager, $this->oroEntityProvider);
    }

    public function testBeforeSaveStartCommentJobIfEntityIsComment()
    {
        $formHandlerEvent = $this->getMockBuilder('Oro\Bundle\CaseBundle\Event\FormHandlerEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $entity = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseComment');
        $formHandlerEvent->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $this->syncManager->expects($this->once())
            ->method('syncComment')
            ->with($entity);
        $this->listener->beforeSave($formHandlerEvent);
    }

    public function testBeforeSaveStartCommentJobIfEntityIsCase()
    {
        $expectedId = 42;
        $formHandlerEvent = $this->getMockBuilder('Oro\Bundle\CaseBundle\Event\FormHandlerEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $entity = $this->createMock('Oro\Bundle\CaseBundle\Entity\CaseEntity');
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $channelField = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $expectedChannel = $this->createMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $channelField->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($expectedId));
        $form->expects($this->once())
            ->method('has')
            ->with(SyncWithZendeskExtension::ZENDESK_CHANNEL_FIELD)
            ->will($this->returnValue(true));
        $form->expects($this->once())
            ->method('get')
            ->with(SyncWithZendeskExtension::ZENDESK_CHANNEL_FIELD)
            ->will($this->returnValue($channelField));
        $formHandlerEvent->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $formHandlerEvent->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));
        $this->oroEntityProvider->expects($this->once())
            ->method('getChannelById')
            ->with($expectedId)
            ->will($this->returnValue($expectedChannel));
        $this->syncManager->expects($this->once())
            ->method('syncCase')
            ->with($entity, $expectedChannel);
        $this->listener->beforeSave($formHandlerEvent);
    }
}
