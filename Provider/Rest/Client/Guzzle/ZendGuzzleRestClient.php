<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extended Zendesk Guzzle HTTP client for handling automatic token refresh
 * on 401 Unauthorized responses.
 */
class ZendGuzzleRestClient extends GuzzleRestClient implements RestClientInterface
{
    private ?TokenRefreshHandlerInterface $tokenRefreshHandler = null;

    /**
     * @throws RestException
     */
    public function performRequest(
        $method,
        $url,
        array $params = [],
        $data = null,
        array $headers = [],
        array $options = []
    ) {
        try {
            $response = parent::performRequest($method, $url, $params, $data, $headers, $options);
        } catch (RestException $e) {
            if (false === $this->shouldRetryWithRefreshedToken($e->getCode())) {
                throw $e;
            }

            $this->setAccessToken($this->tokenRefreshHandler->refreshToken());
            $response = parent::performRequest($method, $url, $params, $data, $headers, $options);
        }

        return $response;
    }

    public function setTokenRefreshHandler(?TokenRefreshHandlerInterface $handler): void
    {
        $this->tokenRefreshHandler = $handler;
    }

    private function shouldRetryWithRefreshedToken(int $code): bool
    {
        return null !== $this->tokenRefreshHandler
            && Response::HTTP_UNAUTHORIZED === $code;
    }

    private function setAccessToken(string $token): void
    {
        $this->defaultOptions['headers'] ??= [];
        $this->defaultOptions['headers']['Authorization'] = 'Bearer ' . $token;
        unset($this->defaultOptions['auth']); // In case old authentication has been used
    }
}
