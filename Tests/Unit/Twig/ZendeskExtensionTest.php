<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Twig;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Oro\Bundle\ZendeskBundle\Twig\ZendeskExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ZendeskExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ZendeskExtension */
    private $extension;

    /** @var OroEntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $oroProvider;

    /** @var ZendeskEntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $zendeskProvider;

    protected function setUp(): void
    {
        $this->oroProvider = $this->createMock(OroEntityProvider::class);
        $this->zendeskProvider = $this->createMock(ZendeskEntityProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_zendesk.entity_provider.oro', $this->oroProvider)
            ->add('oro_zendesk.entity_provider.zendesk', $this->zendeskProvider)
            ->getContainer($this);

        $this->extension = new ZendeskExtension($container);
    }

    public function testGetTicketUrl()
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
        $transport->expects($this->once())
            ->method('getUrl')
            ->willReturn($zendeskUrl);
        $ticket->expects($this->atLeastOnce())
            ->method('getChannel')
            ->willReturn($channel);
        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);

        $this->assertEquals(
            $expectedUrl,
            self::callTwigFunction($this->extension, 'oro_zendesk_ticket_url', [$ticket])
        );
    }

    public function testGetTicketUrlWithoutSchema()
    {
        $id = 42;
        $zendeskUrl = 'test.zendesk.com';
        $expectedUrl = "https://{$zendeskUrl}/tickets/{$id}";

        $ticket = $this->createMock(Ticket::class);
        $ticket->expects($this->atLeastOnce())
            ->method('getOriginId')
            ->willReturn($id);
        $channel = $this->createMock(Channel::class);
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects($this->once())
            ->method('getUrl')
            ->willReturn($zendeskUrl);
        $ticket->expects($this->atLeastOnce())
            ->method('getChannel')
            ->willReturn($channel);
        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);

        $this->assertEquals(
            $expectedUrl,
            self::callTwigFunction($this->extension, 'oro_zendesk_ticket_url', [$ticket])
        );
    }
}
