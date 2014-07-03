<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider;

use OroCRM\Bundle\ZendeskBundle\Provider\TicketCommentConnector;

class TicketCommentConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TicketCommentConnector
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

        $firstComment = array(
            'id' => 1
        );

        $secondComment = array(
            'id' => 2
        );

        $thirdComment = array(
            'id' => 3
        );

        $fourthComment = array(
            'id' => 4
        );

        $expectedResults = array(
            $firstComment,
            $secondComment,
            $thirdComment,
            $fourthComment
        );

        $map = array(
            array($firstTicketId, new \ArrayIterator(array($firstComment))),
            array($secondTicketId, new \ArrayIterator(array($secondComment, $thirdComment))),
            array($thirdTicketId, new \ArrayIterator(array($fourthComment)))
        );

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
                    array(
                        $firstTicketId,
                        $secondTicketId,
                        $thirdTicketId
                    )
                )
            );

        $this->connector->setStepExecution($this->stepExecutor);

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
