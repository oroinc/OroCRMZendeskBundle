<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider;

use OroCRM\Bundle\ZendeskBundle\Provider\AbstractZendeskConnector;

class AbstractZendeskConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Option "transport" should implement "ZendeskTransportInterface"
     */
    public function testValidateConfigurationThrowExceptionIfTransportInvalid()
    {
        $transport = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\TransportInterface');
        $connector = $this->getConnector($transport);
        $stepExecutor = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $connector->setStepExecution($stepExecutor);
    }

    public function testValidateConfiguration()
    {
        $transport = $this->getMock('OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface');
        $connector = $this->getConnector($transport);
        $stepExecutor = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $connector->setStepExecution($stepExecutor);
    }

    /**
     * @param $transport
     * @return AbstractZendeskConnector
     */
    protected function getConnector($transport)
    {

        $registry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy')
            ->disableOriginalConstructor()
            ->getMock();
        $mediator = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator')
            ->disableOriginalConstructor()
            ->getMock();
        $syncState = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Model\SyncState')
            ->disableOriginalConstructor()
            ->getMock();

        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $mediator->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $transportEntity = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $channel->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transportEntity));
        $mediator->expects($this->any())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $registry->expects($this->any())
            ->method('getByStepExecution')
            ->will($this->returnValue($context));

        $constructorArgs = array(
            $syncState,
            $registry,
            $logger,
            $mediator
        );

        $connector = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Provider\AbstractZendeskConnector')
            ->setConstructorArgs($constructorArgs)
            ->setMethods(array('getConnectorSource'))
            ->getMockForAbstractClass();

        $iterator = $this->getMock('\Iterator');
        $connector->expects($this->any())
            ->method('getConnectorSource')
            ->will($this->returnValue($iterator));

        return $connector;
    }
}
