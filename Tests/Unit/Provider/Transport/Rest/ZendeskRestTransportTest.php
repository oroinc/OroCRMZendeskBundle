<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest;

use OroCRM\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestTransport;

class ZendeskRestTransportTest extends \PHPUnit_Framework_TestCase
{
    const BASE_URL = 'https://test.zendesk.com/api/v2';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $clientFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var ZendeskRestTransport
     */
    protected $transport;

    protected function setUp()
    {
        $this->client = $this->getMock('Oro\\Bundle\\IntegrationBundle\\Provider\\Rest\\Client\\RestClientInterface');
        $this->clientFactory = $this->getMock(
            'Oro\\Bundle\\IntegrationBundle\\Provider\\Rest\\Client\\RestClientFactoryInterface'
        );

        $this->transport = new ZendeskRestTransport();
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testGetUsersWorks()
    {
        $this->initTransport();
        $result = $this->transport->getUsers();

        $this->assertInstanceOf(
            'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\ZendeskRestIterator',
            $result
        );

        $this->assertAttributeEquals($this->client, 'client', $result);
        $this->assertAttributeEquals('search.json', 'resource', $result);
        $this->assertAttributeEquals('results', 'dataKeyName', $result);
        $this->assertAttributeEquals(['query' => 'type:user'], 'params', $result);
    }

    public function testGetUsersWorksWithLastUpdatedAt()
    {
        $this->initTransport();
        $datetime = '2014-06-27T01:08:00+0400';
        $result = $this->transport->getUsers(new \DateTime($datetime));
        $this->assertAttributeEquals(['query' => 'type:user updated>' . $datetime], 'params', $result);
    }

    public function testGetTicketsWorks()
    {
        $this->initTransport();
        $result = $this->transport->getTickets();

        $this->assertInstanceOf(
            'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\ZendeskRestIterator',
            $result
        );

        $this->assertAttributeEquals($this->client, 'client', $result);
        $this->assertAttributeEquals('search.json', 'resource', $result);
        $this->assertAttributeEquals('results', 'dataKeyName', $result);
        $this->assertAttributeEquals(['query' => 'type:ticket'], 'params', $result);
    }

    public function testGetTicketsWorksWithLastUpdatedAt()
    {
        $this->initTransport();
        $datetime = '2014-06-27T01:08:00+0400';
        $result = $this->transport->getTickets(new \DateTime($datetime));
        $this->assertAttributeEquals(['query' => 'type:ticket updated>' . $datetime], 'params', $result);
    }

    public function testGetTicketCommentsWorks()
    {
        $ticketId = 1;
        $this->initTransport();
        $result = $this->transport->getTicketComments($ticketId);

        $this->assertInstanceOf('CallbackFilterIterator', $result);
        $iterator = $result->getInnerIterator();
        $this->assertInstanceOf(
            'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\ZendeskRestIterator',
            $iterator
        );

        $this->assertAttributeEquals($this->client, 'client', $iterator);
        $this->assertAttributeEquals('tickets/1/comments.json', 'resource', $iterator);
        $this->assertAttributeEquals('comments', 'dataKeyName', $iterator);
        $this->assertAttributeEquals([], 'params', $iterator);
    }

    public function testGetTicketCommentsHandlesEmptyTicketId()
    {
        $ticketId = null;
        $this->initTransport();
        $result = $this->transport->getTicketComments($ticketId);
        $this->assertInstanceOf('EmptyIterator', $result);
    }

    public function testGetTicketCommentsAddsTicketIdWorks()
    {
        $ticketId = 1;
        $responseComment = ['id' => 1];
        $expectedComment = ['id' => 1, 'ticket_id' => $ticketId];
        $this->initTransport();
        $result = $this->transport->getTicketComments($ticketId);

        $this->client->expects($this->once())
            ->method('get')
            ->with('tickets/1/comments.json')
            ->will(
                $this->returnValue(
                    [
                        'count' => 1,
                        'next_page' => null,
                        'previous_page' => null,
                        'comments' => [
                            $responseComment
                        ]
                    ]
                )
            );

        $result->rewind();
        $this->assertTrue($result->valid());
        $this->assertEquals($expectedComment, $result->current());
    }

    protected function initTransport()
    {
        $url = 'https://test.zendesk.com/api/v2';
        $email = 'admin@example.com';
        $token = 'ZsOcahXwCc6rcwLRsqQH27CPCTdpwM2FTfWHDpTBDZi4kBI5';
        $clientOptions = ['auth' => [$email . '/token', $token]];

        $settings = $this->getMock('Symfony\\Component\\HttpFoundation\\ParameterBag');
        $settings->expects($this->exactly(3))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['url', null, false, $url],
                        ['email', null, false, $email],
                        ['token', null, false, $token],
                    ]
                )
            );

        $entity = $this->getMock('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport');
        $entity->expects($this->atLeastOnce())
            ->method('getSettingsBag')
            ->will($this->returnValue($settings));
        $this->clientFactory->expects($this->once())
            ->method('createRestClient')
            ->with($url, $clientOptions)
            ->will($this->returnValue($this->client));
        $this->transport->init($entity);
    }
}
