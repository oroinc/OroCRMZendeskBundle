<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Processor;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\ContextAwareProcessor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\OroEntityProvider;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\ZendeskEntityProvider;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\SyncLogger;
use Psr\Log\LoggerInterface;

abstract class AbstractExportProcessor implements ContextAwareProcessor
{
    /**
     * @var SyncLogger
     */
    protected $logger;

    /**
     * @var ContextInterface
     */
    protected $context;

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
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = new SyncLogger($logger);
    }

    /**
     * @return SyncLogger
     */
    public function getLogger()
    {
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
