<?php

namespace OroCRM\Bundle\ZendeskBundle\Command;

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
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
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
        $logger = new OutputLogger($output);

        $configuration = [
            'import' => [
                'processorAlias' => 'orocrm_zendesk.sync_from_zendesk_user',
                'entityName' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
                'resource' => 'search.json',
                'logger' => $logger,
                'params' => array(
                    'query' => 'type:user'
                )
            ]
        ];

        $logger->notice('Run synchronization of Zendesk users.');

        /** @var JobResult $jobResult */
        $jobResult = $this->get('oro_importexport.job_executor')
            ->executeJob(ProcessorRegistry::TYPE_IMPORT, 'sync_from_zendesk', $configuration);

        $context = $jobResult->getContext();

        $counts = [];

        if ($context) {
            $counts['process'] = $counts['warnings'] = 0;
            $counts['read']    = $context->getReadCount();
            $counts['process'] += $counts['add'] = $context->getAddCount();
            $counts['process'] += $counts['update'] = $context->getUpdateCount();
            $counts['process'] += $counts['delete'] = $context->getUpdateCount();
        }

        $exceptions = $jobResult->getFailureExceptions();
        $isSuccess  = $jobResult->isSuccessful() && !$exceptions;

        if (!$isSuccess) {
            $logger->error('Errors were occurred:');
            $exceptions = implode(PHP_EOL, $exceptions);
            $logger->error(
                $exceptions,
                ['exceptions' => $jobResult->getFailureExceptions()]
            );
        } else {
            if ($context->getErrors()) {
                $logger->warning('Some entities were skipped due to warnings:');
                foreach ($context->getErrors() as $error) {
                    $logger->warning($error);
                }
            }

            $message = sprintf(
                "Stats: read [%d], updated [%d], added [%d], delete [%d], invalid entries: [%d]",
                $context->getReadCount(),
                $context->getUpdateCount(),
                $context->getAddCount(),
                $context->getDeleteCount(),
                $context->getErrorEntriesCount()
            );
            $logger->notice($message);
        }

        $logger->notice('Completed.');

        return 0;
    }

    /**
     * Get service from DI container by id
     *
     * @param string $id
     * @return object
     */
    protected function get($id)
    {
        return $this->getContainer()->get($id);
    }
}
