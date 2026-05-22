<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\Repository\ZendeskRestTransportRepository;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;
use Oro\Bundle\ZendeskBundle\EventListener\Doctrine\TwoWaySyncChangeListener;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TwoWaySyncChangeListenerTest extends TestCase
{
    private const TRANSPORT_ID = 42;

    private TwoWaySyncChangeListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new TwoWaySyncChangeListener();
    }

    public function testOnFlushClearsTokensWhenTwoWaySyncStateChanges(): void
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::once())
            ->method('getAuthorizationType')
            ->willReturn(AuthorizationType::OAUTH);
        $transport->expects(self::once())
            ->method('getId')
            ->willReturn(self::TRANSPORT_ID);

        $channel = $this->createMock(Channel::class);
        $channel->expects(self::once())
            ->method('getType')
            ->willReturn(ChannelType::TYPE);
        $channel->expects(self::once())
            ->method('getTransport')
            ->willReturn($transport);

        $oldSettings = $this->settingsMock(false);
        $newSettings = $this->settingsMock(true);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$channel]);
        $uow->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($channel)
            ->willReturn(['synchronizationSettings' => [$oldSettings, $newSettings]]);

        $repository = $this->createMock(ZendeskRestTransportRepository::class);
        $repository->expects(self::once())
            ->method('clearOAuthTokensById')
            ->with(self::TRANSPORT_ID);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(ZendeskRestTransport::class)
            ->willReturn($repository);

        $this->listener->onFlush(new OnFlushEventArgs($em));
    }

    public function testOnFlushSkipsWhenTransportAuthorizationIsNotOAuth(): void
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::once())
            ->method('getAuthorizationType')
            ->willReturn(AuthorizationType::EMAIL_TOKEN);
        $transport->expects(self::never())
            ->method('getId');

        $channel = $this->createMock(Channel::class);
        $channel->expects(self::once())
            ->method('getType')
            ->willReturn(ChannelType::TYPE);
        $channel->expects(self::once())
            ->method('getTransport')
            ->willReturn($transport);

        $oldSettings = $this->settingsMock(false);
        $newSettings = $this->settingsMock(true);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$channel]);
        $uow->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($channel)
            ->willReturn(['synchronizationSettings' => [$oldSettings, $newSettings]]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $em->expects(self::never())
            ->method('getRepository');

        $this->listener->onFlush(new OnFlushEventArgs($em));
    }

    public function testOnFlushSkipsWhenTwoWaySyncStateDoesNotChange(): void
    {
        $channel = $this->createMock(Channel::class);
        $channel->expects(self::once())
            ->method('getType')
            ->willReturn(ChannelType::TYPE);
        $channel->expects(self::never())
            ->method('getTransport');

        $oldSettings = $this->settingsMock(true);
        $newSettings = $this->settingsMock(true);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$channel]);
        $uow->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($channel)
            ->willReturn(['synchronizationSettings' => [$oldSettings, $newSettings]]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $em->expects(self::never())
            ->method('getRepository');

        $this->listener->onFlush(new OnFlushEventArgs($em));
    }

    private function settingsMock(bool $isTwoWaySyncEnabled): MockObject
    {
        $settings = $this->getMockBuilder(\ArrayObject::class)
            ->addMethods(['offsetGetOr'])
            ->getMock();
        $settings->expects(self::once())
            ->method('offsetGetOr')
            ->with('isTwoWaySyncEnabled', false)
            ->willReturn($isTwoWaySyncEnabled);

        return $settings;
    }
}
