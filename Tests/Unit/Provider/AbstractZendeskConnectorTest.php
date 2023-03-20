<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\ZendeskBundle\Model\SyncState;
use Oro\Bundle\ZendeskBundle\Provider\AbstractZendeskConnector;
use Oro\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;

class AbstractZendeskConnectorTest extends \PHPUnit\Framework\TestCase
{
    use ExecutionContextTrait;

    /** @var AbstractZendeskConnector */
    private $connector;

    /** @var StepExecution|\PHPUnit\Framework\MockObject\MockObject */
    private $stepExecutor;

    /** @var ConnectorContextMediator|\PHPUnit\Framework\MockObject\MockObject */
    private $mediator;

    protected function setUp(): void
    {
        $this->mediator = $this->createMock(ConnectorContextMediator::class);
        $this->stepExecutor = $this->createMock(StepExecution::class);

        $channel = $this->createMock(Channel::class);
        $channel->expects($this->any())
            ->method('getTransport')
            ->willReturn($this->createMock(Transport::class));

        $this->mediator->expects($this->any())
            ->method('getChannel')
            ->willReturn($channel);

        $syncState = $this->createMock(SyncState::class);

        $registry = $this->createMock(ContextRegistry::class);
        $registry->expects($this->any())
            ->method('getByStepExecution')
            ->willReturn($this->createMock(ContextInterface::class));

        $logger = $this->createMock(LoggerStrategy::class);

        $this->stepExecutor->expects($this->any())
            ->method('getExecutionContext')
            ->willReturn($this->initExecutionContext());

        $this->connector = $this->getMockBuilder(AbstractZendeskConnector::class)
            ->setConstructorArgs([$syncState, $registry, $logger, $this->mediator])
            ->onlyMethods(['getConnectorSource'])
            ->getMockForAbstractClass();

        $this->connector->expects($this->any())
            ->method('getConnectorSource')
            ->willReturn($this->createMock(\Iterator::class));
    }

    public function testValidateConfigurationThrowExceptionIfTransportInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Option "transport" should implement "ZendeskTransportInterface"');

        $transport = $this->createMock(TransportInterface::class);
        $this->mediator->expects($this->any())
            ->method('getTransport')
            ->willReturn($transport);
        $this->connector->setStepExecution($this->stepExecutor);
    }

    public function testValidateConfiguration()
    {
        $transport = $this->createMock(ZendeskTransportInterface::class);
        $this->mediator->expects($this->any())
            ->method('getTransport')
            ->willReturn($transport);

        $isUpdatedLastSyncDateCallback = $this->getIsUpdatedLastSyncDateCallback();
        $this->connector->setStepExecution($this->stepExecutor);
        $this->assertTrue(
            $isUpdatedLastSyncDateCallback(),
            'Last sync date should be saved to context data !'
        );
    }
}
