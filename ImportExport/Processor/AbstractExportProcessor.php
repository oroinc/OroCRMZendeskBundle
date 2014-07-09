<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\ContextAwareProcessor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use OroCRM\Bundle\ZendeskBundle\ImportExport\ImportExportLogger;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;

abstract class AbstractExportProcessor implements
    StepExecutionAwareInterface,
    ContextAwareProcessor,
    LoggerAwareInterface
{
    /**
     * @var ImportExportLogger
     */
    private $logger;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var ContextRegistry
     */
    private $contextRegistry;

    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskProvider;

    /**
     * @var ConnectorContextMediator
     */
    protected $connectorContextMediator;

    /**
     * @var Channel
     */
    private $channel = null;

    /**
     * @param ContextRegistry $contextRegistry
     */
    public function setContextRegistry(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }


    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = new ImportExportLogger($logger);
    }

    /**
     * @return ImportExportLogger
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new ImportExportLogger(new NullLogger());
        }
        $this->logger->setImportExportContext($this->getContext());
        return $this->logger;
    }

    /**
     * @param ZendeskEntityProvider $zendeskProvider
     */
    public function setZendeskProvider(ZendeskEntityProvider $zendeskProvider)
    {
        $this->zendeskProvider = $zendeskProvider;
    }

    /**
     * @param ConnectorContextMediator $connectorContextMediator
     */
    public function setConnectorContextMediator(ConnectorContextMediator $connectorContextMediator)
    {
        $this->connectorContextMediator = $connectorContextMediator;
    }

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        if ($this->channel === null) {
            $this->channel = $this->connectorContextMediator->getChannel($this->context);
        }

        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->setImportExportContext($this->contextRegistry->getByStepExecution($stepExecution));
    }

    /**
     * @param ContextInterface $context
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @return ContextInterface
     */
    public function getContext()
    {
        return $this->context;
    }
}
