<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Handler;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ZendeskBundle\Handler\TicketCommentExceptionHandler;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\InvalidRecordException;

class TicketCommentExceptionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TicketCommentExceptionHandler */
    private $exceptionHandler;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ContextInterface::class);

        $this->exceptionHandler = new TicketCommentExceptionHandler();
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testProcess(\Exception $exception, int $expectedIncrementErrorEntriesCount, bool $expected)
    {
        $this->context->expects($this->exactly($expectedIncrementErrorEntriesCount))
             ->method('incrementErrorEntriesCount');

        $this->assertEquals(
            $this->exceptionHandler->process($exception, $this->context),
            $expected
        );
    }

    public function exceptionDataProvider(): array
    {
        return [
            'Test with unexpected Exception' => [
                'exception'                          => new \Exception(),
                'expectedIncrementErrorEntriesCount' => 0,
                'expected'                           => false
            ],
            'Test with expected exception but unxpexted code' => [
                'exception'                          => new InvalidRecordException('', 0),
                'expectedIncrementErrorEntriesCount' => 0,
                'expected'                           => false
            ],
            'Test with expected exception and expected code' => [
                'exception'                          => new InvalidRecordException('', 422),
                'expectedIncrementErrorEntriesCount' => 1,
                'expected'                           => true
            ]
        ];
    }
}
