<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest;

use OroCRM\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestTransport;

class ZendeskRestTransportTest extends \PHPUnit_Framework_TestCase
{
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
            ->method('getJSON')
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

    /**
     * @dataProvider createUserProvider
     */
    public function testCreateUserWorks(
        $data,
        $expectedRequest,
        $expectedResponse,
        $expectedException,
        $expectedResult = null
    ) {
        $this->initTransport();

        $response = $this->getMockResponse($expectedResponse);

        $this->client->expects($this->once())
            ->method('post')
            ->with($expectedRequest['resource'], $expectedRequest['data'])
            ->will($this->returnValue($response));

        if ($expectedException) {
            $this->setExpectedException(
                $expectedException['class'],
                $expectedException['message']
            );
        }

        $actualResult = $this->transport->createUser($data);
        if ($expectedResult) {
            $this->assertEquals($expectedResult, $actualResult);
        }
    }

    public function createUserProvider()
    {
        return [
            'Create user OK' => [
                'data' => ['name' => 'John Doe'],
                'expectedRequest' => [
                    'resource' => 'users.json',
                    'data' => ['user' => ['name' => 'John Doe']],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonData' => ['user' => ['id' => 1, 'name' => 'John Doe']],
                ],
                'expectedException' => null,
                'expectedResult' => ['id' => 1, 'name' => 'John Doe']
            ],
            'Can\'t get user data from response' => [
                'data' => ['name' => 'John Doe'],
                'expectedRequest' => [
                    'resource' => 'users.json',
                    'data' => ['user' => ['name' => 'John Doe']],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonData' => [],
                ],
                'expectedException' => [
                    'class' => 'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\RestException',
                    'message' => 'Unsuccessful response: Can\'t get user data from response.'
                ]
            ],
            'Can\'t parse create user response' => [
                'data' => ['name' => 'John Doe'],
                'expectedRequest' => [
                    'resource' => 'users.json',
                    'data' => ['user' => ['name' => 'John Doe']],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonException' => new \Exception(),
                ],
                'expectedException' => [
                    'class' => 'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\RestException',
                    'message' => 'Unsuccessful response: Can\'t parse create user response.'
                ]
            ],
            'Validation errors' => [
                'data' => ['name' => 'John Doe'],
                'expectedRequest' => [
                    'resource' => 'users.json',
                    'data' => ['user' => ['name' => 'John Doe']],
                ],
                'expectedResponse' => [
                    'statusCode' => 400,
                    'isClientError' => true,
                    'jsonData' => [
                        'details' => [
                            'email' => [
                                ['description' => 'Email: can\'t be empty']
                            ]
                        ],
                        'description' => 'Validation errors'
                    ],
                ],
                'expectedException' => [
                    'class' =>
                        'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\InvalidRecordException',
                    'message' => 'Can\'t create user.' . PHP_EOL . 'Validation errors:' . PHP_EOL
                        . '[email] Email: can\'t be empty'
                ]
            ],
        ];
    }

