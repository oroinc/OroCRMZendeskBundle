<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Job;

use Doctrine\Persistence\ManagerRegistry;
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
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $resource;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /** {@inheritdoc} */
    protected function setUp(): void
    {
        $this->initClient();

        $this->resource = $this->createMock(ZendeskRestTransport::class);
        $this->getContainer()->set('oro_zendesk.tests.transport.rest_transport', $this->resource);

        $this->managerRegistry = $this->getContainer()->get('doctrine');
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
}
