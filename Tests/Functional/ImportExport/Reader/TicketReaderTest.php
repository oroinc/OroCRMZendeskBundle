<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Reader\TicketReader;

class TicketReaderTest extends WebTestCase
{
    /**
     * @var TicketReader
     */
    protected $reader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $restClient;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $restClientFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $importExportContextRegistry;

    protected function setUp()
    {
        $this->initClient();

        $this->restClientFactory = $this->getServiceMockBuilder('orocrm_zendesk.rest_client_factory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->getContainer()->set('orocrm_zendesk.rest_client_factory', $this->restClientFactory);

        $this->importExportContextRegistry = $this->getServiceMockBuilder('oro_importexport.context_registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->getContainer()->set('oro_importexport.context_registry', $this->importExportContextRegistry);

        $this->restClient = $this->getMock('OroCRM\\Bundle\\ZendeskBundle\\Model\\RestClientInterface');
        $this->restClientFactory->expects($this->any())
            ->method('getRestClient')
            ->will($this->returnValue($this->restClient));

        $this->reader = $this->getContainer()->get('orocrm_zendesk.importexport.reader.ticket_reader');
    }

    protected function tearDown()
    {
        $this->getContainer()->set('orocrm_zendesk.rest_client_factory', null);
        $this->getContainer()->set('oro_importexport.context_registry', null);
        $this->getContainer()->set('orocrm_zendesk.importexport.reader.zendesk_api', null);
    }

    /**
     * @dataProvider readDataProvider
     */
    public function testRead(array $configuration, array $clientExpectations, array $expectedItems)
    {
        foreach ($clientExpectations as $index => $data) {
            $stub = $this->restClient->expects($this->at($index))->method('get');
            call_user_func_array(array($stub, 'with'), $data['request']);
            $stub->will($this->returnValue($data['response']));
        }

        $this->reader->setStepExecution($this->createMockStepExecution($configuration));

        foreach ($expectedItems as $expectedItem) {
            $this->assertEquals($expectedItem, $this->reader->read());
        }
        $this->assertNull($this->reader->read());
    }

    public function readDataProvider()
    {
        return array(
            array(
                'configuration' => array(
                    'resource' => 'search.json',
                    'params' => array('query' => 'type:user'),
                ),
                'clientExpectations' => array(
                    array(
                        'request' => array('search.json', array('query' => 'type:user')),
                        'response' => array(
                            'count' => 1,
                            'next_page' => 'http://test.zendesk.com/api/v2/search.json?query=type:user&page=2',
                            'previous_page' => null,
                            'results' => array(
                                array('id' => 1)
                            )
                        ),
                    ),
                    array(
                        'request' => array('http://test.zendesk.com/api/v2/search.json?query=type:user&page=2'),
                        'response' => array(
                            'count' => 2,
                            'next_page' => null,
                            'previous_page' => 'http://test.zendesk.com/api/v2/search.json?query=type:user&page=2',
                            'results' => array(
                                array('id' => 5)
                            )
                        ),
                    ),
                    array(
                        'request' => array('tickets/1/comments.json', array()),
                        'response' => array(
                            'count' => 5,
                            'next_page' => 'http://test.zendesk.com/api/v2/tickets/1/comments.json?page=2',
                            'previous_page' => null,
                            'comments' => array(
                                array('id' => 1),
                                array('id' => 2),
                                array('id' => 3),
                                array('id' => 4),
                            )
                        ),
                    ),
                    array(
                        'request' => array('http://test.zendesk.com/api/v2/tickets/1/comments.json?page=2'),
                        'response' => array(
                            'count' => 5,
                            'next_page' => null,
                            'previous_page' => 'http://test.zendesk.com/api/v2/tickets/1/comments.json',
                            'comments' => array(
                                array('id' => 5)
                            )
                        ),
                    ),
                    array(
                        'request' => array('tickets/5/comments.json', array()),
                        'response' => array(
                            'count' => 1,
                            'next_page' => null,
                            'previous_page' => null,
                            'comments' => array(
                                array('id' => 1)
                            )
                        ),
                    )
                ),
                'expectedItems' => array(
                    array(
                        'id'       => 1,
                        'comments' => array(
                            array('id' => 1),
                            array('id' => 2),
                            array('id' => 3),
                            array('id' => 4),
                            array('id' => 5)
                        )
                    ),
                    array(
                        'id'       => 5,
                        'comments' => array(
                            array('id' => 1)
                        )
                    )
                )
            )
        );
    }

    /**
     * @param array $configuration
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockStepExecution(array $configuration)
    {
        $stepExecution = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Entity\\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $this->importExportContextRegistry->expects($this->atLeastOnce())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($this->createContext($configuration)));

        return $stepExecution;
    }

    /**
     * @param array $configuration
     * @return Context
     */
    protected function createContext(array $configuration)
    {
        return new Context($configuration);
    }
}
