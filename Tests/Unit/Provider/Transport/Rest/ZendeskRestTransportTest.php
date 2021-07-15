<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest;

use Oro\Bundle\ImportExportBundle\Serializer\SerializerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport as ZendeskTransportSettingsEntity;
use Oro\Bundle\ZendeskBundle\Form\Type\RestTransportSettingsFormType;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\InvalidRecordException;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\RestException;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestIterator;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestTransport;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ZendeskRestTransportTest extends \PHPUnit\Framework\TestCase
{
    private const TICKET_TYPE = Ticket::class;
    private const COMMENT_TYPE = TicketComment::class;
    private const USER_TYPE = User::class;

    /** @var RestClientFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $clientFactory;

    /** @var RestClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $client;

    /** @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $serializer;

    /** @var ZendeskRestTransport */
    protected $transport;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RestClientInterface::class);
        $this->clientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->transport = new ZendeskRestTransport($this->serializer);
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testGetUsersWorks(): void
    {
        $this->initTransport();
        $result = $this->transport->getUsers();

        $this->assertInstanceOf(ZendeskRestIterator::class, $result);

        $this->assertAttributeEquals($this->client, 'client', $result);
        $this->assertAttributeEquals('search.json', 'resource', $result);
        $this->assertAttributeEquals('results', 'dataKeyName', $result);

        $params = self::getObjectAttribute($result, 'params');
        $query = $params['query'] ?? '';
        $this->assertContains('type:user created<=', $query);
        $this->checkThatSearchQueryContainDateInCorrectFormat($query);
    }

    public function testGetUsersWorksWithLastUpdatedAt(): void
    {
        $this->initTransport();
        $datetime = '2017-07-05T21:35:36+000';
        $result = $this->transport->getUsers(new \DateTime($datetime, new \DateTimeZone('UTC')));
        $this->assertAttributeEquals(
            [
                'query' => 'type:user updated>=2017-07-05T21:35:36+0000',
                'sort_by' => 'created_at',
                'sort_order' => 'asc'
            ],
            'params',
            $result
        );
    }

    public function testGetTicketsWorks(): void
    {
        $this->initTransport();
        $result = $this->transport->getTickets();

        $this->assertInstanceOf(ZendeskRestIterator::class, $result);

        $this->assertAttributeEquals($this->client, 'client', $result);
        $this->assertAttributeEquals('search.json', 'resource', $result);
        $this->assertAttributeEquals('results', 'dataKeyName', $result);

        $params = self::getObjectAttribute($result, 'params');
        $query = $params['query'] ?? '';
        $this->assertContains('type:ticket created<=', $query);
        $this->checkThatSearchQueryContainDateInCorrectFormat($query);

        $this->assertAttributeEquals($this->serializer, 'serializer', $result);
        $this->assertAttributeEquals(
            'Oro\\Bundle\\ZendeskBundle\\Entity\\Ticket',
            'itemType',
            $result
        );
    }

    public function testGetTicketsWorksWithLastUpdatedAt(): void
    {
        $this->initTransport();
        $datetime = '2014-06-27T01:08:00+0000';
        $result = $this->transport->getTickets(new \DateTime($datetime, new \DateTimeZone('UTC')));
        $this->assertAttributeEquals(
            [
                'query' => 'type:ticket updated>=2014-06-27T01:08:00+0000',
                'sort_by' => 'created_at',
                'sort_order' => 'asc'
            ],
            'params',
            $result
        );
    }

    public function testGetTicketCommentsWorks(): void
    {
        $ticketId = 1;
        $this->initTransport();
        $result = $this->transport->getTicketComments($ticketId);

        $this->assertInstanceOf(ZendeskRestIterator::class, $result);

        $this->assertAttributeEquals($this->client, 'client', $result);
        $this->assertAttributeEquals('tickets/1/comments.json', 'resource', $result);
        $this->assertAttributeEquals('comments', 'dataKeyName', $result);
        $this->assertAttributeEquals([], 'params', $result);
        $this->assertAttributeEquals($this->serializer, 'serializer', $result);
        $this->assertAttributeEquals(TicketComment::class, 'itemType', $result);
        $this->assertAttributeEquals(['ticket_id' => $ticketId], 'deserializeContext', $result);
    }

    public function testGetTicketCommentsHandlesEmptyTicketId(): void
    {
        $ticketId = null;
        $this->initTransport();
        $result = $this->transport->getTicketComments($ticketId);
        $this->assertInstanceOf('EmptyIterator', $result);
    }

    /**
     * @dataProvider createUserProvider
     *
     * @param object $data
     * @param array $expectedSerializeValueMap
     * @param array $expectedDeserializeValueMap
     * @param array $expectedRequest
     * @param array $expectedResponse
     * @param array|null $expectedException
     * @param User|null $expectedResult
     *
     * @throws \Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException
     */
    public function testCreateUserWorks(
        object $data,
        array $expectedSerializeValueMap,
        array $expectedDeserializeValueMap,
        array $expectedRequest,
        array $expectedResponse,
        ?array $expectedException,
        ?User $expectedResult = null
    ): void {
        $this->initTransport();

        $this->mockSerializer($expectedSerializeValueMap, $expectedDeserializeValueMap);

        $response = $this->getMockResponse($expectedResponse);

        $this->client->expects($this->once())
            ->method('post')
            ->with($expectedRequest['resource'], $expectedRequest['data'])
            ->willReturn($response);

        if ($expectedException) {
            $this->expectException($expectedException['class']);
            $this->expectExceptionMessage($expectedException['message']);
        }

        $actualResult = $this->transport->createUser($data);
        if ($expectedResult) {
            $this->assertEquals($expectedResult, $actualResult);
        }
    }

    public function createUserProvider(): array
    {
        return [
            'Create user OK' => [
                'data' => $user = $this->createUser()->setName('John Doe'),
                'expectedSerializeValueMap' => [
                    [$user, 'json', [], json_encode($userData = ['name' => 'John Doe'])],
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $createdUserData = ['id' => 1, 'name' => 'Foo'],
                        self::USER_TYPE,
                        '',
                        [],
                        $createdUser = $this->createUser()->setOriginId(1)->setName('John Doe'),
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
                    [$user, 'json', [], json_encode($userData = ['name' => 'John Doe'])],
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
                    'class' => RestException::class,
                    'message' => 'Unsuccessful response: Can\'t get user data from response.'
                ]
            ],
            'Can\'t parse create user response' => [
                'data' => $user = $this->createUser()->setName('John Doe'),
                'expectedSerializeValueMap' => [
                    [$user, 'json', [], json_encode($userData = ['name' => 'John Doe'])],
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
                    'class' => RestException::class,
                    'message' => 'Unsuccessful response: Can\'t parse create user response.'
                ]
            ],
            'Validation errors' => [
                'data' => $user = $this->createUser()->setName('John Doe'),
                'expectedSerializeValueMap' => [
                    [$user, 'json', [], json_encode($userData = ['name' => 'John Doe'])],
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
                        InvalidRecordException::class,
                    'message' => 'Can\'t create user.' . PHP_EOL . 'Validation errors:' . PHP_EOL
                        . '[email] Email: can\'t be empty'
                ]
            ],
        ];
    }

    /**
     * @dataProvider createTicketProvider
     *
     * @param object $data
     * @param array $expectedSerializeValueMap
     * @param array $expectedDeserializeValueMap
     * @param array $expectedRequest
     * @param array $expectedResponse
     * @param array|null $expectedException
     * @param array|null $expectedResult
     *
     * @throws \Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException
     */
    public function testCreateTicketWorks(
        object $data,
        array $expectedSerializeValueMap,
        array $expectedDeserializeValueMap,
        array $expectedRequest,
        array $expectedResponse,
        ?array $expectedException,
        ?array $expectedResult = null
    ): void {
        $this->initTransport();

        $this->mockSerializer($expectedSerializeValueMap, $expectedDeserializeValueMap);

        $response = $this->getMockResponse($expectedResponse);

        $this->client->expects($this->once())
            ->method('post')
            ->with($expectedRequest['resource'], $expectedRequest['data'])
            ->willReturn($response);

        if ($expectedException) {
            $this->expectException($expectedException['class']);
            $this->expectExceptionMessage($expectedException['message']);
        }

        $actualResult = $this->transport->createTicket($data);
        if ($expectedResult) {
            $this->assertEquals($expectedResult, $actualResult);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createTicketProvider(): array
    {
        return [
            'Create ticket with comment OK' => [
                'data' => $ticket = $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        'json',
                        [],
                        json_encode(
                            $ticketData = [
                                'subject' => 'My printer is on fire!',
                                'comment' => ['body' => 'The smoke is very colorful!'],
                            ]
                        ),
                    ],
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $createdTicketData = [
                            'id' => 1,
                            'subject' => 'My printer is on fire!',
                            'description' => 'The smoke is very colorful!'
                        ],
                        self::TICKET_TYPE,
                        '',
                        [],
                        $createdTicket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                    ],
                    [
                        $createdCommentData = ['id' => 2, 'body' => 'The smoke is very colorful!'],
                        self::COMMENT_TYPE,
                        '',
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
                        'json',
                        [],
                        json_encode(
                            $ticketData = [
                                'subject' => 'My printer is on fire!',
                                'comment' => ['body' => 'The smoke is very colorful!'],
                            ]
                        ),
                    ],
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $createdTicketData = [
                            'id' => 1,
                            'subject' => 'My printer is on fire!',
                            'description' => 'The smoke is very colorful!'
                        ],
                        self::TICKET_TYPE,
                        '',
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
                    [$ticket, 'json', [], json_encode($ticketData = ['subject' => 'My printer is on fire!'])],
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
                    'class' => RestException::class,
                    'message' => 'Unsuccessful response: Can\'t get ticket data from response.'
                ]
            ],
            "Can't parse create ticket response" => [
                'data' => $ticket = $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [$ticket, 'json', [], json_encode($ticketData = ['subject' => 'My printer is on fire!'])],
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
                    'class' => RestException::class,
                    'message' => 'Unsuccessful response: Can\'t parse create ticket response.'
                ]
            ],
            'Validation errors' => [
                'data' => $ticket = $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [$ticket, 'json', [], json_encode($ticketData = ['subject' => 'My printer is on fire!'])],
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
                        InvalidRecordException::class,
                    'message' => 'Can\'t create ticket.' . PHP_EOL . 'Validation errors:' . PHP_EOL
                        . '[author_id] Author: can\'t be empty'
                ]
            ],
        ];
    }

    /**
     * @dataProvider updateTicketProvider
     *
     * @param object $data
     * @param array $expectedSerializeValueMap
     * @param array $expectedDeserializeValueMap
     * @param array|null $expectedRequest
     * @param array|null $expectedResponse
     * @param array|null $expectedException
     * @param Ticket|null $expectedResult
     *
     * @throws \Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException
     */
    public function testUpdateTicketWorks(
        object $data,
        array $expectedSerializeValueMap,
        array $expectedDeserializeValueMap,
        ?array $expectedRequest,
        ?array $expectedResponse,
        ?array $expectedException,
        ?Ticket $expectedResult = null
    ): void {
        $this->initTransport();

        $response = null;

        $this->mockSerializer($expectedSerializeValueMap, $expectedDeserializeValueMap);

        if ($expectedResponse) {
            $response = $this->getMockResponse($expectedResponse);
        }

        if ($expectedRequest && $response) {
            $this->client->expects($this->once())
                ->method('put')
                ->with($expectedRequest['resource'], $expectedRequest['data'])
                ->willReturn($response);
        }

        if ($expectedException) {
            $this->expectException($expectedException['class']);
            $this->expectExceptionMessage($expectedException['message']);
        }

        $actualResult = $this->transport->updateTicket($data);
        if ($expectedResult) {
            $this->assertEquals($expectedResult, $actualResult);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateTicketProvider(): array
    {
        return [
            'Update ticket OK' => [
                'data' => $ticket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        'json',
                        [],
                        json_encode($ticketData = ['subject' => 'My printer is on fire!']),
                    ],
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $updatedTicketData = [
                            'id' => 1,
                            'subject' => 'UPDATED',
                        ],
                        self::TICKET_TYPE,
                        '',
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
                'data' => $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => null,
                'expectedResponse' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Ticket must have "originId" value.'
                ]
            ],
            "Can't get ticket data from response" => [
                'data' => $ticket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        'json',
                        [],
                        json_encode($ticketData = ['subject' => 'My printer is on fire!']),
                    ],
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
                    'class' => RestException::class,
                    'message' => 'Unsuccessful response: Can\'t get ticket data from response.'
                ]
            ],
            'Can\'t parse update ticket response' => [
                'data' => $ticket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        'json',
                        [],
                        json_encode($ticketData = ['subject' => 'My printer is on fire!']),
                    ],
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
                    'class' => RestException::class,
                    'message' => 'Unsuccessful response: Can\'t parse update ticket response.'
                ]
            ],
            'Validation errors' => [
                'data' => $ticket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [
                    [
                        $ticket,
                        'json',
                        [],
                        json_encode($ticketData = ['subject' => 'My printer is on fire!']),
                    ],
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
                        InvalidRecordException::class,
                    'message' => 'Can\'t update ticket.' . PHP_EOL . 'Validation errors:' . PHP_EOL
                        . '[author_id] Author: can\'t be empty'
                ],
            ],
        ];
    }

    /**
     * @dataProvider addTicketCommentProvider
     *
     * @param object $data
     * @param array $expectedSerializeValueMap
     * @param array $expectedDeserializeValueMap
     * @param array|null $expectedRequest
     * @param array|null $expectedResponse
     * @param array|null $expectedException
     * @param TicketComment|array|null $expectedResult
     *
     * @throws \Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException
     */
    public function testAddTicketCommentWorks(
        object $data,
        array $expectedSerializeValueMap,
        array $expectedDeserializeValueMap,
        ?array $expectedRequest,
        ?array $expectedResponse,
        ?array $expectedException,
        $expectedResult = null
    ): void {
        $this->initTransport();

        $response = null;

        $this->mockSerializer($expectedSerializeValueMap, $expectedDeserializeValueMap);

        if ($expectedResponse) {
            $response = $this->getMockResponse($expectedResponse);
        }

        if ($expectedRequest && $response) {
            $this->client->expects($this->once())
                ->method('put')
                ->with($expectedRequest['resource'], $expectedRequest['data'])
                ->willReturn($response);

            $this->client->expects(self::any())
                ->method('getLastResponse')
                ->willReturn($response);
        }

        if ($expectedException) {
            $this->expectException($expectedException['class']);
            $this->expectExceptionMessage($expectedException['message']);
        }

        $actualResult = $this->transport->addTicketComment($data);
        if ($expectedResult) {
            $this->assertEquals($expectedResult, $actualResult);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function addTicketCommentProvider(): array
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
                        'json',
                        [],
                        json_encode($commentData = ['body' => 'The smoke is very colorful!']),
                    ],
                ],
                'expectedDeserializeValueMap' => [
                    [
                        $updatedCommentData = [
                            'id' => 1,
                            'body' => 'UPDATED',
                        ],
                        self::COMMENT_TYPE,
                        '',
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
                'data' => $this->createComment(),
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
                        'json',
                        [],
                        json_encode($commentData = ['body' => 'The smoke is very colorful!']),
                    ],
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
                    'class' => RestException::class,
                    'message' => 'Can\'t get comment data from response.',
                ],
            ],
        ];
    }

    public function testGetSettingsFormType(): void
    {
        self::assertSame(RestTransportSettingsFormType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN(): void
    {
        self::assertSame(ZendeskTransportSettingsEntity::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabel(): void
    {
        self::assertSame('oro.zendesk.transport.rest.label', $this->transport->getLabel());
    }

    /**
     * @param string $query
     */
    protected function checkThatSearchQueryContainDateInCorrectFormat($query): void
    {
        [, $rawDateTime] = explode('=', $query);

        /**
         * Check if string contains correct datetime format
         * If format isn't valid we will receive exception
         */
        new \DateTime($rawDateTime);
    }

    protected function initTransport(): void
    {
        $url = 'https://test.zendesk.com';
        $expectedUrl = $url . '/' . ZendeskRestTransport::API_URL_PREFIX;
        $email = 'admin@example.com';
        $token = 'ZsOcahXwCc6rcwLRsqQH27CPCTdpwM2FTfWHDpTBDZi4kBI5';
        $clientOptions = ['auth' => [$email . '/token', $token]];

        $settings = new ParameterBag(
            [
                'url' => $url,
                'email' => $email,
                'token' => $token,
            ]
        );

        $entity = $this->createMock(Transport::class);
        $entity->expects(self::atLeastOnce())
            ->method('getSettingsBag')
            ->willReturn($settings);
        $this->clientFactory->expects($this->once())
            ->method('createRestClient')
            ->with($expectedUrl, $clientOptions)
            ->willReturn($this->client);
        $this->transport->init($entity);
    }

    /**
     * @param array $expectedResponseData
     *
     * @return RestResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockResponse(array $expectedResponseData): RestResponseInterface
    {
        $response = $this->createMock(RestResponseInterface::class);

        if (isset($expectedResponseData['statusCode'])) {
            $response->expects($this->atLeastOnce())
                ->method('getStatusCode')
                ->willReturn($expectedResponseData['statusCode']);
        }

        if (isset($expectedResponseData['jsonData'])) {
            $response->expects($this->once())
                ->method('json')
                ->willReturn($expectedResponseData['jsonData']);
        }

        if (isset($expectedResponseData['jsonException'])) {
            $response->expects($this->once())
                ->method('json')
                ->willThrowException($expectedResponseData['jsonException']);
        }

        if (isset($expectedResponseData['isClientError'])) {
            $response->expects($this->once())
                ->method('isClientError')
                ->willReturn($expectedResponseData['isClientError']);
        }

        return $response;
    }

    private function mockSerializer(array $expectedSerializeValueMap, array $expectedDeserializeValueMap): void
    {
        if ($expectedSerializeValueMap) {
            $this->serializer->expects(self::exactly(count($expectedSerializeValueMap)))
                ->method('serialize')
                ->willReturnMap($expectedSerializeValueMap);
        }

        if ($expectedDeserializeValueMap) {
            $this->serializer->expects(self::exactly(count($expectedDeserializeValueMap)))
                ->method('deserialize')
                ->willReturnMap($expectedDeserializeValueMap);
        }
    }

    protected function createTicket(): Ticket
    {
        return new Ticket();
    }

    protected function createComment(): TicketComment
    {
        return new TicketComment();
    }

    protected function createUser(): User
    {
        return new User();
    }
}
