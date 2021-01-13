<?php

namespace Oro\Bundle\ZendeskBundle\Provider\Tests\Unit\Transport\Rest;

use Oro\Bundle\ImportExportBundle\Serializer\Encoder\DummyEncoder;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestIterator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class ZendeskRestIteratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RestClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var ZendeskRestIterator */
    private $iterator;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RestClientInterface::class);

        $normalizer = $this->createMock(DenormalizerInterface::class);
        $normalizer->expects($this->any())
            ->method('supportsDenormalization')
            ->willReturn(true);
        $normalizer->expects($this->any())
            ->method('denormalize')
            ->willReturnArgument(0);

        $serializer = new Serializer([$normalizer], [new DummyEncoder()]);

        $this->iterator = new ZendeskRestIterator($this->client, 'resource', 'rows', []);
        $this->iterator->setupDeserialization($serializer, '', []);
    }

    public function testCurrent(): void
    {
        $this->client->expects($this->once())
            ->method('getJSON')
            ->willReturn(
                [
                    'rows' => [
                        ['id' => 1],
                        ['id' => 2],
                    ],
                ]
            );

        $this->iterator->next();
        $this->assertEquals(['id' => 1], $this->iterator->current());

        $this->iterator->next();
        $this->assertEquals(['id' => 2], $this->iterator->current());
    }
}
