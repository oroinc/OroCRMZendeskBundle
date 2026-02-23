<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Twig;

use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Oro\Bundle\ZendeskBundle\Twig\ZendeskExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ZendeskExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private OroEntityProvider&MockObject $oroProvider;
    private ZendeskEntityProvider&MockObject $zendeskProvider;
    private ZendeskExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->oroProvider = $this->createMock(OroEntityProvider::class);
        $this->zendeskProvider = $this->createMock(ZendeskEntityProvider::class);

        $container = self::getContainerBuilder()
            ->add(OroEntityProvider::class, $this->oroProvider)
            ->add(ZendeskEntityProvider::class, $this->zendeskProvider)
            ->getContainer($this);

        $this->extension = new ZendeskExtension($container);
    }

    public function testGetEnabledTwoWaySyncChannels(): void
    {
        $channels = [$this->createMock(Channel::class)];

        $this->oroProvider->expects(self::once())
            ->method('getEnabledTwoWaySyncChannels')
            ->willReturn($channels);

        self::assertSame(
            $channels,
            self::callTwigFunction($this->extension, 'oro_zendesk_enabled_two_way_sync_channels', [])
        );
    }

    public function testGetTicketByCase(): void
    {
        $caseEntity = $this->createMock(CaseEntity::class);
        $ticket = $this->createMock(Ticket::class);

        $this->zendeskProvider->expects(self::once())
            ->method('getTicketByCase')
            ->with(self::identicalTo($caseEntity))
            ->willReturn($ticket);

        self::assertSame(
            $ticket,
            self::callTwigFunction($this->extension, 'oro_zendesk_ticket_by_related_case', [$caseEntity])
        );
    }

    public function testGetTicketUrl(): void
    {
        $id = 42;
        $zendeskUrl = 'https://test.zendesk.com';
        $expectedUrl = "{$zendeskUrl}/tickets/{$id}";

        $ticket = $this->createMock(Ticket::class);
        $ticket->expects($this->atLeastOnce())
            ->method('getOriginId')
            ->willReturn($id);
        $channel = $this->createMock(Channel::class);
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::once())
            ->method('getUrl')
            ->willReturn($zendeskUrl);
        $ticket->expects(self::atLeastOnce())
            ->method('getChannel')
            ->willReturn($channel);
        $channel->expects(self::once())
            ->method('getTransport')
            ->willReturn($transport);

        self::assertEquals(
            $expectedUrl,
            self::callTwigFunction($this->extension, 'oro_zendesk_ticket_url', [$ticket])
        );
    }

    public function testGetTicketUrlWithoutSchema(): void
    {
        $id = 42;
        $zendeskUrl = 'test.zendesk.com';
        $expectedUrl = "https://{$zendeskUrl}/tickets/{$id}";

        $ticket = $this->createMock(Ticket::class);
        $ticket->expects(self::atLeastOnce())
            ->method('getOriginId')
            ->willReturn($id);
        $channel = $this->createMock(Channel::class);
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::once())
            ->method('getUrl')
            ->willReturn($zendeskUrl);
        $ticket->expects(self::atLeastOnce())
            ->method('getChannel')
            ->willReturn($channel);
        $channel->expects(self::once())
            ->method('getTransport')
            ->willReturn($transport);

        self::assertEquals(
            $expectedUrl,
            self::callTwigFunction($this->extension, 'oro_zendesk_ticket_url', [$ticket])
        );
    }
}
