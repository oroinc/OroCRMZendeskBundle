<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

class SyncState
{
    /**
     * @var array
     */
    protected $ticketIds = array();

    /**
     * return \DateTime|null
     */
    public function getLastSyncDate()
    {
        return null;
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
     */
    public function addTicketId($id)
    {
        $this->ticketIds[] = $id;
    }
}
