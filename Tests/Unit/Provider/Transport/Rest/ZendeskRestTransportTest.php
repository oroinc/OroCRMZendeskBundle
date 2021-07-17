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
use Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest\Stub\ZendeskRestIteratorStub;
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
    private RestClientFactoryInterface $clientFactory;

    /** @var RestClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private RestClientInterface $client;

    /** @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private SerializerInterface $serializer;

    private ZendeskRestTransport $transport;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RestClientInterface::class);
        $this->clientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->transport = new ZendeskRestTransport($this->serializer, ZendeskRestIteratorStub::class);
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testGetUsersWorks(): void
    {
        $this->initTransport();

        /** @var ZendeskRestIteratorStub $result */
        $result = $this->transport->getUsers();

        static::assertInstanceOf(ZendeskRestIteratorStub::class, $result);

        static::assertEquals($this->client, $result->xgetClient());
        static::assertEquals('search.json', $result->xgetResource());
        static::assertEquals('results', $result->xgetDataKeyName());

        $params = $result->xgetParams();
        $query = $params['query'] ?? '';
        static::assertStringContainsString('type:user created<=', $query);
        $this->checkThatSearchQueryContainDateInCorrectFormat($query);
    }

    public function testGetUsersWorksWithLastUpdatedAt(): void
    {
        $this->initTransport();
        $datetime = '2017-07-05T21:35:36+000';

        /** @var ZendeskRestIteratorStub $result */
        $result = $this->transport->getUsers(new \DateTime($datetime, new \DateTimeZone('UTC')));

        static::assertEquals(
            [
                'query' => 'type:user updated>=2017-07-05T21:35:36+0000',
                'sort_by' => 'created_at',
                'sort_order' => 'asc',
            ],
            $result->xgetParams()
        );
    }

    public function testGetTicketsWorks(): void
    {
        $this->initTransport();

        /** @var ZendeskRestIteratorStub $result */
        $result = $this->transport->getTickets();

        static::assertInstanceOf(ZendeskRestIterator::class, $result);

        static::assertEquals($this->client, $result->xgetClient());
        static::assertEquals('search.json', $result->xgetResource());
        static::assertEquals('results', $result->xgetDataKeyName());

        $params = $result->xgetParams();
        $query = $params['query'] ?? '';
        static::assertStringContainsString('type:ticket created<=', $query);
        $this->checkThatSearchQueryContainDateInCorrectFormat($query);

        static::assertEquals($this->serializer, $result->xgetSerializer());
        static::assertEquals(Ticket::class, $result->xgetItemType());
    }

    public function testGetTicketsWorksWithLastUpdatedAt(): void
    {
        $this->initTransport();
        $datetime = '2014-06-27T01:08:00+0000';

        /** @var ZendeskRestIteratorStub $result */
        $result = $this->transport->getTickets(new \DateTime($datetime, new \DateTimeZone('UTC')));
        static::assertEquals(
            [
                'query' => 'type:ticket updated>=2014-06-27T01:08:00+0000',
                'sort_by' => 'created_at',
                'sort_order' => 'asc',
            ],
            $result->xgetParams()
        );
    }

    public function testGetTicketCommentsWorks(): void
    {
        $ticketId = 1;
        $this->initTransport();

        /** @var ZendeskRestIteratorStub $result */
        $result = $this->transport->getTicketComments($ticketId);

        static::assertInstanceOf(ZendeskRestIterator::class, $result);

        static::assertEquals($this->client, $result->xgetClient());
        static::assertEquals('tickets/1/comments.json', $result->xgetResource());
        static::assertEquals('comments', $result->xgetDataKeyName());
        static::assertEquals([], $result->xgetParams());
        static::assertEquals($this->serializer, $result->xgetSerializer());
        static::assertEquals(TicketComment::class, $result->xgetItemType());
        static::assertEquals(['ticket_id' => $ticketId], $result->xgetDeserializeContext());
    }

    public function testGetTicketCommentsHandlesEmptyTicketId(): void
    {
        $ticketId = null;
        $this->initTransport();
        $result = $this->transport->getTicketComments($ticketId);
        static::assertInstanceOf('EmptyIterator', $result);
    }

    /**
     * @dataProvider createUserProvider
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

        $this->client->expects(self::once())
            ->method('post')
            ->with($expectedRequest['resource'], $expectedRequest['data'])
            ->willReturn($response);

        if ($expectedException) {
            $this->expectException($expectedException['class']);
            $this->expectExceptionMessage($expectedException['message']);
        }

        $actualResult = $this->transport->createUser($data);
        if ($expectedResult) {
            static::assertEquals($expectedResult, $actualResult);
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
            "Can't get user data from response" => [
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
                    'message' => "Unsuccessful response: Can't get user data from response.",
                ],
            ],
            "Can't parse create user response" => [
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
                    'message' => "Unsuccessful response: Can't parse create user response.",
                ],
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
                                ['description' => "Email: can't be empty"],
                            ],
                        ],
                        'description' => 'Validation errors',
                    ],
                ],
                'expectedException' => [
                    'class' =>
                        InvalidRecordException::class,
                    'message' => "Can't create user." . PHP_EOL . 'Validation errors:' . PHP_EOL
                        . "[email] Email: can't be empty",
                ],
            ],
        ];
    }

    /**
     * @dataProvider createTicketProvider
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

        $this->client->expects(self::once())
            ->method('post')
            ->with($expectedRequest['resource'], $expectedRequest['data'])
            ->willReturn($response);

        if ($expectedException) {
            $this->expectException($expectedException['class']);
            $this->expectExceptionMessage($expectedException['message']);
        }

        $actualResult = $this->transport->createTicket($data);
        if ($expectedResult) {
            static::assertEquals($expectedResult, $actualResult);
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
                            'description' => 'The smoke is very colorful!',
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
                            ->setBody('The smoke is very colorful'),
                    ],
                ],
                'expectedRequest' => [
                    'resource' => 'tickets.json',
                    'data' => [
                        'ticket' => $ticketData,
                    ],
                ],
                'expectedResponse' => [
                    'statusCode' => 201,
                    'jsonData' => [
                        'ticket' => $createdTicketData,
                        'audit' => [
                            'events' => [
                                array_merge($createdCommentData, ['type' => ZendeskRestTransport::COMMENT_EVENT_TYPE]),
                            ],
                        ],
                    ],
                ],
                'expectedException' => null,
                'expectedResult' => [
                    'ticket' => $createdTicket,
                    'comment' => $createdComment,
                ],
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
                            'description' => 'The smoke is very colorful!',
                        ],
                        self::TICKET_TYPE,
                        '',
                        [],
                        $createdTicket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                    ],
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
                ],
            ],
            "Can't get ticket data from response" => [
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
                    'message' => "Unsuccessful response: Can't get ticket data from response.",
                ],
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
                    'message' => "Unsuccessful response: Can't parse create ticket response.",
                ],
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
                                ['description' => "Author: can't be empty"],
                            ],
                        ],
                        'description' => 'Validation errors',
                    ],
                ],
                'expectedException' => [
                    'class' =>
                        InvalidRecordException::class,
                    'message' => "Can't create ticket." . PHP_EOL . 'Validation errors:' . PHP_EOL
                        . "[author_id] Author: can't be empty",
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateTicketProvider
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
            $this->client->expects(self::once())
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
            static::assertEquals($expectedResult, $actualResult);
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
                        $updatedTicket = $this->createTicket()->setOriginId(1)->setSubject('UPDATED'),
                    ],
                ],
                'expectedRequest' => [
                    'resource' => 'tickets/1.json',
                    'data' => ['ticket' => $ticketData],
                ],
                'expectedResponse' => [
                    'statusCode' => 200,
                    'jsonData' => [
                        'ticket' => $updatedTicketData,
                    ],
                ],
                'expectedException' => null,
                'expectedResult' => $updatedTicket,
            ],
            'Data missing id' => [
                'data' => $this->createTicket()->setSubject('My printer is on fire!'),
                'expectedSerializeValueMap' => [],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => null,
                'expectedResponse' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Ticket must have "originId" value.',
                ],
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
                    'message' => "Unsuccessful response: Can't get ticket data from response.",
                ],
            ],
            "Can't parse update ticket response" => [
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
                    'message' => "Unsuccessful response: Can't parse update ticket response.",
                ],
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
                                ['description' => "Author: can't be empty"],
                            ],
                        ],
                        'description' => 'Validation errors',
                    ],
                ],
                'expectedException' => [
                    'class' =>
                        InvalidRecordException::class,
                    'message' => "Can't update ticket." . PHP_EOL . 'Validation errors:' . PHP_EOL
                        . "[author_id] Author: can't be empty",
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
            $this->client->expects(self::once())
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
            static::assertEquals($expectedResult, $actualResult);
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
                        $updatedComment = $this->createComment()->setOriginId(1)->setBody('UPDATED'),
                    ],
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
                                array_merge($updatedCommentData, ['type' => ZendeskRestTransport::COMMENT_EVENT_TYPE]),
                            ],
                        ],
                    ],
                ],
                'expectedException' => null,
                'expectedResult' => $updatedComment,
            ],
            'Data missing ticket id' => [
                'data' => $this->createComment(),
                'expectedSerializeValueMap' => [],
                'expectedDeserializeValueMap' => [],
                'expectedRequest' => null,
                'expectedResponse' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Ticket comment data must have "ticket" with "originId" value',
                ],
            ],
            "Can't get comment data from response" => [
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
                    'message' => "Can't get comment data from response.",
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

    private function checkThatSearchQueryContainDateInCorrectFormat(string $query): void
    {
        [, $rawDateTime] = explode('=', $query);

        /**
         * Check if string contains correct datetime format
         * If format isn't valid we will receive exception
         */
        new \DateTime($rawDateTime);
    }

    private function initTransport(): void
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
        $this->clientFactory->expects(self::once())
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
    private function getMockResponse(array $expectedResponseData): RestResponseInterface
    {
        $response = $this->createMock(RestResponseInterface::class);

        if (isset($expectedResponseData['statusCode'])) {
            $response->expects(self::atLeastOnce())
                ->method('getStatusCode')
                ->willReturn($expectedResponseData['statusCode']);
        }

        if (isset($expectedResponseData['jsonData'])) {
            $response->expects(self::once())
                ->method('json')
                ->willReturn($expectedResponseData['jsonData']);
        }

        if (isset($expectedResponseData['jsonException'])) {
            $response->expects(self::once())
                ->method('json')
                ->willThrowException($expectedResponseData['jsonException']);
        }

        if (isset($expectedResponseData['isClientError'])) {
            $response->expects(self::once())
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

    private function createTicket(): Ticket
    {
        return new Ticket();
    }

    private function createComment(): TicketComment
    {
        return new TicketComment();
    }

    private function createUser(): User
    {
        return new User();
    }
}
