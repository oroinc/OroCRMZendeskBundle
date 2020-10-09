<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Twig;

use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Oro\Bundle\ZendeskBundle\Twig\ZendeskExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ZendeskExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ZendeskExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $oroProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $zendeskProvider;

    protected function setUp(): void
    {
        $this->oroProvider = $this->getMockBuilder(OroEntityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->zendeskProvider = $this->getMockBuilder(ZendeskEntityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $ticket = $this->createMock('Oro\Bundle\ZendeskBundle\Entity\Ticket');
        $ticket->expects($this->atLeastOnce())
            ->method('getOriginId')
            ->will($this->returnValue($id));
        $channel = $this->createMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $transport = $this->createMock('Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport');
        $transport->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue($zendeskUrl));
        $ticket->expects($this->atLeastOnce())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $channel->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));

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

        $ticket = $this->createMock('Oro\Bundle\ZendeskBundle\Entity\Ticket');
        $ticket->expects($this->atLeastOnce())
            ->method('getOriginId')
            ->will($this->returnValue($id));
        $channel = $this->createMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $transport = $this->createMock('Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport');
        $transport->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue($zendeskUrl));
        $ticket->expects($this->atLeastOnce())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $channel->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));

        $this->assertEquals(
            $expectedUrl,
            self::callTwigFunction($this->extension, 'oro_zendesk_ticket_url', [$ticket])
        );
    }
}
