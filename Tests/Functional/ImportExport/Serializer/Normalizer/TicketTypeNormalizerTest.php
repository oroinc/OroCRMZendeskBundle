<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;

class TicketTypeNormalizerTest extends WebTestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->initClient();
        $this->serializer = $this->getContainer()->get('oro_importexport.serializer');
    }

    /**
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize($data, $expected)
    {
        $this->markTestSkipped('CRM-8206');

        $actual = $this->serializer->deserialize($data, TicketType::class, '');

        $this->assertEquals($expected, $actual);
    }

    public function denormalizeProvider(): array
    {
        return [
            'full' => [
                'data' => [
                    'name' => 'task',
                ],
                'expected' => new TicketType('task')
            ],
            'short' => [
                'data' => 'task',
                'expected' => new TicketType('task')
            ],
        ];
    }
}
