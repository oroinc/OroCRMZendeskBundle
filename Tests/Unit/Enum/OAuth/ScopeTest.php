<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Enum\OAuth;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\OAuth\Scope;
use Oro\Component\Config\Common\ConfigObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ScopeTest extends TestCase
{
    public function testFromTwoWaySyncReturnsReadWriteWhenEnabled(): void
    {
        self::assertSame(Scope::READ_WRITE, Scope::fromTwoWaySync(true));
    }

    public function testFromTwoWaySyncReturnsReadWhenDisabled(): void
    {
        self::assertSame(Scope::READ, Scope::fromTwoWaySync(false));
    }

    public function testFromTransportReturnsReadWriteWhenTwoWaySyncEnabled(): void
    {
        $scope = Scope::fromTransport($this->transportWithSettings(['isTwoWaySyncEnabled' => true]));

        self::assertSame(Scope::READ_WRITE, $scope);
    }

    public function testFromTransportReturnsReadWhenTwoWaySyncDisabled(): void
    {
        $scope = Scope::fromTransport($this->transportWithSettings(['isTwoWaySyncEnabled' => false]));

        self::assertSame(Scope::READ, $scope);
    }

    public function testFromTransportReturnsReadWhenSettingsAreMissing(): void
    {
        $scope = Scope::fromTransport($this->transportWithSettings([]));

        self::assertSame(Scope::READ, $scope);
    }

    public function testFromTransportReturnsReadWhenTransportChannelIsNull(): void
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::once())
            ->method('getChannel')
            ->willReturn(null);

        $scope = Scope::fromTransport($transport);

        self::assertSame(Scope::READ, $scope);
    }

    private function transportWithSettings(array $settings): ZendeskRestTransport&MockObject
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $channel = $this->createMock(Channel::class);

        $channel->expects(self::once())
            ->method('getSynchronizationSettings')
            ->willReturn(ConfigObject::create($settings));

        $transport->expects(self::once())
            ->method('getChannel')
            ->willReturn($channel);

        return $transport;
    }
}
