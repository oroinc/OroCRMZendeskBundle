<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;

class SyncState
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @var array
     */
    protected $ticketIds = array();

    /**
     * @param Channel $channel
     * @param string $connector
     * @return \DateTime|null
     */
    public function getLastSyncDate(Channel $channel, $connector)
    {
        $repository = $this->entityManager->getRepository('OroIntegrationBundle:Status');

        /**
         * @var Status $status
         */
        $status = $repository->findOneBy(
            array('code' => Status::STATUS_COMPLETED, 'channel' => $channel, 'connector' => $connector),
            array('date' => 'DESC')
        );

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
     * @return SyncState
     */
    public function addTicketId($id)
    {
        $this->ticketIds[] = $id;

        return $this;
    }

    /**
     * @param array $ticketIds
     * @return SyncState
     */
    public function setTicketIds(array $ticketIds)
    {
        $this->ticketIds = $ticketIds;

        return $this;
    }
}
