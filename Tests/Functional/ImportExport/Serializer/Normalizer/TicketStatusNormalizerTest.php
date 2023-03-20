<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;

class TicketStatusNormalizerTest extends WebTestCase
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

        $actual = $this->serializer->deserialize($data, TicketStatus::class, '');

        $this->assertEquals($expected, $actual);
    }

    public function denormalizeProvider(): array
    {
        return [
            'full' => [
                'data' => [
                    'name' => 'open',
                ],
                'expected' => new TicketStatus('open')
            ],
            'short' => [
                'data' => 'open',
                'expected' => new TicketStatus('open')
            ],
        ];
    }
}
