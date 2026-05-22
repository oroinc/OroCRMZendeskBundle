<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;

/**
 * Factory to create Zendesk Guzzle REST client used for integrations
 */
class ZendGuzzleRestClientFactory implements RestClientFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createRestClient($baseUrl, array $defaultOptions): RestClientInterface
    {
        return new ZendGuzzleRestClient($baseUrl, $defaultOptions);
    }
}
