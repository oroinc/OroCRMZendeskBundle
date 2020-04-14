<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Handler;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ZendeskBundle\Handler\TicketCommentExceptionHandler;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\InvalidRecordException;

class TicketCommentExceptionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TicketCommentExceptionHandler */
    protected $exceptionHandler;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    /** {@inheritdoc} */
    protected function setUp(): void
    {
        $this->context = $this->createMock(ContextInterface::class);

        $this->exceptionHandler = new TicketCommentExceptionHandler();
    }

    /** {@inheritdoc} */
    protected function tearDown(): void
    {
        unset(
            $this->exceptionHandler,
            $this->context
        );

        parent::tearDown();
    }

    /**
     * @dataProvider exceptionDataProvider
     *
     * @param \Exception    $exception
     * @param int           $expectedIncrementErrorEntriesCount
     * @param bool          $expected
     */
    public function testProcess(\Exception $exception, $expectedIncrementErrorEntriesCount, $expected)
    {
        $this->context
             ->expects($this->exactly($expectedIncrementErrorEntriesCount))
             ->method('incrementErrorEntriesCount');

        $this->assertEquals(
            $this->exceptionHandler->process($exception, $this->context),
            $expected
        );
    }

    /** @return mixed[] */
    public function exceptionDataProvider()
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
