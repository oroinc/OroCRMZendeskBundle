<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestTransport;

class ZendeskRestTransportTest extends \PHPUnit_Framework_TestCase
{
    const TICKET_TYPE = 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket';
    const COMMENT_TYPE = 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment';
    const USER_TYPE = 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $clientFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;

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

        $this->serializer = $this->getMock('Symfony\\Component\\Serializer\\SerializerInterface');

        $this->transport = new ZendeskRestTransport($this->serializer);
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
        $expectedUpdated = '2014-06-26';
        $result = $this->transport->getUsers(new \DateTime($datetime));
        $this->assertAttributeEquals(['query' => 'type:user updated>' . $expectedUpdated], 'params', $result);
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
        $this->assertAttributeEquals($this->serializer, 'serializer', $result);
        $this->assertAttributeEquals(
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
            'itemType',
            $result
        );
    }

    public function testGetTicketsWorksWithLastUpdatedAt()
    {
        $this->initTransport();
        $datetime = '2014-06-27T01:08:00+0400';
        $expectedUpdated = '2014-06-26';
        $result = $this->transport->getTickets(new \DateTime($datetime));
        $this->assertAttributeEquals(['query' => 'type:ticket updated>' . $expectedUpdated], 'params', $result);
    }

    public function testGetTicketCommentsWorks()
    {
        $ticketId = 1;
        $this->initTransport();
        $result = $this->transport->getTicketComments($ticketId);

        $this->assertInstanceOf(
            'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\ZendeskRestIterator',
            $result
        );

        $this->assertAttributeEquals($this->client, 'client', $result);
        $this->assertAttributeEquals('tickets/1/comments.json', 'resource', $result);
        $this->assertAttributeEquals('comments', 'dataKeyName', $result);
        $this->assertAttributeEquals([], 'params', $result);
        $this->assertAttributeEquals($this->serializer, 'serializer', $result);
        $this->assertAttributeEquals(
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment',
            'itemType',
            $result
        );
        $this->assertAttributeEquals(
            ['ticket_id' => $ticketId],
            'deserializeContext',
            $result
        );
    }

    public function testGetTicketCommentsHandlesEmptyTicketId()
    {
        $ticketId = null;
        $this->initTransport();
        $result = $this->transport->getTicketComments($ticketId);
        $this->assertInstanceOf('EmptyIterator', $result);
    }

    /**
     * @dataProvider createUserProvider
     */
    public function testCreateUserWorks(
        $data,
        $expectedSerializeValueMap,
        $expectedDeserializeValueMap,
        $expectedRequest,
        $expectedResponse,
        $expectedException,
        $expectedResult = null
    ) {
        $this->initTransport();

        if ($expectedSerializeValueMap) {
            $this->serializer->expects($this->exactly(count($expectedSerializeValueMap)))
                ->method('serialize')
                ->will($this->returnValueMap($expectedSerializeValueMap));
        }

        if ($expectedDeserializeValueMap) {
            $this->serializer->expects($this->exactly(count($expectedDeserializeValueMap)))
                ->method('deserialize')
                ->will($this->returnValueMap($expectedDeserializeValueMap));
        }

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
                'data' => $user = $this->createUser()->setName('John Doe'),
                'expectedSerializeValueMap' => [
                    [$user, null, [], $userData = ['name' => 'John Doe']],
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $createdUserData = ['id' => 1, 'name' => 'Foo'],
                        self::USER_TYPE,
                        null,
                        [],
                        $createdUser = $this->createUser()->setOriginId(1)->setName('John Doe')
                    ],
                ],
                'expectedRequest' => [
                    'resource' => 'users.json',
                    'data' => ['user' => $userData],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonData' => ['user' => $createdUserData],
                ],
                'expectedException' => null,
                'expectedResult' => $createdUser,
            ],
            'Can\'t get user data from response' => [
                'data' => $user = $this->createUser()->setName('John Doe'),
                'expectedSerializeValueMap' => [
                    [$user, null, [], $userData = ['name' => 'John Doe']],
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'users.json',
                    'data' => ['user' => $userData],
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
                'data' => $user = $this->createUser()->setName('John Doe'),
                'expectedSerializeValueMap' => [
                    [$user, null, [], $userData = ['name' => 'John Doe']],
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'users.json',
                    'data' => ['user' => $userData],
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
                'data' => $user = $this->createUser()->setName('John Doe'),
                'expectedSerializeValueMap' => [
                    [$user, null, [], $userData = ['name' => 'John Doe']],
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'users.json',
                    'data' => ['user' => $userData],
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
        $expectedSerializeValueMap,
        $expectedDeserializeValueMap,
        $expectedRequest,
        $expectedResponse,
        $expectedException,
        $expectedResult = null
    ) {
        $this->initTransport();

        if ($expectedSerializeValueMap) {
            $this->serializer->expects($this->exactly(count($expectedSerializeValueMap)))
                ->method('serialize')
                ->will($this->returnValueMap($expectedSerializeValueMap));
        }

        if ($expectedDeserializeValueMap) {
            $this->serializer->expects($this->exactly(count($expectedDeserializeValueMap)))
                ->method('deserialize')
                ->will($this->returnValueMap($expectedDeserializeValueMap));
        }

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
                'data' => $ticket = $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = [
                            'subject' => 'My printer is on fire!',
                            'comment' => ['body' => 'The smoke is very colorful!']
                        ]
                    ]
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $createdTicketData = [
                            'id' => 1,
                            'subject' => 'My printer is on fire!',
                            'description' => 'The smoke is very colorful!'
                        ],
                        self::TICKET_TYPE,
                        null,
                        [],
                        $createdTicket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!')
                    ],
                    [
                        $createdCommentData = ['id' => 2, 'body' => 'The smoke is very colorful!'],
                        self::COMMENT_TYPE,
                        null,
                        [],
                        $createdComment = $this->createComment()->setOriginId(1)
                            ->setBody('The smoke is very colorful')
                    ]
                ],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => [
                        'ticket' => $ticketData
                    ],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonData' => [
                        'ticket' => $createdTicketData,
                        'audit' => [
                            'events' => [
                                array_merge($createdCommentData, ['type' => ZendeskRestTransport::COMMENT_EVENT_TYPE])
                            ]
                        ]
                    ]
                ],
                'expectedException' => null,
                'expectedResult' => [
                    'ticket' => $createdTicket,
                    'comment' => $createdComment,
                ]
            ],
            'Create ticket with empty comment OK' => [
                'data' => $ticket = $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = [
                            'subject' => 'My printer is on fire!',
                            'comment' => ['body' => 'The smoke is very colorful!']
                        ]
                    ]
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $createdTicketData = [
                            'id' => 1,
                            'subject' => 'My printer is on fire!',
                            'description' => 'The smoke is very colorful!'
                        ],
                        self::TICKET_TYPE,
                        null,
                        [],
                        $createdTicket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!')
                    ]
                ],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => ['ticket' => $ticketData],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonData' => ['ticket' => $createdTicketData],
                ],
                'expectedException' => null,
                'expectedResult' => [
                    'ticket' => $createdTicket,
                    'comment' => null,
                ]
            ],
            'Can\'t get ticket data from response' => [
                'data' => $ticket = $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [$ticket, null, [], $ticketData = ['subject' => 'My printer is on fire!']]
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => ['ticket' => $ticketData],
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
                'data' => $ticket = $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [$ticket, null, [], $ticketData = ['subject' => 'My printer is on fire!']]
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => ['ticket' => $ticketData],
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
                'data' => $ticket = $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [$ticket, null, [], $ticketData = ['subject' => 'My printer is on fire!']]
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => ['ticket' => $ticketData],
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
        $expectedSerializeValueMap,
        $expectedDeserializeValueMap,
        $expectedRequest,
        $expectedResponse,
        $expectedException,
        $expectedResult = null
    ) {
        $this->initTransport();

        $response = null;

        if ($expectedSerializeValueMap) {
            $this->serializer->expects($this->exactly(count($expectedSerializeValueMap)))
                ->method('serialize')
                ->will($this->returnValueMap($expectedSerializeValueMap));
        }

        if ($expectedDeserializeValueMap) {
            $this->serializer->expects($this->exactly(count($expectedDeserializeValueMap)))
                ->method('deserialize')
                ->will($this->returnValueMap($expectedDeserializeValueMap));
        }

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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateTicketProvider()
    {
        return [
            'Update ticket OK' => [
                'data' => $ticket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = ['subject' => 'My printer is on fire!']
                    ]
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $updatedTicketData = [
                            'id' => 1,
                            'subject' => 'UPDATED',
                        ],
                        self::TICKET_TYPE,
                        null,
                        [],
                        $updatedTicket = $this->createTicket()->setOriginId(1)->setSubject('UPDATED')
                    ]
                ],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => $ticketData],
                ],
                'expectedResponse' => [
                    'statusCode' => 200,
                    'jsonData' => [
                        'ticket' => $updatedTicketData
                    ],
                ],
                'expectedException' => null,
                'expectedResult' => $updatedTicket
            ],
            'Data missing id' => [
                'data' => $ticket = $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => null,
                'expectedResponse' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Ticket must have "originId" value.'
                ]
            ],
            'Can\'t get ticket data from response' => [
                'data' => $ticket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = ['subject' => 'My printer is on fire!']
                    ]
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => $ticketData],
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
            'Can\'t parse update ticket response' => [
                'data' => $ticket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = ['subject' => 'My printer is on fire!']
                    ]
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => $ticketData],
                ],
                'expectedResponse' => [
                    'statusCode' => 200,
                    'jsonException' => new \Exception(),
                ],
                'expectedException' => [
                    'class' => 'OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\Rest\\Exception\\RestException',
                    'message' => 'Unsuccessful response: Can\'t parse update ticket response.'
                ]
            ],
            'Validation errors' => [
                'data' => $ticket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = ['subject' => 'My printer is on fire!']
                    ]
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => $ticketData],
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
        $expectedSerializeValueMap,
        $expectedDeserializeValueMap,
        $expectedRequest,
        $expectedResponse,
        $expectedException,
        $expectedResult = null
    ) {
        $this->initTransport();

        $response = null;

        if ($expectedSerializeValueMap) {
            $this->serializer->expects($this->exactly(count($expectedSerializeValueMap)))
                ->method('serialize')
                ->will($this->returnValueMap($expectedSerializeValueMap));
        }

        if ($expectedDeserializeValueMap) {
            $this->serializer->expects($this->exactly(count($expectedDeserializeValueMap)))
                ->method('deserialize')
                ->will($this->returnValueMap($expectedDeserializeValueMap));
        }

        if ($expectedResponse) {
            $response = $this->getMockResponse($expectedResponse);
        }

        if ($expectedRequest && $response) {
            $this->client->expects($this->once())
                ->method('put')
                ->with($expectedRequest['resource'], $expectedRequest['data'])
                ->will($this->returnValue($response));

            $this->client->expects($this->any())
                ->method('getLastResponse')
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function addTicketCommentProvider()
    {
        return [
            'Add ticket comment OK' => [
                'data' => $comment = $this->createComment()
                    ->setOriginId(2)
                    ->setBody('The smoke is very colorful!')
                    ->setTicket($this->createTicket()->setOriginId(1)),
                'expectedSerializeValueMap' => [
                    [
                        $comment,
                        null,
                        [],
                        $commentData = ['body' => 'The smoke is very colorful!']
                    ]
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $updatedCommentData = [
                            'id' => 1,
                            'body' => 'UPDATED',
                        ],
                        self::COMMENT_TYPE,
                        null,
                        [],
                        $updatedComment = $this->createComment()->setOriginId(1)->setBody('UPDATED')
                    ]
                ],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => ['comment' => $commentData]],
                ],
                'expectedResponse' => [
                    'statusCode' => 200,
                    'jsonData' => [
                        'ticket' => [
                            'id' => 1,
                        ],
                        'audit' => [
                            'events' => [
                                array_merge($updatedCommentData, ['type' => ZendeskRestTransport::COMMENT_EVENT_TYPE])
                            ]
                        ]
                    ],
                ],
                'expectedException' => null,
                'expectedResult' => $updatedComment
            ],
            'Data missing ticket id' => [
                'data' => $comment = $this->createComment(),
                'expectedSerializeValueMap' => [],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => null,
                'expectedResponse' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Ticket comment data must have "ticket" with "originId" value'
                ]
            ],
            'Can\'t get comment data from response' => [
                'data' => $comment = $this->createComment()
                        ->setOriginId(2)
                        ->setBody('The smoke is very colorful!')
                        ->setTicket($this->createTicket()->setOriginId(1)),
                'expectedSerializeValueMap' => [
                    [
                        $comment,
                        null,
                        [],
                        $commentData = ['body' => 'The smoke is very colorful!']
                    ]
                ],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => ['comment' => $commentData]],
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

    protected function createTicket()
    {
        return new Ticket();
    }

    protected function createComment()
    {
        return new TicketComment();
    }

    protected function createUser()
    {
        return new User();
    }
}
