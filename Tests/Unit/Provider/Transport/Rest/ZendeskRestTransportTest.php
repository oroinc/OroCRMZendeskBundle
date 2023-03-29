<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest;

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
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ZendeskRestTransportTest extends \PHPUnit\Framework\TestCase
{
    private const API_URL_PREFIX = 'api/v2';
    private const COMMENT_EVENT_TYPE = 'Comment';

    /** @var RestClientFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $clientFactory;

    /** @var RestClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var ContextAwareNormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $normalizer;

    /** @var ContextAwareDenormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $denormalizer;

    /** @var ZendeskRestTransport */
    private $transport;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RestClientInterface::class);
        $this->clientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->normalizer = $this->createMock(ContextAwareNormalizerInterface::class);
        $this->denormalizer = $this->createMock(ContextAwareDenormalizerInterface::class);

        $this->transport = new ZendeskRestTransport(
            $this->normalizer,
            $this->denormalizer,
            ZendeskRestIteratorStub::class
        );
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testGetUsersWorks(): void
    {
        $this->initTransport();

        /** @var ZendeskRestIteratorStub $result */
        $result = $this->transport->getUsers();

        self::assertInstanceOf(ZendeskRestIteratorStub::class, $result);

        self::assertEquals($this->client, $result->xgetClient());
        self::assertEquals('users.json', $result->xgetResource());
        self::assertEquals('users', $result->xgetDataKeyName());

        $params = $result->xgetParams();
        $query = $params['query'] ?? '';
        self::assertStringContainsString('type:user created<=', $query);
        $this->checkThatSearchQueryContainDateInCorrectFormat($query);
    }

    public function testGetUsersWorksWithLastUpdatedAt(): void
    {
        $this->initTransport();
        $datetime = '2017-07-05T21:35:36+000';

        /** @var ZendeskRestIteratorStub $result */
        $result = $this->transport->getUsers(new \DateTime($datetime, new \DateTimeZone('UTC')));

        self::assertEquals(
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

        self::assertInstanceOf(ZendeskRestIterator::class, $result);

        self::assertEquals($this->client, $result->xgetClient());
        self::assertEquals('tickets.json', $result->xgetResource());
        self::assertEquals('tickets', $result->xgetDataKeyName());

        $params = $result->xgetParams();
        $query = $params['query'] ?? '';
        self::assertStringContainsString('type:ticket created<=', $query);
        $this->checkThatSearchQueryContainDateInCorrectFormat($query);

        self::assertEquals($this->denormalizer, $result->xgetDenormalizer());
        self::assertEquals(Ticket::class, $result->xgetItemType());
    }

    public function testGetTicketsWorksWithLastUpdatedAt(): void
    {
        $this->initTransport();
        $datetime = '2014-06-27T01:08:00+0000';

        /** @var ZendeskRestIteratorStub $result */
        $result = $this->transport->getTickets(new \DateTime($datetime, new \DateTimeZone('UTC')));
        self::assertEquals(
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

        self::assertInstanceOf(ZendeskRestIterator::class, $result);

        self::assertEquals($this->client, $result->xgetClient());
        self::assertEquals('tickets/1/comments.json', $result->xgetResource());
        self::assertEquals('comments', $result->xgetDataKeyName());
        self::assertEquals([], $result->xgetParams());
        self::assertEquals($this->denormalizer, $result->xgetDenormalizer());
        self::assertEquals(TicketComment::class, $result->xgetItemType());
        self::assertEquals(['ticket_id' => $ticketId], $result->xgetDenormalizeContext());
    }

    public function testGetTicketCommentsHandlesEmptyTicketId(): void
    {
        $ticketId = null;
        $this->initTransport();
        $result = $this->transport->getTicketComments($ticketId);
        self::assertInstanceOf('EmptyIterator', $result);
    }

    /**
     * @dataProvider createUserProvider
     */
    public function testCreateUserWorks(
        User $data,
        array $expectedNormalizeValueMap,
        array $expectedDenormalizeValueMap,
        array $expectedRequest,
        array $expectedResponse,
        ?array $expectedException,
        ?User $expectedResult = null
    ): void {
        $this->initTransport();

        $this->mockNormalizer($expectedNormalizeValueMap);
        $this->mockDenormalizer($expectedDenormalizeValueMap);

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
            self::assertEquals($expectedResult, $actualResult);
        }
    }

    public function createUserProvider(): array
    {
        return [
            'Create user OK' => [
                'data' => $user = $this->createUser()->setName('John Doe'),
                'expectedNormalizeValueMap' => [
                    [$user, null, [], $userData = ['name' => 'John Doe']],
                ],
                'expectedDenormalizeValueMap' => [
                    [
                        $createdUserData = ['id' => 1, 'name' => 'Foo'],
                        User::class,
                        null,
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
                'expectedNormalizeValueMap' => [
                    [$user, null, [], $userData = ['name' => 'John Doe']],
                ],
                'expectedDenormalizeValueMap' => [],
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
                'expectedNormalizeValueMap' => [
                    [$user, null, [], $userData = ['name' => 'John Doe']],
                ],
                'expectedDenormalizeValueMap' => [],
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
                'expectedNormalizeValueMap' => [
                    [$user, null, [], $userData = ['name' => 'John Doe']],
                ],
                'expectedDenormalizeValueMap' => [],
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
     */
    public function testCreateTicketWorks(
        Ticket $data,
        array $expectedNormalizeValueMap,
        array $expectedDenormalizeValueMap,
        array $expectedRequest,
        array $expectedResponse,
        ?array $expectedException,
        ?array $expectedResult = null
    ): void {
        $this->initTransport();

        $this->mockNormalizer($expectedNormalizeValueMap);
        $this->mockDenormalizer($expectedDenormalizeValueMap);

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
            self::assertEquals($expectedResult, $actualResult);
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
                'expectedNormalizeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = [
                            'subject' => 'My printer is on fire!',
                            'comment' => ['body' => 'The smoke is very colorful!'],
                        ],
                    ],
                ],
                'expectedDenormalizeValueMap' => [
                    [
                        $createdTicketData = [
                            'id' => 1,
                            'subject' => 'My printer is on fire!',
                            'description' => 'The smoke is very colorful!',
                        ],
                        Ticket::class,
                        null,
                        [],
                        $createdTicket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                    ],
                    [
                        $createdCommentData = ['id' => 2, 'body' => 'The smoke is very colorful!'],
                        TicketComment::class,
                        null,
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
                                array_merge($createdCommentData, ['type' => self::COMMENT_EVENT_TYPE]),
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
                'expectedNormalizeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = [
                            'subject' => 'My printer is on fire!',
                            'comment' => ['body' => 'The smoke is very colorful!'],
                        ],
                    ],
                ],
                'expectedDenormalizeValueMap' => [
                    [
                        $createdTicketData = [
                            'id' => 1,
                            'subject' => 'My printer is on fire!',
                            'description' => 'The smoke is very colorful!',
                        ],
                        Ticket::class,
                        null,
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
                'expectedNormalizeValueMap' => [
                    [$ticket, null, [], $ticketData = ['subject' => 'My printer is on fire!']],
                ],
                'expectedDenormalizeValueMap' => [],
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
                'expectedNormalizeValueMap' => [
                    [$ticket, null, [], $ticketData = ['subject' => 'My printer is on fire!']],
                ],
                'expectedDenormalizeValueMap' => [],
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
                'expectedNormalizeValueMap' => [
                    [$ticket, null, [], $ticketData = ['subject' => 'My printer is on fire!']],
                ],
                'expectedDenormalizeValueMap' => [],
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
     */
    public function testUpdateTicketWorks(
        Ticket $data,
        array $expectedNormalizeValueMap,
        array $expectedDenormalizeValueMap,
        ?array $expectedRequest,
        ?array $expectedResponse,
        ?array $expectedException,
        ?Ticket $expectedResult = null
    ): void {
        $this->initTransport();

        $response = null;

        $this->mockNormalizer($expectedNormalizeValueMap);
        $this->mockDenormalizer($expectedDenormalizeValueMap);

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
            self::assertEquals($expectedResult, $actualResult);
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
                'expectedNormalizeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = ['subject' => 'My printer is on fire!'],
                    ],
                ],
                'expectedDenormalizeValueMap' => [
                    [
                        $updatedTicketData = [
                            'id' => 1,
                            'subject' => 'UPDATED',
                        ],
                        Ticket::class,
                        null,
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
                'expectedNormalizeValueMap' => [],
                'expectedDenormalizeValueMap' => [],
                'expectedRequest' => null,
                'expectedResponse' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Ticket must have "originId" value.',
                ],
            ],
            "Can't get ticket data from response" => [
                'data' => $ticket = $this->createTicket()->setOriginId(1)->setSubject('My printer is on fire!'),
                'expectedNormalizeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = ['subject' => 'My printer is on fire!'],
                    ],
                ],
                'expectedDenormalizeValueMap' => [],
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
                'expectedNormalizeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = ['subject' => 'My printer is on fire!'],
                    ],
                ],
                'expectedDenormalizeValueMap' => [],
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
                'expectedNormalizeValueMap' => [
                    [
                        $ticket,
                        null,
                        [],
                        $ticketData = ['subject' => 'My printer is on fire!'],
                    ],
                ],
                'expectedDenormalizeValueMap' => [],
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
     */
    public function testAddTicketCommentWorks(
        TicketComment $data,
        array $expectedNormalizeValueMap,
        array $expectedDenormalizeValueMap,
        ?array $expectedRequest,
        ?array $expectedResponse,
        ?array $expectedException,
        TicketComment|array|null $expectedResult = null
    ): void {
        $this->initTransport();

        $response = null;

        $this->mockNormalizer($expectedNormalizeValueMap);
        $this->mockDenormalizer($expectedDenormalizeValueMap);

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
            self::assertEquals($expectedResult, $actualResult);
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
                'expectedNormalizeValueMap' => [
                    [
                        $comment,
                        null,
                        [],
                        $commentData = ['body' => 'The smoke is very colorful!'],
                    ],
                ],
                'expectedDenormalizeValueMap' => [
                    [
                        $updatedCommentData = [
                            'id' => 1,
                            'body' => 'UPDATED',
                        ],
                        TicketComment::class,
                        null,
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
                                array_merge($updatedCommentData, ['type' => self::COMMENT_EVENT_TYPE]),
                            ],
                        ],
                    ],
                ],
                'expectedException' => null,
                'expectedResult' => $updatedComment,
            ],
            'Data missing ticket id' => [
                'data' => $this->createComment(),
                'expectedNormalizeValueMap' => [],
                'expectedDenormalizeValueMap' => [],
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
                'expectedNormalizeValueMap' => [
                    [
                        $comment,
                        null,
                        [],
                        $commentData = ['body' => 'The smoke is very colorful!'],
                    ],
                ],
                'expectedDenormalizeValueMap' => [],
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
        $expectedUrl = $url . '/' . self::API_URL_PREFIX;
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

    private function getMockResponse(
        array $expectedResponseData
    ): RestResponseInterface|\PHPUnit\Framework\MockObject\MockObject {
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

    private function mockNormalizer(array $expectedNormalizeValueMap): void
    {
        if ($expectedNormalizeValueMap) {
            $this->normalizer->expects(self::exactly(count($expectedNormalizeValueMap)))
                ->method('normalize')
                ->willReturnMap($expectedNormalizeValueMap);
        }
    }

    private function mockDenormalizer(array $expectedDenormalizeValueMap): void
    {
        if ($expectedDenormalizeValueMap) {
            $this->denormalizer->expects(self::exactly(count($expectedDenormalizeValueMap)))
                ->method('denormalize')
                ->willReturnMap($expectedDenormalizeValueMap);
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
