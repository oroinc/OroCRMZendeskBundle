<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use OroCRM\Bundle\ZendeskBundle\ImportExport\ImportExportLogger;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ContextAwareProcessor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

abstract class AbstractImportProcessor implements
    StepExecutionAwareInterface,
    ContextAwareProcessor,
    LoggerAwareInterface
{
    /**
     * @var ConnectorContextMediator
     */
    private $connectorContextMediator;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var ContextRegistry
     */
    private $contextRegistry;

    /**
     * @var ImportExportLogger
     */
    private $logger;

    /**
     * @var Channel
     */
    private $channel = null;

    /**
     * Validates availability of origin id field
     *
     * @param mixed $entity
     * @return bool
     */
    public function validateOriginId($entity)
    {
        if (!$entity->getOriginId()) {
            $readPosition = $this->getContext()->getReadCount();
            $message = "Can't process record with empty id at read position $readPosition.";

            $this->getContext()->addError($message);
            $this->getLogger()->error($message);

            $this->getContext()->incrementErrorEntriesCount();

            return false;
        }

        return true;
    }

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        if ($this->channel === null || $this->context->getOption('channel') !== $this->channel->getId()) {
            $this->channel = $this->connectorContextMediator->getChannel($this->context);
        }

        return $this->channel;
    }

    /**
     * @param ContextRegistry $contextRegistry
     */
    public function setContextRegistry(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * @param ConnectorContextMediator $connectorContextMediator
     */
    public function setConnectorContextMediator(ConnectorContextMediator $connectorContextMediator)
    {
        $this->connectorContextMediator = $connectorContextMediator;
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
    protected function getContext()
    {
        return $this->context;
    }

    /**
     * @return ImportExportLogger
     */
    protected function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new ImportExportLogger(new NullLogger());
        }
        $this->logger->setImportExportContext($this->getContext());
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = new ImportExportLogger($logger);
    }
}
