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

    /** @var TicketCommentConnector */
    private $connector;

    /** @var ConnectorContextMediator|\PHPUnit\Framework\MockObject\MockObject */
    private $mediator;

    /** @var LoggerStrategy|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var SyncState|\PHPUnit\Framework\MockObject\MockObject */
    private $syncState;

    /** @var ZendeskTransportInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var StepExecution|\PHPUnit\Framework\MockObject\MockObject */
    private $stepExecutor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ContextRegistry::class);
        $this->logger = $this->createMock(LoggerStrategy::class);
        $this->mediator = $this->createMock(ConnectorContextMediator::class);
        $this->syncState = $this->createMock(SyncState::class);
        $this->transport = $this->createMock(ZendeskTransportInterface::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->stepExecutor = $this->createMock(StepExecution::class);

        $channel = $this->createMock(Channel::class);
        $channel->expects($this->any())
            ->method('getTransport')
            ->willReturn($this->createMock(Transport::class));

        $this->mediator->expects($this->any())
            ->method('getTransport')
            ->willReturn($this->transport);
        $this->mediator->expects($this->any())
            ->method('getChannel')
            ->willReturn($channel);

        $this->stepExecutor->expects($this->any())
            ->method('getExecutionContext')
            ->willReturn($this->initExecutionContext());

        $this->connector = new TicketCommentConnector(
            $this->syncState,
            $this->registry,
            $this->logger,
            $this->mediator
        );
    }

    public function testGetConnectorSource()
    {
        $firstTicketId = 10;
        $secondTicketId = 20;
        $thirdTicketId = 30;

        $firstComment = ['id' => 1];
        $secondComment = ['id' => 2];
        $thirdComment = ['id' => 3];
        $fourthComment = ['id' => 4];

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
            ->willReturnMap($map);

        $this->registry->expects($this->atLeastOnce())
            ->method('getByStepExecution')
            ->with($this->stepExecutor)
            ->willReturn($this->context);

        $this->syncState->expects($this->once())
            ->method('getTicketIds')
            ->willReturn([$firstTicketId, $secondTicketId, $thirdTicketId]);

        $isUpdatedLastSyncDateCallback = $this->getIsUpdatedLastSyncDateCallback();
        $this->connector->setStepExecution($this->stepExecutor);
        $this->assertTrue(
            $isUpdatedLastSyncDateCallback(),
            'Last sync date should be saved to context data !'
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
