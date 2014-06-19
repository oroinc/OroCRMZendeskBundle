<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;

class TicketStatusNormalizerTest extends WebTestCase
{
    /**
     * @var Serializer
     */
    protected $serializer;

    protected function setUp()
    {
        $this->initClient();
        $this->serializer = $this->getContainer()->get('oro_importexport.serializer');
    }

    /**
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize($data, $expected)
    {
        $actual = $this->serializer->deserialize($data, 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketStatus', null);

        $this->assertEquals($expected, $actual);
    }

    public function denormalizeProvider()
    {
        return array(
            'full' => array(
                'data' => array(
                    'name' => 'open',
                ),
                'expected' => new TicketStatus('open')
            ),
            'short' => array(
                'data' => 'open',
                'expected' => new TicketStatus('open')
            ),
        );
    }
}
