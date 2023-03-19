<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\EventListener\Channel;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\IntegrationUpdateEvent;
use Oro\Bundle\ZendeskBundle\EventListener\Channel\ChannelUpdateListener;
use Oro\Bundle\ZendeskBundle\Model\SyncManager;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;

class ChannelUpdateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChannelUpdateListener */
    private $listener;

    /** @var SyncManager|\PHPUnit\Framework\MockObject\MockObject */
    private $syncManager;

    protected function setUp(): void
    {
        $this->syncManager = $this->createMock(SyncManager::class);

        $this->listener = new ChannelUpdateListener($this->syncManager);
    }

    /**
     * @dataProvider onUpdateDataProvider
     */
    public function testOnUpdate($data, $expectedRunSync = false)
    {
        $channel = $this->createMock(Channel::class);
        $oldState = $this->createMock(Channel::class);
        $event = $this->createMock(IntegrationUpdateEvent::class);
        $event->expects($this->once())
            ->method('getIntegration')
            ->willReturn($channel);
        $event->expects($this->any())
            ->method('getOldState')
            ->willReturn($oldState);
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
            ->willReturn($data['type']);
        $settings = $this->getMockBuilder(\ArrayObject::class)
            ->addMethods(['offsetGetOr'])
            ->getMock();
        $settings->expects($this->any())
            ->method('offsetGetOr')
            ->willReturn($data['sync_enable']);
        $channel->expects($this->any())
            ->method('getSynchronizationSettings')
            ->willReturn($settings);
        $channel->expects($this->any())
            ->method('isEnabled')
            ->willReturn($data['enable']);
        $settings = $this->getMockBuilder(\ArrayObject::class)
            ->addMethods(['offsetGetOr'])
            ->getMock();
        $settings->expects($this->any())
            ->method('offsetGetOr')
            ->willReturn($data['sync_enable_old']);
        $oldState->expects($this->any())
            ->method('getSynchronizationSettings')
            ->willReturn($settings);
        $oldState->expects($this->any())
            ->method('isEnabled')
            ->willReturn($data['enable_old']);
        $this->listener->onUpdate($event);
    }

    public function onUpdateDataProvider(): array
    {
        return [
            'incorrect type' => [
                'data' => [
                    'type' => 'incorrect_type',
                    'sync_enable' => true,
                    'enable' => true,
                    'sync_enable_old' => false,
                    'enable_old' => true
                ]
            ],
            'two way sync not enabled' => [
                'data' => [
                    'type' => ChannelType::TYPE,
                    'sync_enable' => false,
                    'enable' => true,
                    'sync_enable_old' => false,
                    'enable_old' => false
                ]
            ],
            'two way sync already enabled' => [
                'data' => [
                    'type' => ChannelType::TYPE,
                    'sync_enable' => true,
                    'enable' => true,
                    'sync_enable_old' => true,
                    'enable_old' => true
                ]
            ],
            'two way sync already enabled but channel disabled' => [
                'data' => [
                    'type' => ChannelType::TYPE,
                    'sync_enable' => true,
                    'enable' => false,
                    'sync_enable_old' => false,
                    'enable_old' => true
                ]
            ],
            'two way sync was disabled' => [
                'data' => [
                    'type' => ChannelType::TYPE,
                    'sync_enable' => true,
                    'enable' => true,
                    'sync_enable_old' => false,
                    'enable_old' => true
                ],
                'expectedRunSync' => true
            ],
            'two way sync already enabled but channel was disabled' => [
                'data' => [
                    'type' => ChannelType::TYPE,
                    'sync_enable' => true,
                    'enable' => true,
                    'sync_enable_old' => true,
                    'enable_old' => false
                ],
                'expectedRunSync' => true
            ],
        ];
    }
}
