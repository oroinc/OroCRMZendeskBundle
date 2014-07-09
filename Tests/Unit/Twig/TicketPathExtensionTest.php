<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Twig;

use OroCRM\Bundle\ZendeskBundle\Twig\ZendeskExtension;

class ZendeskExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZendeskExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $oroProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $zendeskProvider;

    protected function setUp()
    {
        $this->oroProvider = $this->getMockBuilder(
            'OroCRM\\Bundle\\ZendeskBundle\\Model\\EntityProvider\\OroEntityProvider'
        )->disableOriginalConstructor()->getMock();
        $this->zendeskProvider = $this->getMockBuilder(
            'OroCRM\\Bundle\\ZendeskBundle\\Model\\EntityProvider\\ZendeskEntityProvider'
        )->disableOriginalConstructor()->getMock();

        $this->extension = new ZendeskExtension($this->oroProvider, $this->zendeskProvider);
    }

    public function testGetTicketUrl()
    {
        $id = 42;
        $zendeskUrl = 'https://test.zendesk.com';
        $expectedUrl = "{$zendeskUrl}/tickets/{$id}";

        $ticket = $this->getMock('OroCRM\Bundle\ZendeskBundle\Entity\Ticket');
        $ticket->expects($this->atLeastOnce())
            ->method('getOriginId')
            ->will($this->returnValue($id));
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $transport = $this->getMock('OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport');
        $transport->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue($zendeskUrl));
        $ticket->expects($this->atLeastOnce())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $channel->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        $url = $this->extension->getTicketUrl($ticket);
        $this->assertEquals($expectedUrl, $url);
    }
}
