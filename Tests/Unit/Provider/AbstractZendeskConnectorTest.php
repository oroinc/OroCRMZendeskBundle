<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider;

use OroCRM\Bundle\ZendeskBundle\Provider\AbstractZendeskConnector;

class AbstractZendeskConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractZendeskConnector
     */
    protected $connector;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stepExecutor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mediator;

    protected function setUp()
    {
        $registry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mediator = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator')
            ->disableOriginalConstructor()
            ->getMock();
        $syncState = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Model\SyncState')
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $transportEntity = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $channel->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transportEntity));
        $this->mediator->expects($this->any())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $registry->expects($this->any())
            ->method('getByStepExecution')
            ->will($this->returnValue($context));
        $constructorArgs = array(
            $syncState,
            $registry,
            $logger,
            $this->mediator
        );

        $this->connector = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Provider\AbstractZendeskConnector')
            ->setConstructorArgs($constructorArgs)
            ->setMethods(array('getConnectorSource'))
            ->getMockForAbstractClass();
        $this->stepExecutor = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $iterator = $this->getMock('\Iterator');
        $this->connector->expects($this->any())
            ->method('getConnectorSource')
            ->will($this->returnValue($iterator));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Option "transport" should implement "ZendeskTransportInterface"
     */
    public function testValidateConfigurationThrowExceptionIfTransportInvalid()
    {
        $transport = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\TransportInterface');
        $this->mediator->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        $this->connector->setStepExecution($this->stepExecutor);
    }

    public function testValidateConfiguration()
    {
        $transport = $this->getMock('OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface');
        $this->mediator->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transport));

        $this->connector->setStepExecution($this->stepExecutor);
    }
}
