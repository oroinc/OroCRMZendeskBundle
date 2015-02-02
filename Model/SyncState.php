<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;

class SyncState
{
    /** @var array */
    protected $ticketIds = [];

    /**
     * @param Channel $channel
     * @param string  $connector
     *
     * @return \DateTime|null
     */
    public function getLastSyncDate(Channel $channel, $connector)
    {
        $status = $channel->getLastStatusForConnector($connector, Status::STATUS_COMPLETED);

        return $status ? $status->getDate() : null;
    }

    /**
     * @return array
     */
    public function getTicketIds()
    {
        return $this->ticketIds;
    }

    /**
     * @param int $id
     *
     * @return SyncState
     */
    public function addTicketId($id)
    {
        $this->ticketIds[] = $id;

        return $this;
    }

    /**
     * @param array $ticketIds
     *
     * @return SyncState
     */
    public function setTicketIds(array $ticketIds)
    {
        $this->ticketIds = $ticketIds;

        return $this;
    }
}
