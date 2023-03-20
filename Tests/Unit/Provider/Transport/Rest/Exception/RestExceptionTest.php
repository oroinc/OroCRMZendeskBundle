<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest\Exception;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\InvalidRecordException;

class RestExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider exceptionDataProvider
     */
    public function testCreateFromResponseWorks(
        string $expectedMessage,
        array $expectedValidationErrors,
        ?string $message,
        array $jsonData,
        bool $jsonException,
        int $statusCode
    ) {
        $previous = new \Exception();

        $response = $this->createMock(RestResponseInterface::class);

        if ($jsonException) {
            $response->expects($this->once())
                ->method('json')
                ->willThrowException(new \Exception());
        } else {
            $response->expects($this->once())
                ->method('json')
                ->willReturn($jsonData);
        }

        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $exception = InvalidRecordException::createFromResponse($response, $message, $previous);

        $this->assertInstanceOf(
            InvalidRecordException::class,
            $exception
        );
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($response, $exception->getResponse());
        $this->assertSame($expectedValidationErrors, $exception->getValidationErrors());
        $this->assertEquals($statusCode, $exception->getCode());
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    public function exceptionDataProvider(): array
    {
        return [
            'response with expected errors format' => [
                'expectedMessage' => 'Record validation errors:' . PHP_EOL
                    . '[name] Name: is too short (minimum one character)' . PHP_EOL
                    . '[name] Name: can\'t be empty' . PHP_EOL
                    . '[email] Email: invalid email',
                'expectedValidationErrors' => [
                    'name' => ['Name: is too short (minimum one character)', 'Name: can\'t be empty'],
                    'email' => ['Email: invalid email'],
                ],
                'message' => null,
                'jsonData' => [
                    'details' => [
                        'name' => [
                            ['description' => 'Name: is too short (minimum one character)'],
                            ['description' => 'Name: can\'t be empty'],
                        ],
                        'email' => [
                            ['description' => 'Email: invalid email']
                        ],
                    ],
                ],
                'jsonException' => false,
                'statusCode' => 400,
            ],
            'response with unexpected errors format' => [
                'expectedMessage' => 'Record validation errors:' . PHP_EOL
                    . '[name] Name: is too short (minimum one character)' . PHP_EOL
                    . '[email] Email: invalid email' . PHP_EOL
                    . '[notes] {"data":"Notes: cannot be empty"}',
                'expectedValidationErrors' => [
                    'name' => ['Name: is too short (minimum one character)'],
                    'email' => ['Email: invalid email'],
                    'notes' => ['{"data":"Notes: cannot be empty"}'],
                ],
                'message' => null,
                'jsonData' => [
                    'details' => [
                        'name' => 'Name: is too short (minimum one character)',
                        'email' => ['Email: invalid email'],
                        'notes' => [
                            ['data' => 'Notes: cannot be empty']
                        ],
                    ],
                ],
                'jsonException' => false,
                'statusCode' => 400,
            ],
            'with json parse error' => [
                'expectedMessage' => 'Record invalid error.',
                'expectedValidationErrors' => [],
                'message' => null,
                'jsonData' => [],
                'jsonException' => true,
                'statusCode' => 400,
            ],
            'with custom message' => [
                'expectedMessage' => 'Can\'t create user.' . PHP_EOL . 'Details:' . PHP_EOL
                    . '[name] Name: is too short (minimum one character)',
                'expectedValidationErrors' => [
                    'name' => ['Name: is too short (minimum one character)'],
                ],
                'message' => 'Can\'t create user.',
                'jsonData' => [
                    'details' => ['name' => [['description' => 'Name: is too short (minimum one character)']]],
                    'description' => 'Details'
                ],
                'jsonException' => false,
                'statusCode' => 400,
            ],
            'with default message from description' => [
                'expectedMessage' => 'Some error message',
                'expectedValidationErrors' => [],
                'message' => null,
                'jsonData' => [
                    'description' => 'Some error message'
                ],
                'jsonException' => false,
                'statusCode' => 400,
            ],
        ];
    }
}
