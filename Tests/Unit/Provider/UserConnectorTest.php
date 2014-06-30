<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider;

use OroCRM\Bundle\ZendeskBundle\Provider\UserConnector;

class UserConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserConnector
     */
    protected $connector;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mediator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $syncState;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $channel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stepExecutor;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mediator = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->syncState = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Model\SyncState')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transport = $this->getMock('OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface');
        $this->context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $this->mediator->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($this->transport));
        $this->channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $transportEntity = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $this->channel->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transportEntity));
        $this->mediator->expects($this->any())
            ->method('getChannel')
            ->will($this->returnValue($this->channel));
        $this->stepExecutor = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $this->connector = new UserConnector(
            $this->syncState,
            $this->registry,
            $this->logger,
            $this->mediator
        );
    }

    public function testGetConnectorSource()
    {
        $expectedResults = array(
            array('id' => 1),
            array('id' => 12),
            array('id' => 3),
            array('id' => 46),
            array('id' => 51),
        );
        $expectedSyncDate = new \DateTime('2014-06-10T12:12:21Z');
        $expectedChannel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $this->mediator->expects($this->atLeastOnce())
            ->method('getChannel')
            ->will($this->returnValue($expectedChannel));
        $this->transport->expects($this->once())
            ->method('getUsers')
            ->with($expectedSyncDate)
            ->will($this->returnValue(new \ArrayIterator($expectedResults)));

        $this->syncState->expects($this->once())
            ->method('getLastSyncDate')
            ->with($expectedChannel, 'user')
            ->will($this->returnValue($expectedSyncDate));

        $this->registry->expects($this->atLeastOnce())
            ->method('getByStepExecution')
            ->with($this->stepExecutor)
            ->will($this->returnValue($this->context));

        $this->connector->setStepExecution($this->stepExecutor);

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