    /**
     * @dataProvider createTicketProvider
     */
    public function testCreateTicketWorks(
        $data,
        $expectedRequest,
        $expectedResponse,
        $expectedException,
        $expectedResult = null
    ) {
        $this->initTransport();

        $response = $this->getMockResponse($expectedResponse);

        $this->client->expects($this->once())
            ->method('post')
            ->with($expectedRequest['resource'], $expectedRequest['data'])
            ->will($this->returnValue($response));

        if ($expectedException) {
            $this->setExpectedException(
                $expectedException['class'],
                $expectedException['message']
            );
        }

        $actualResult = $this->transport->createTicket($data);
        if ($expectedResult) {
            $this->assertEquals($expectedResult, $actualResult);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createTicketProvider()
    {
        return [
            'Create ticket with comment OK' => [
                'data' => [
                    'subject' => 'My printer is on fire!',
                    'comment' => ['body' => 'The smoke is very colorful!']
                ],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => [
                        'ticket' => [
                            'subject' => 'My printer is on fire!',
                            'comment' => ['body' => 'The smoke is very colorful!']
                        ]
                    ],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonData' => [
                        'ticket' => [
                            'id' => 1,
                            'subject' => 'My printer is on fire!',
                            'description' => ['body' => 'The smoke is very colorful!']
                        ],
                        'audit' => [
                            'events' => [
                                [
                                    'type' => ZendeskRestTransport::COMMENT_EVENT_TYPE,
                                    'id' => 2, 'body' => 'The smoke is very colorful!'
                                ]
                            ]
                        ]
                    ],
                ],
                'expectedException' => null,
                'expectedResult' => [
                    'ticket' => [
                        'id' => 1,
                        'subject' => 'My printer is on fire!',
                        'description' => ['body' => 'The smoke is very colorful!']
                    ],
                    'comment' => ['id' => 2, 'body' => 'The smoke is very colorful!'],
                ]
            ],
            'Create ticket with empty comment OK' => [
                'data' => ['subject' => 'My printer is on fire!'],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => ['ticket' => ['subject' => 'My printer is on fire!']],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonData' => ['ticket' => ['id' => 1, 'subject' => 'My printer is on fire!']],
                ],
                'expectedException' => null,
                'expectedResult' => [
                    'ticket' => ['id' => 1, 'subject' => 'My printer is on fire!'],
                    'comment' => null,
                ]
            ],
            'Can\'t get ticket data from response' => [
                'data' => ['subject' => 'My printer is on fire!'],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => ['ticket' => ['subject' => 'My printer is on fire!']],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonData' => [],
                ],
                'expectedException' => [
                    'class' => 'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\RestException',
                    'message' => 'Unsuccessful response: Can\'t get ticket data from response.'
                ]
            ],
            'Can\'t parse create ticket response' => [
                'data' => ['subject' => 'My printer is on fire!'],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => ['ticket' => ['subject' => 'My printer is on fire!']],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonException' => new \Exception(),
                ],
                'expectedException' => [
                    'class' => 'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\RestException',
                    'message' => 'Unsuccessful response: Can\'t parse create ticket response.'
                ]
            ],
            'Validation errors' => [
                'data' => ['subject' => 'My printer is on fire!'],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => ['ticket' => ['subject' => 'My printer is on fire!']],
                ],
                'expectedResponse' => [
                    'statusCode' => 400,
                    'isClientError' => true,
                    'jsonData' => [
                        'details' => [
                            'author_id' => [
                                ['description' => 'Author: can\'t be empty']
                            ]
                        ],
                        'description' => 'Validation errors'
                    ],
                ],
                'expectedException' => [
                    'class' =>
                        'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\InvalidRecordException',
                    'message' => 'Can\'t create ticket.' . PHP_EOL . 'Validation errors:' . PHP_EOL
                        . '[author_id] Author: can\'t be empty'
                ]
            ],
        ];
    }

    /**
     * @dataProvider updateTicketProvider
     */
    public function testUpdateTicketWorks(
        $data,
        $expectedRequest,
        $expectedResponse,
        $expectedException,
        $expectedResult = null
    ) {
        $this->initTransport();

        $response = null;

        if ($expectedResponse) {
            $response = $this->getMockResponse($expectedResponse);
        }

        if ($expectedRequest && $response) {
            $this->client->expects($this->once())
                ->method('put')
                ->with($expectedRequest['resource'], $expectedRequest['data'])
                ->will($this->returnValue($response));
        }

        if ($expectedException) {
            $this->setExpectedException(
                $expectedException['class'],
                $expectedException['message']
            );
        }

        $actualResult = $this->transport->updateTicket($data);
        if ($expectedResult) {
            $this->assertEquals($expectedResult, $actualResult);
        }
    }

    public function updateTicketProvider()
    {
        return [
            'Update ticket OK' => [
                'data' => ['id' => 1, 'subject' => 'My printer is on fire!'],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => ['subject' => 'My printer is on fire!']],
                ],
                'expectedResponse' => [
                    'statusCode' => 200,
                    'jsonData' => [
                        'ticket' => ['id' => 1, 'subject' => 'My printer is on fire!', 'updated' => true]
                    ],
                ],
                'expectedException' => null,
                'expectedResult' => ['id' => 1, 'subject' => 'My printer is on fire!', 'updated' => true]
            ],
            'Data missing id' => [
                'data' => ['subject' => 'My printer is on fire!'],
                'expectedRequest' => null,
                'expectedResponse' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Ticket data must have "id" value.'
                ]
            ],
            'Can\'t get ticket data from response' => [
                'data' => ['id' => 1, 'subject' => 'My printer is on fire!'],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => ['subject' => 'My printer is on fire!']],
                ],
                'expectedResponse' => [
                    'statusCode' => 200,
                    'jsonData' => [],
                ],
                'expectedException' => [
                    'class' => 'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\RestException',
                    'message' => 'Unsuccessful response: Can\'t get ticket data from response.'
                ]
            ],
            'Can\'t parse create ticket response' => [
                'data' => ['id' => 1, 'subject' => 'My printer is on fire!'],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => ['subject' => 'My printer is on fire!']],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonException' => new \Exception(),
                ],
                'expectedException' => [
                    'class' => 'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\RestException',
                    'message' => 'Unsuccessful response: Can\'t parse create ticket response.'
                ]
            ],
            'Validation errors' => [
                'data' => ['id' => 1, 'subject' => 'My printer is on fire!'],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => ['subject' => 'My printer is on fire!']],
                ],
                'expectedResponse' => [
                    'statusCode' => 400,
                    'isClientError' => true,
                    'jsonData' => [
                        'details' => [
                            'author_id' => [
                                ['description' => 'Author: can\'t be empty']
                            ]
                        ],
                        'description' => 'Validation errors'
                    ],
                ],
                'expectedException' => [
                    'class' =>
                        'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\InvalidRecordException',
                    'message' => 'Can\'t update ticket.' . PHP_EOL . 'Validation errors:' . PHP_EOL
                        . '[author_id] Author: can\'t be empty'
                ]
            ],
        ];
    }

    /**
     * @dataProvider addTicketCommentProvider
     */
    public function testAddTicketCommentWorks(
        $data,
        $expectedRequest,
        $expectedResponse,
        $expectedException,
        $expectedResult = null
    ) {
        $this->initTransport();

        $response = null;

        if ($expectedResponse) {
            $response = $this->getMockResponse($expectedResponse);
        }

        if ($expectedRequest && $response) {
            $this->client->expects($this->once())
                ->method('put')
                ->with($expectedRequest['resource'], $expectedRequest['data'])
                ->will($this->returnValue($response));
        }

        if ($expectedException) {
            $this->setExpectedException(
                $expectedException['class'],
                $expectedException['message']
            );
        }

        $actualResult = $this->transport->addTicketComment($data);
        if ($expectedResult) {
            $this->assertEquals($expectedResult, $actualResult);
        }
    }

    public function addTicketCommentProvider()
    {
        return [
            'Add ticket comment OK' => [
                'data' => ['ticket_id' => 1, 'body' => 'The smoke is very colorful!'],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => ['comment' => ['body' => 'The smoke is very colorful!']]],
                ],
                'expectedResponse' => [
                    'statusCode' => 200,
                    'jsonData' => [
                        'ticket' => [
                            'id' => 1,
                        ],
                        'audit' => [
                            'events' => [
                                [
                                    'type' => ZendeskRestTransport::COMMENT_EVENT_TYPE,
                                    'id' => 2, 'body' => 'The smoke is very colorful!'
                                ]
                            ]
                        ]
                    ],
                ],
                'expectedException' => null,
                'expectedResult' => [
                    'id' => 2,
                    'body' => 'The smoke is very colorful!',
                ]
            ],
            'Data missing ticket_id' => [
                'data' => ['body' => 'The smoke is very colorful!'],
                'expectedRequest' => null,
                'expectedResponse' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Ticket comment data must have "ticket_id" value.'
                ]
            ],
            'Can\'t get comment data from response' => [
                'data' => ['ticket_id' => 1, 'body' => 'The smoke is very colorful!'],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => ['comment' => ['body' => 'The smoke is very colorful!']]],
                ],
                'expectedResponse' => [
                    'statusCode' => 200,
                    'jsonData' => [
                        'ticket' => ['id' => 1],
                    ],
                ],
                'expectedException' => [
                    'class' => 'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\RestException',
                    'message' => 'Can\'t get comment data from response.',
                ],
            ],
        ];
    }

    protected function initTransport()
    {
        $url = 'https://test.zendesk.com';
        $expectedUrl = $url . '/' . ZendeskRestTransport::API_URL_PREFIX;
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
            ->with($expectedUrl, $clientOptions)
            ->will($this->returnValue($this->client));
        $this->transport->init($entity);
    }

    protected function getMockResponse(array $expectedResponseData)
    {
        $response = $this->getMock('Oro\\Bundle\\IntegrationBundle\\Provider\\Rest\\Client\\RestResponseInterface');

        if (isset($expectedResponseData['statusCode'])) {
            $response->expects($this->atLeastOnce())
                ->method('getStatusCode')
                ->will($this->returnValue($expectedResponseData['statusCode']));
        }

        if (isset($expectedResponseData['jsonData'])) {
            $response->expects($this->once())
                ->method('json')
                ->will($this->returnValue($expectedResponseData['jsonData']));
        }

        if (isset($expectedResponseData['jsonException'])) {
            $response->expects($this->once())
                ->method('json')
                ->will($this->throwException($expectedResponseData['jsonException']));
        }

        if (isset($expectedResponseData['isClientError'])) {
            $response->expects($this->once())
                ->method('isClientError')
                ->will($this->returnValue($expectedResponseData['isClientError']));
        }

        return $response;
    }
}
