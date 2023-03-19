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
use Oro\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;
use Oro\Bundle\ZendeskBundle\Provider\UserConnector;

class UserConnectorTest extends \PHPUnit\Framework\TestCase
{
    use ExecutionContextTrait;

    /** @var UserConnector */
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

        $this->connector = new UserConnector(
            $this->syncState,
            $this->registry,
            $this->logger,
            $this->mediator
        );
    }

    public function testGetConnectorSource()
    {
        $expectedResults = [
            ['id' => 1],
            ['id' => 12],
            ['id' => 3],
            ['id' => 46],
            ['id' => 51],
        ];
        $expectedSyncDate = new \DateTime('2014-06-10T12:12:21Z');
        $expectedChannel = $this->createMock(Channel::class);
        $this->mediator->expects($this->atLeastOnce())
            ->method('getChannel')
            ->willReturn($expectedChannel);
        $this->transport->expects($this->once())
            ->method('getUsers')
            ->with($expectedSyncDate)
            ->willReturn(new \ArrayIterator($expectedResults));

        $this->syncState->expects($this->once())
            ->method('getLastSyncDate')
            ->with($expectedChannel, 'user')
            ->willReturn($expectedSyncDate);

        $this->registry->expects($this->atLeastOnce())
            ->method('getByStepExecution')
            ->with($this->stepExecutor)
            ->willReturn($this->context);

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
        $this->assertEquals(UserConnector::IMPORT_ENTITY, $this->connector->getImportEntityFQCN());
    }

    public function testGetType()
    {
        $this->assertEquals(UserConnector::TYPE, $this->connector->getType());
    }

    public function testGetImportJobName()
    {
        $this->assertEquals(UserConnector::IMPORT_JOB, $this->connector->getImportJobName());
    }
}
