<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;

class UserRoleNormalizerTest extends WebTestCase
{
    private Serializer $serializer;

    #[\Override]
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

        $actual = $this->serializer->deserialize($data, UserRole::class, '');

        $this->assertEquals($expected, $actual);
    }

    public function denormalizeProvider(): array
    {
        return [
            'full' => [
                'data' => [
                    'name' => 'agent',
                ],
                'expected' => new UserRole('agent')
            ],
            'short' => [
                'data' => 'end-user',
                'expected' => new UserRole('end-user')
            ],
        ];
    }
}
