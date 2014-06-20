<?php

namespace OroCRM\Bundle\ZendeskBundle\Command;

use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\TicketSyncStrategy;
use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ImportExportBundle\Job\JobResult;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;

class SyncCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const COMMAND_NAME = 'oro:cron:zendesk:sync';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $counts = array();

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var array
     */
    protected $exceptions = array();

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return $this->getContainer()
            ->get('orocrm_zendesk.configuration_provider')
            ->getCronSchedule();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Runs synchronization for Zendesk');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new OutputLogger($output);

        $this->executeSyncFromZendeskJob(
            'zendesk_users',
            'Synchronization of Zendesk users',
            [
                'processorAlias' => 'orocrm_zendesk.sync_from_zendesk_user',
                'entityName' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
                'resource' => 'search.json',
                'logger' => $this->logger,
                'params' => array(
                    'query' => 'type:user'
                )
            ]
        );

        $result = $this->executeSyncFromZendeskJob(
            'zendesk_tickets',
            'Synchronization of Zendesk tickets',
            [
                'processorAlias' => 'orocrm_zendesk.sync_from_zendesk_ticket',
                'entityName' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
                'resource' => 'search.json',
                'logger' => $this->logger,
                'params' => array(
                    'query' => 'type:ticket'
                )
            ]
        );

        $commentTickets = $result->getContext()->getValue(TicketSyncStrategy::COMMENT_TICKETS);

        foreach ($commentTickets as $ticketId) {
            $this->executeSyncFromZendeskJob(
                'zendesk_ticket_comments',
                sprintf('Synchronization of comments of Zendesk ticket [id=%s]', $ticketId),
                [
                    'processorAlias' => 'orocrm_zendesk.sync_from_zendesk_ticket_comment',
                    'entityName' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment',
                    'resource' => sprintf('tickets/%s/comments.json', $ticketId),
                    'logger' => $this->logger,
                    'readerDataKeyName' => 'comments',
                    'ticketId' => $ticketId,
                ]
            );
        }

        $this->logger->notice('Result report');
        $this->showErrorReport();
        $this->showCountReport('zendesk_users', 'Synchronization of Zendesk users');
        $this->showCountReport('zendesk_tickets', 'Synchronization of Zendesk tickets');
        $this->showCountReport('zendesk_ticket_comments', 'Synchronization of comments of Zendesk tickets');

        return 0;
    }

    /**
     * Show error report
     */
    protected function showErrorReport()
    {
        if ($this->exceptions) {
            $this->logger->error('Errors were occurred:');
            $exceptions = implode(PHP_EOL, $this->exceptions);
            $this->logger->error(
                $exceptions,
                ['exceptions' => $this->exceptions]
            );
        }
        if ($this->errors) {
            $this->logger->warning('Some entities were skipped due to warnings:');
            foreach ($this->errors as $error) {
                $this->logger->warning($error);
            }
        }
    }

    /**
     * Show count report
     *
     * @param string $name
     * @param string $title
     */
    protected function showCountReport($name, $title)
    {
        $message = sprintf(
            "%s: read [%d], updated [%d], added [%d], delete [%d], invalid entries: [%d]",
            $title,
            $this->counts[$name]['read'],
            $this->counts[$name]['update'],
            $this->counts[$name]['add'],
            $this->counts[$name]['delete'],
            $this->counts[$name]['error_entries']
        );
        $this->logger->notice($message);
    }

    /**
     * Execute synchronization of Zendesk entities
     *
     * @param string $name
     * @param string $title
     * @param array $configuration
     * @return JobResult
     */
    protected function executeSyncFromZendeskJob($name, $title, array $configuration)
    {
        $configuration = [
            'import' => $configuration
        ];

        $this->logger->notice($title);
        $this->logger->notice('Starting...');

        $result = $this->getContainer()->get('oro_importexport.job_executor')
            ->executeJob(ProcessorRegistry::TYPE_IMPORT, 'sync_from_zendesk', $configuration);

        $this->logger->notice('Completed.');

        $exceptions = $result->getFailureExceptions();
        $isSuccess = $result->isSuccessful() && !$exceptions;

        if (!$isSuccess) {
            $this->exceptions = array_merge($this->exceptions, $result->getFailureExceptions());
        } else {
            $this->errors = array_merge($this->errors, $result->getContext()->getErrors());
        }

        $this->incrementJobResultCounts($name, $result);

        return $result;
    }

    /**
     * @param string $sectionName
     * @param JobResult $jobResult
     */
    protected function incrementJobResultCounts($sectionName, JobResult $jobResult)
    {
        $context = $jobResult->getContext();
        $this->incrementCountValue($sectionName, 'read', $context->getReadCount());
        $this->incrementCountValue($sectionName, 'add', $context->getAddCount());
        $this->incrementCountValue($sectionName, 'update', $context->getUpdateCount());
        $this->incrementCountValue($sectionName, 'delete', $context->getDeleteCount());
        $this->incrementCountValue($sectionName, 'error_entries', $context->getErrorEntriesCount());
    }

    /**
     * @param string $sectionName
     * @param string $valueName
     * @param int $value
     */
    protected function incrementCountValue($sectionName, $valueName, $value)
    {
        $counts = isset($this->counts[$sectionName]) ? $this->counts[$sectionName] : array();
        $counts[$valueName] = isset($counts[$valueName]) ? $counts[$valueName] : 0;
        $counts[$valueName] += (int)$value;
        $this->counts[$sectionName] = $counts;
    }
}