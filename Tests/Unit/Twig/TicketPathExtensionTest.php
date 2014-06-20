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
        $this->provider = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Provider\ConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new TicketPathExtension($this->provider);
    }

    public function testGetTicketViewPathReturnNullIfZendeskUrlConfigurationNotFound()
    {
        $ticket = $this->getMock('OroCRM\Bundle\ZendeskBundle\Entity\Ticket');
        $this->provider->expects($this->at(0))
            ->method('getZendeskUrl')
            ->will($this->returnValue(''));

        $this->provider->expects($this->at(1))
            ->method('getZendeskUrl')
            ->will($this->throwException(new ConfigurationException()));

        $url = $this->extension->getTicketViewPath($ticket);
        $this->assertNull($url);

        $url = $this->extension->getTicketViewPath($ticket);
        $this->assertNull($url);
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
        $this->provider->expects($this->once())
            ->method('getZendeskUrl')
            ->will($this->returnValue($zendeskUrl));

        $url = $this->extension->getTicketViewPath($ticket);
        $this->assertEquals($expectedUrl, $url);
    }
}
