<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Twig;

use OroCRM\Bundle\ZendeskBundle\Exception\ConfigurationException;
use OroCRM\Bundle\ZendeskBundle\Twig\TicketPathExtension;

class TicketPathExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TicketPathExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    protected function setUp()
    {
        $this->extension = new TicketPathExtension();
    }

    public function testGetTicketViewPath()
    {
        $id = 42;
        $zendeskUrl = 'https://test.zendesk.com';
        $expectedUrl = "{$zendeskUrl}/tickets/{$id}";

        $ticket = $this->getMock('OroCRM\Bundle\ZendeskBundle\Entity\Ticket');
        $ticket->expects($this->once())
            ->method('getOriginId')
            ->will($this->returnValue($id));
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $transport = $this->getMock('OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport');
        $transport->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue($zendeskUrl));
        $ticket->expects($this->once())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $channel->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        $url = $this->extension->getTicketViewPath($ticket);
        $this->assertEquals($expectedUrl, $url);
    }
}
