<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\ZendeskBundle\Model\SyncState;
use Oro\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
use Oro\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;

class TicketCommentConnectorTest extends \PHPUnit\Framework\TestCase
{
    use ExecutionContextTrait;

    /**
     * @var TicketCommentConnector
     */
    protected $connector;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mediator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $syncState;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $transport;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $channel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $stepExecutor;

    protected function setUp(): void
    {
        $this->registry = $this->getMockBuilder(ContextRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerStrategy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mediator = $this->getMockBuilder(ConnectorContextMediator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->syncState = $this->getMockBuilder(SyncState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transport = $this->createMock(ZendeskTransportInterface::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->mediator->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($this->transport));
        $this->channel = $this->createMock(Channel::class);
        $transportEntity = $this->createMock(Transport::class);
        $this->channel->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transportEntity));
        $this->mediator->expects($this->any())
            ->method('getChannel')
            ->will($this->returnValue($this->channel));
        $this->stepExecutor = $this->getMockBuilder(StepExecution::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stepExecutor
            ->expects($this->any())
            ->method('getExecutionContext')
            ->willReturn($this->initExecutionContext());

        $this->connector = new TicketCommentConnector(
            $this->syncState,
            $this->registry,
            $this->logger,
            $this->mediator
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset(
            $this->connector,
            $this->mediator,
            $this->logger,
            $this->registry,
            $this->syncState,
            $this->transport,
            $this->context,
            $this->channel,
            $this->executionContext
        );
    }

    public function testGetConnectorSource()
    {
        $firstTicketId = 10;
        $secondTicketId = 20;
        $thirdTicketId = 30;

        $firstComment = [
            'id' => 1,
        ];

        $secondComment = [
            'id' => 2,
        ];

        $thirdComment = [
            'id' => 3,
        ];

        $fourthComment = [
            'id' => 4,
        ];

        $expectedResults = [
            $firstComment,
            $secondComment,
            $thirdComment,
            $fourthComment,
        ];

        $map = [
            [$firstTicketId, new \ArrayIterator([$firstComment])],
            [$secondTicketId, new \ArrayIterator([$secondComment, $thirdComment])],
            [$thirdTicketId, new \ArrayIterator([$fourthComment])],
        ];

        $this->transport->expects($this->exactly(3))
            ->method('getTicketComments')
            ->will($this->returnValueMap($map));

        $this->registry->expects($this->atLeastOnce())
            ->method('getByStepExecution')
            ->with($this->stepExecutor)
            ->will($this->returnValue($this->context));

        $this->syncState->expects($this->once())
            ->method('getTicketIds')
            ->will(
                $this->returnValue(
                    [
                        $firstTicketId,
                        $secondTicketId,
                        $thirdTicketId,
                    ]
                )
            );

        $isUpdatedLastSyncDateCallback = $this->getIsUpdatedLastSyncDateCallback();
        $this->connector->setStepExecution($this->stepExecutor);
        $this->assertTrue(
            $isUpdatedLastSyncDateCallback(),
            "Last sync date should be saved to context data !"
        );

        foreach ($expectedResults as $expected) {
            $result = $this->connector->read();
            $this->assertEquals($expected, $result);
        }
    }

    public function testGetImportEntityFQCN()
    {
        $this->assertEquals(TicketCommentConnector::IMPORT_ENTITY, $this->connector->getImportEntityFQCN());
    }

    public function testGetType()
    {
        $this->assertEquals(TicketCommentConnector::TYPE, $this->connector->getType());
    }

    public function testGetImportJobName()
    {
        $this->assertEquals(TicketCommentConnector::IMPORT_JOB, $this->connector->getImportJobName());
    }

    public function testGetExportJobName()
    {
        $this->assertEquals(TicketCommentConnector::EXPORT_JOB, $this->connector->getExportJobName());
    }
}
