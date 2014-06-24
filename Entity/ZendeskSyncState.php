<?php

namespace OroCRM\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orocrm_zd_sync_state")
 * @ORM\Entity
 */
class ZendeskSyncState
{
    const STATE_ID = 0;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_sync", type="datetime", nullable=true)
     */
    protected $lastSync;

    /**
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $lastSync
     * @return ZendeskSyncState
     */
    public function setLastSync(\DateTime $lastSync)
    {
        $this->lastSync = $lastSync;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastSync()
    {
        return $this->lastSync;
    }
}
