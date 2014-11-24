<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\EventListener\Channel;

use OroCRM\Bundle\ZendeskBundle\EventListener\Channel\ChannelUpdateListener;
use OroCRM\Bundle\ZendeskBundle\Provider\ChannelType;

class ChannelUpdateListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChannelUpdateListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $syncManager;

    protected function setUp()
    {
        $this->syncManager = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Model\SyncManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new ChannelUpdateListener($this->syncManager);
    }

    /**
     * @dataProvider onUpdateDataProvider
     */
    public function testOnUpdate($data, $expectedRunSync = false)
    {
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $oldState = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $event = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Event\IntegrationUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getIntegration')
            ->will($this->returnValue($channel));
        $event->expects($this->any())
            ->method('getOldState')
            ->will($this->returnValue($oldState));
        if ($expectedRunSync) {
            $this->syncManager->expects($this->once())
                ->method('reverseSyncChannel')
                ->with($channel);
        } else {
            $this->syncManager->expects($this->never())
                ->method('reverseSyncChannel');
        }
        $channel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($data['type']));
        $settings = $this->getMock('\StdClass', array('offsetGetOr'));
        $settings->expects($this->any())
            ->method('offsetGetOr')
            ->will($this->returnValue($data['sync_enable']));
        $channel->expects($this->any())
            ->method('getSynchronizationSettings')
            ->will($this->returnValue($settings));
        $channel->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue($data['enable']));
        $settings = $this->getMock('\StdClass', array('offsetGetOr'));
        $settings->expects($this->any())
            ->method('offsetGetOr')
            ->will($this->returnValue($data['sync_enable_old']));
        $oldState->expects($this->any())
            ->method('getSynchronizationSettings')
            ->will($this->returnValue($settings));
        $oldState->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue($data['enable_old']));
        $this->listener->onUpdate($event);
    }

    public function onUpdateDataProvider()
    {
        return array(
            'incorrect type' => array(
                'data' => array(
                    'type' => 'incorrect_type',
                    'sync_enable' => true,
                    'enable' => true,
                    'sync_enable_old' => false,
                    'enable_old' => true
                )
            ),
            'two way sync not enabled' => array(
                'data' => array(
                    'type' => ChannelType::TYPE,
                    'sync_enable' => false,
                    'enable' => true,
                    'sync_enable_old' => false,
                    'enable_old' => false
                )
            ),
            'two way sync already enabled' => array(
                'data' => array(
                    'type' => ChannelType::TYPE,
                    'sync_enable' => true,
                    'enable' => true,
                    'sync_enable_old' => true,
                    'enable_old' => true
                )
            ),
            'two way sync already enabled but channel disabled' => array(
                'data' => array(
                    'type' => ChannelType::TYPE,
                    'sync_enable' => true,
                    'enable' => false,
                    'sync_enable_old' => false,
                    'enable_old' => true
                )
            ),
            'two way sync was disabled' => array(
                'data' => array(
                    'type' => ChannelType::TYPE,
                    'sync_enable' => true,
                    'enable' => true,
                    'sync_enable_old' => false,
                    'enable_old' => true
                ),
                'expectedRunSync' => true
            ),
            'two way sync already enabled but channel was disabled' => array(
                'data' => array(
                    'type' => ChannelType::TYPE,
                    'sync_enable' => true,
                    'enable' => true,
                    'sync_enable_old' => true,
                    'enable_old' => false
                ),
                'expectedRunSync' => true
            ),
        );
    }
}
