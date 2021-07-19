<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ContextAwareProcessor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\ZendeskBundle\ImportExport\ImportExportLogger;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base processor for zendesk export
 */
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

    public function setZendeskProvider(ZendeskEntityProvider $zendeskProvider)
    {
        $this->zendeskProvider = $zendeskProvider;
    }

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
