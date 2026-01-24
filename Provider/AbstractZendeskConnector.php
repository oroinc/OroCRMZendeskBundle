<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\ZendeskBundle\Model\SyncState;
use Oro\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;

/**
 * Provides common functionality for Zendesk connectors.
 *
 * This abstract class extends the base integration connector to provide Zendesk-specific functionality.
 * It manages synchronization state tracking and ensures that the transport is properly configured as
 * a {@see ZendeskTransportInterface}. Subclasses can leverage the sync state management
 * and last sync date tracking to implement specific Zendesk data import/export operations.
 */
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

    public function __construct(
        SyncState $syncState,
        ContextRegistry $contextRegistry,
        LoggerStrategy $logger,
        ConnectorContextMediator $contextMediator
    ) {
        $this->syncState = $syncState;
        parent::__construct($contextRegistry, $logger, $contextMediator);
    }

    #[\Override]
    protected function initializeFromContext(ContextInterface $context)
    {
        parent::initializeFromContext($context);
        $this->addLastSyncDate();
    }

    /**
     * Write last sync date to context
     */
    protected function addLastSyncDate()
    {
        $today = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->addStatusData(
            SyncState::LAST_SYNC_DATE_KEY,
            $today->format(\DateTime::ISO8601)
        );
    }

    /**
     * @return \DateTime|null
     */
    protected function getLastSyncDate()
    {
        $channel = $this->contextMediator->getChannel($this->getContext());
        return $this->syncState->getLastSyncDate($channel, $this->getType());
    }

    #[\Override]
    protected function validateConfiguration()
    {
        parent::validateConfiguration();

        if (!$this->transport instanceof ZendeskTransportInterface) {
            throw new \LogicException('Option "transport" should implement "ZendeskTransportInterface"');
        }
    }
}
