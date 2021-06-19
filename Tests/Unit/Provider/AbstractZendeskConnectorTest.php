<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
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
        $registry = $this->createMock(ContextRegistry::class);
        $logger = $this->createMock(LoggerStrategy::class);
        $this->mediator = $this->createMock(ConnectorContextMediator::class);
        $syncState = $this->createMock(SyncState::class);
        $context = $this->createMock(ContextInterface::class);
        $channel = $this->createMock(Channel::class);
        $transportEntity = $this->createMock(Transport::class);
        $channel->expects($this->any())
            ->method('getTransport')
            ->willReturn($transportEntity);
        $this->mediator->expects($this->any())
            ->method('getChannel')
            ->willReturn($channel);
        $registry->expects($this->any())
            ->method('getByStepExecution')
            ->willReturn($context);
        $constructorArgs = [
            $syncState,
            $registry,
            $logger,
            $this->mediator
        ];

        $this->connector = $this->getMockBuilder(AbstractZendeskConnector::class)
            ->setConstructorArgs($constructorArgs)
            ->onlyMethods(['getConnectorSource'])
            ->getMockForAbstractClass();
        $this->stepExecutor = $this->createMock(StepExecution::class);

        $this->stepExecutor->expects($this->any())
            ->method('getExecutionContext')
            ->willReturn($this->initExecutionContext());

        $iterator = $this->createMock(\Iterator::class);
        $this->connector->expects($this->any())
            ->method('getConnectorSource')
            ->willReturn($iterator);
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
