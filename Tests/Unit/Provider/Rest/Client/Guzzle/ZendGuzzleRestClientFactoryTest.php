<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\ZendeskBundle\Provider\Rest\Client\Guzzle\ZendGuzzleRestClient;
use Oro\Bundle\ZendeskBundle\Provider\Rest\Client\Guzzle\ZendGuzzleRestClientFactory;
use PHPUnit\Framework\TestCase;

class ZendGuzzleRestClientFactoryTest extends TestCase
{
    private ZendGuzzleRestClientFactory $factory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->factory = new ZendGuzzleRestClientFactory();
    }

    public function testCreatesZendGuzzleRestClient(): void
    {
        $client = $this->factory->createRestClient('https://test.zendesk.com', []);

        self::assertInstanceOf(ZendGuzzleRestClient::class, $client);
        self::assertInstanceOf(RestClientInterface::class, $client);
    }
}
