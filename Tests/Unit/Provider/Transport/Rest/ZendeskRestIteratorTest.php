<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestIterator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ZendeskRestIteratorTest extends \PHPUnit\Framework\TestCase
{
    private const DATA_KEY_NAME = 'results';
    private const RESOURCE = 'users';
    private const PARAMS = ['foo' => 'bar'];

    /** @var RestClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var ZendeskRestIterator */
    private $iterator;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RestClientInterface::class);

        $this->iterator = new ZendeskRestIterator(
            $this->client,
            self::RESOURCE,
            self::DATA_KEY_NAME,
            self::PARAMS
        );
    }

    public function testCurrent(): void
    {
        $this->client->expects(self::once())
            ->method('getJSON')
            ->with(self::RESOURCE, self::PARAMS)
            ->willReturn([self::DATA_KEY_NAME => [['id' => 1], ['id' => 2]]]);

        $itemType = 'testType';
        $denormalizeContext = ['key' => 'val'];
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects(self::exactly(2))
            ->method('denormalize')
            ->with(self::anything(), $itemType, '', $denormalizeContext)
            ->willReturnArgument(0);

        $this->iterator->setupDenormalization($denormalizer, $itemType, $denormalizeContext);

        $this->iterator->next();
        self::assertEquals(['id' => 1], $this->iterator->current());

        $this->iterator->next();
        self::assertEquals(['id' => 2], $this->iterator->current());
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorForeach(array $clientExpectations, array $expectedItems): void
    {
        $with = [];
        $will = [];
        foreach ($clientExpectations as $data) {
            $with[] = $data['request'];
            $will[] = $data['response'];
        }
        $this->client->expects(self::exactly(count($clientExpectations)))
            ->method('getJSON')
            ->withConsecutive(...$with)
            ->willReturnOnConsecutiveCalls(...$will);

        $actualItems = [];

        foreach ($this->iterator as $key => $value) {
            $actualItems[$key] = $value;
        }

        self::assertEquals($expectedItems, $actualItems);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorWhile(array $clientExpectations, array $expectedItems): void
    {
        $with = [];
        $will = [];
        foreach ($clientExpectations as $data) {
            $with[] = $data['request'];
            $will[] = $data['response'];
        }
        $this->client->expects(self::exactly(count($clientExpectations)))
            ->method('getJSON')
            ->withConsecutive(...$with)
            ->willReturnOnConsecutiveCalls(...$will);

        $actualItems = [];

        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        self::assertEquals($expectedItems, $actualItems);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIterateTwice(array $clientExpectations, array $expectedItems): void
    {
        $with = [];
        $will = [];
        foreach ($clientExpectations as $data) {
            $with[] = $data['request'];
            $will[] = $data['response'];
        }
        foreach ($clientExpectations as $data) {
            $with[] = $data['request'];
            $will[] = $data['response'];
        }
        $this->client->expects(self::exactly(count($clientExpectations) * 2))
            ->method('getJSON')
            ->withConsecutive(...$with)
            ->willReturnOnConsecutiveCalls(...$will);

        $actualItems = [];

        $this->iterator->rewind();
        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        self::assertEquals($expectedItems, $actualItems);

        $actualItems = [];

        $this->iterator->rewind();
        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        self::assertEquals($expectedItems, $actualItems);
    }

    public function iteratorDataProvider(): array
    {
        return [
            'two pages, 7 records' => [
                'clientExpectations' => [
                    [
                        'request' => [self::RESOURCE, self::PARAMS],
                        'response' => [
                            'count' => 7,
                            'next_page' => 'http://test.zendesk.com/api/v2/' . self::RESOURCE . '.json?page=2',
                            'previous_page' => null,
                            'results' => [
                                ['id' => 1],
                                ['id' => 2],
                                ['id' => 3],
                                ['id' => 4],
                            ],
                        ],
                    ],
                    [
                        'request' => ['http://test.zendesk.com/api/v2/' . self::RESOURCE . '.json?page=2'],
                        'response' => [
                            'count' => 7,
                            'next_page' => null,
                            'previous_page' => 'http://test.zendesk.com/api/v2/' . self::RESOURCE . '.json?page=1',
                            'results' => [
                                ['id' => 5],
                                ['id' => 6],
                                ['id' => 7],
                            ],
                        ],
                    ],
                ],
                'expectedItems' => [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 3],
                    ['id' => 4],
                    ['id' => 5],
                    ['id' => 6],
                    ['id' => 7],
                ],
            ],
            'no total count' => [
                'clientExpectations' => [
                    [
                        'request' => [self::RESOURCE, self::PARAMS],
                        'response' => [
                            'next_page' => 'http://test.zendesk.com/api/v2/' . self::RESOURCE . '.json?page=2',
                            'previous_page' => null,
                            'results' => [
                                ['id' => 1],
                                ['id' => 2],
                                ['id' => 3],
                                ['id' => 4],
                            ],
                        ],
                    ],
                    [
                        'request' => ['http://test.zendesk.com/api/v2/' . self::RESOURCE . '.json?page=2'],
                        'response' => [
                            'next_page' => null,
                            'previous_page' => 'http://test.zendesk.com/api/v2/' . self::RESOURCE . '.json?page=2',
                            'results' => [],
                        ],
                    ],
                ],
                'expectedItems' => [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 3],
                    ['id' => 4],
                ],
            ],
            'empty results' => [
                'clientExpectations' => [
                    [
                        'request' => [self::RESOURCE, self::PARAMS],
                        'response' => [
                            'next_page' => null,
                            'previous_page' => null,
                            'results' => [],
                        ],
                    ],
                ],
                'expectedItems' => [],
            ],
            'empty response' => [
                'clientExpectations' => [
                    [
                        'request' => [self::RESOURCE, self::PARAMS],
                        'response' => [],
                    ],
                ],
                'expectedItems' => [],
            ],
        ];
    }

    /**
     * @dataProvider countDataProvider
     */
    public function testCount(array $response, int $expectedCount): void
    {
        $this->client->expects(self::once())
            ->method('getJSON')
            ->with(self::RESOURCE, self::PARAMS)
            ->willReturn($response);

        self::assertEquals($expectedCount, $this->iterator->count());
    }

    public function countDataProvider(): array
    {
        return [
            'normal' => [
                'response' => [
                    'count' => 1777,
                    'next_page' => null,
                    'previous_page' => 'http://test.zendesk.com/api/v2/' . self::RESOURCE . '.json?page=1',
                    'results' => [
                        ['id' => 1],
                        ['id' => 2],
                        ['id' => 3],
                    ],
                ],
                'expectedCount' => 1777,
            ],
            'empty count' => [
                'response' => [
                    'next_page' => null,
                    'previous_page' => 'http://test.zendesk.com/api/v2/' . self::RESOURCE . '.json?page=1',
                    'results' => [
                        ['id' => 1],
                        ['id' => 2],
                        ['id' => 3],
                    ],
                ],
                'expectedCount' => 3,
            ],
            'empty response' => [
                'response' => [
                    'next_page' => null,
                    'previous_page' => 'http://test.zendesk.com/api/v2/' . self::RESOURCE . '.json?page=1',
                    'results' => [],
                ],
                'expectedCount' => 0,
            ],
        ];
    }
}
