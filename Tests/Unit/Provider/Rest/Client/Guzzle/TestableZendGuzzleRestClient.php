<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use GuzzleHttp\Client as GuzzleClient;
use Oro\Bundle\ZendeskBundle\Provider\Rest\Client\Guzzle\ZendGuzzleRestClient;

/**
 * Testable version of ZendGuzzleRestClient that intercepts HTTP calls for unit testing.
 */
class TestableZendGuzzleRestClient extends ZendGuzzleRestClient
{
    public array $capturedHeaders = [];
    public ?string $capturedAuthorizationHeader = null;
    public int $requestCount = 0;

    private ?GuzzleClient $mockClient = null;

    public function __construct(string $baseUrl = '', array $defaultOptions = [])
    {
        parent::__construct($baseUrl, $defaultOptions);
    }

    public function setMockGuzzleClient(GuzzleClient $client): void
    {
        $this->mockClient = $client;
    }

    public function captureRequestData(array $headers): void
    {
        $this->capturedHeaders = $headers;
        $this->requestCount++;
    }

    public function performRequest(
        $method,
        $url,
        array $params = [],
        $data = null,
        array $headers = [],
        array $options = []
    ) {
        $result = parent::performRequest($method, $url, $params, $data, $headers, $options);

        if (isset($this->defaultOptions['headers']['Authorization'])) {
            $this->capturedAuthorizationHeader = $this->defaultOptions['headers']['Authorization'];
        }

        return $result;
    }

    protected function getGuzzleClient()
    {
        if ($this->mockClient !== null) {
            return $this->mockClient;
        }

        return parent::getGuzzleClient();
    }
}
