<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Job;

use Doctrine\Common\Persistence\ManagerRegistry;

use Monolog\Handler\TestHandler;
use Monolog\Logger;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestTransport;

class AbstractImportExportJobTestCase extends WebTestCase
{
    const SYNC_PROCESSOR = 'oro_integration.reverse_sync.processor';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resource;

    /** @var  ZendeskRestTransport */
    protected $realResource;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();

        $this->stubResources();
        $this->managerRegistry = $this->getContainer()
            ->get('doctrine');
    }

    /** {@inheritdoc} */
    public function tearDown()
    {
        $this->getContainer()->set('oro_zendesk.transport.rest_transport', $this->realResource);
        unset(
            $this->managerRegistry,
            $this->realResource,
            $this->resource
        );
        parent::tearDown();
    }

    /**
     * @param string  $processorId
     *
     * @param Channel $channel
     * @param string  $connector
     * @param array   $parameters
     * @param array   $jobLog
     *
     * @return bool
     */
    public function runImportExportConnectorsJob(
        $processorId,
        Channel $channel,
        $connector,
        array $parameters = [],
        &$jobLog = []
    ) {
        /** @var SyncProcessor $processor */
        $processor = $this->getContainer()->get($processorId);
        $testLoggerHandler = new TestHandler(Logger::WARNING);
        $processor->getLoggerStrategy()->setLogger(new Logger('testDebug', [$testLoggerHandler]));

        $result = $processor->process($channel, $connector, $parameters);

        $jobLog = $testLoggerHandler->getRecords();

        return $result;
    }

    /**
     * @param array $jobLog
     *
     * @return string
     */
    public function formatImportExportJobLog(array $jobLog)
    {
        $output = array_reduce(
            $jobLog,
            function ($carry, $record) {
                $formatMessage = sprintf(
                    '%s> [level: %s] Message: %s',
                    PHP_EOL,
                    $record['level_name'],
                    empty($record['formatted']) ? $record['message'] : $record['formatted']
                );

                return $carry . $formatMessage;
            }
        );

        return $output;
    }

    protected function stubResources()
    {
        $this->resource = $this->createMock(ZendeskRestTransport::class);

        $this->realResource = $this->getContainer()
            ->get('oro_zendesk.transport.rest_transport');

        $this->getContainer()
            ->set('oro_zendesk.transport.rest_transport', $this->resource);
    }
}
