<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\EventListener\CaseEntity;

use OroCRM\Bundle\ZendeskBundle\EventListener\CaseEntity\CaseEntityListener;
use OroCRM\Bundle\ZendeskBundle\Form\Extension\SyncWithZendeskExtension;

class CaseFormHandledListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CaseEntityListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $syncManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $oroEntityProvider;

    protected function setUp()
    {
        $this->syncManager = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Model\SyncManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->oroEntityProvider = $this->getMockBuilder(
            'OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new CaseEntityListener($this->syncManager, $this->oroEntityProvider);
    }

    public function testBeforeSaveStartCommentJobIfEntityIsComment()
    {
        $formHandlerEvent = $this->getMockBuilder('OroCRM\Bundle\CaseBundle\Event\FormHandlerEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $entity = $this->getMock('OroCRM\Bundle\CaseBundle\Entity\CaseComment');
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
        $formHandlerEvent = $this->getMockBuilder('OroCRM\Bundle\CaseBundle\Event\FormHandlerEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $entity = $this->getMock('OroCRM\Bundle\CaseBundle\Entity\CaseEntity');
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $channelField = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $expectedChannel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
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
