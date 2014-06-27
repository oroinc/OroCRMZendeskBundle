<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use OroCRM\Bundle\ZendeskBundle\Model\SyncState;
use OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;

abstract class AbstractZendeskConnector extends AbstractConnector
{
    /**
     * @var SyncState
     */
    protected $syncState;

    /**
     * @var ZendeskTransportInterface
     */
    protected $transport;

    /**
     * @param SyncState                $syncState
     * @param ContextRegistry          $contextRegistry
     * @param LoggerStrategy           $logger
     * @param ConnectorContextMediator $contextMediator
     */
    public function __construct(
        SyncState $syncState,
        ContextRegistry $contextRegistry,
        LoggerStrategy $logger,
        ConnectorContextMediator $contextMediator
    ) {
        $this->syncState = $syncState;
        parent::__construct($contextRegistry, $logger, $contextMediator);
    }

    /**
     * @return \DateTime|null
     */
    protected function getLastSyncDate()
    {
        $channel = $this->contextMediator->getChannel($this->getContext());
        return $this->syncState->getLastSyncDate($channel, $this->getType());
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfiguration()
    {
        parent::validateConfiguration();

        if (!$this->transport instanceof ZendeskTransportInterface) {
            throw new \LogicException('Option "transport" should implement "ZendeskTransportInterface"');
        }
    }
}
