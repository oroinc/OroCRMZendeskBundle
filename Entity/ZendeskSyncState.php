<?php

namespace OroCRM\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orocrm_zd_sync_state")
 * @ORM\Entity
 */
class ZendeskSyncState
{
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
     * @ORM\Column(type="datetime")
     */
    protected $userSync;


    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $ticketSync;

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
     * @param \DateTime $ticketSync
     * @return ZendeskSyncState
     */
    public function setTicketSync(\DateTime $ticketSync)
    {
        $this->ticketSync = $ticketSync;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTicketSync()
    {
        return $this->ticketSync;
    }

    /**
     * @param \DateTime $userSync
     * @return ZendeskSyncState
     */
    public function setUserSync(\DateTime $userSync)
    {
        $this->userSync = $userSync;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUserSync()
    {
        return $this->userSync;
    }
}
