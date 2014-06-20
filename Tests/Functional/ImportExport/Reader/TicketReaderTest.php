<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TicketReaderTest extends WebTestCase
{
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

        $provider = $this->getServiceMockBuilder('orocrm_zendesk.configuration_provider')
            ->disableOriginalConstructor()
            ->getMock();
        $provider->expects($this->any())
            ->method('getApiToken')
            ->will($this->returnValue('46PliuoNsoYjZYpIM0OOt50RpemP56QrvcpB7Nx3'));
        $provider->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('info@magecore.com'));
        $provider->expects($this->any())
            ->method('getZendeskUrl')
            ->will($this->returnValue('https://testoro.zendesk.com'));
        $this->importExportContextRegistry = $this->getServiceMockBuilder('oro_importexport.context_registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->getContainer()->set('oro_importexport.context_registry', $this->importExportContextRegistry);
        $this->getContainer()->set('orocrm_zendesk.configuration_provider', $provider);
    }

    protected function tearDown()
    {
        $this->getContainer()->set('orocrm_zendesk.configuration_provider', null);
        $this->getContainer()->set('oro_importexport.context_registry', null);
    }


    public function testTrue()
    {
      /*  $reader = $this->client->getContainer()->get('orocrm_zendesk.importexport.reader.ticket_reader');

        $configuration = array(
            'resource' => 'search.json',
            'params' => array('query' => 'type:ticket'),
        );

        $reader->setStepExecution($this->createMockStepExecution($configuration));

        $data = $reader->read();

        print_r($data);exit;*/
    }

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

    protected function createContext(array $configuration)
    {
        return new Context($configuration);
    }
}
