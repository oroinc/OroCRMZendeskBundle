<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\ZendeskBundle\Provider\Rest\Client\Guzzle\TokenRefreshHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Response;

class ZendGuzzleRestClientTest extends TestCase
{
    private TestableZendGuzzleRestClient $client;
    private GuzzleClient|MockObject $mockGuzzleClient;

    #[\Override]
    protected function setUp(): void
    {
        $this->client = new TestableZendGuzzleRestClient('https://test.zendesk.com');
        $this->mockGuzzleClient = $this->createMock(GuzzleClient::class);
        $this->client->setMockGuzzleClient($this->mockGuzzleClient);
    }

    private function mockResponse(int $statusCode = 200, array $data = ['success' => true]): void
    {
        $this->mockGuzzleClient->expects(self::once())
            ->method('send')
            ->willReturnCallback(fn ($request) => $this->captureAndRespond($request, $statusCode, $data));
    }

    private function mockExceptionThenSuccess(int $errorCode, array $successData = ['success' => true]): void
    {
        $this->mockGuzzleClient->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(function ($request) use ($errorCode, $successData) {
                static $callCount = 0;
                if (++$callCount === 1) {
                    $this->captureHeaders($request);
                    throw new ClientException('Error', $request, new GuzzleResponse($errorCode));
                }

                return $this->captureAndRespond($request, 200, $successData);
            });
    }

    private function captureAndRespond($request, int $statusCode, array $data): GuzzleResponse
    {
        $this->captureHeaders($request);

        return new GuzzleResponse($statusCode, [], json_encode($data));
    }

    private function captureHeaders($request): void
    {
        $headers = array_map(fn ($values) => $values[0] ?? '', $request->getHeaders());
        $this->client->captureRequestData($headers);
    }

    public function testRetainsCustomHeaders(): void
    {
        $this->mockResponse();
        $this->client->post('tickets', '{"data": "test"}', ['X-Custom' => 'value']);

        self::assertSame('value', $this->client->capturedHeaders['X-Custom']);
    }

    public function testRefreshesTokenOn401(): void
    {
        $handler = $this->createMock(TokenRefreshHandlerInterface::class);
        $handler->expects(self::once())->method('refreshToken')->willReturn('new-token');
        $this->client->setTokenRefreshHandler($handler);

        $this->mockExceptionThenSuccess(Response::HTTP_UNAUTHORIZED);
        $result = $this->client->get('users');

        self::assertNotNull($result);
        self::assertSame('Bearer new-token', $this->client->capturedAuthorizationHeader);
    }

    public function testDoesNotRefreshTokenOnNonUnauthorizedError(): void
    {
        $handler = $this->createMock(TokenRefreshHandlerInterface::class);
        $handler->expects(self::never())->method('refreshToken');
        $this->client->setTokenRefreshHandler($handler);

        $this->mockGuzzleClient->expects(self::once())
            ->method('send')
            ->willThrowException(new ClientException(
                'Not found',
                $this->createMock(RequestInterface::class),
                new GuzzleResponse(Response::HTTP_NOT_FOUND)
            ));

        $this->expectException(RestException::class);

        $this->client->get('users');
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testHandlesExceptionsWithoutTokenHandler(
        int $statusCode,
        ?TokenRefreshHandlerInterface $handler
    ): void {
        if ($handler !== null) {
            $this->client->setTokenRefreshHandler($handler);
            $this->client->setTokenRefreshHandler(null);
        }

        $this->mockGuzzleClient->expects(self::once())
            ->method('send')
            ->willThrowException(new ClientException(
                'Error',
                $this->createMock(RequestInterface::class),
                new GuzzleResponse($statusCode)
            ));

        $this->expectException(RestException::class);
        $this->client->get('users');
    }

    public function exceptionDataProvider(): array
    {
        return [
            '401 without handler' => [
                'statusCode' => 401,
                'handler' => null,
            ],
            '401 after removing handler' => [
                'statusCode' => 401,
                'handler' => $this->createMock(TokenRefreshHandlerInterface::class),
            ],
            '404 error' => [
                'statusCode' => 404,
                'handler' => null,
            ],
        ];
    }
}
