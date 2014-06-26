<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use OroCRM\Bundle\ZendeskBundle\Model\SyncState;

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
     * @param SyncState $syncState
     */
    public function setSyncState(SyncState $syncState)
    {
        $this->syncState = $syncState;
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
