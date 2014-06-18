<?php

namespace OroCRM\Bundle\ZendeskBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;

/**
 * @TODO This command is for test purposes only right now.
 */
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
        $configuration = [
            'import' => [
                'processorAlias' => 'orocrm_zendesk.sync_from_zendesk_user',
                'resource'       => 'search.json',
                'params'         => array(
                    'query' => 'type:user'
                )
            ]
        ];

        $jobResult = $this->get('oro_importexport.job_executor')
            ->executeJob(ProcessorRegistry::TYPE_IMPORT, 'sync_from_zendesk', $configuration);

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
