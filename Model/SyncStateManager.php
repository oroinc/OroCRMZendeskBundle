<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use Doctrine\ORM\EntityManager;

use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskSyncState;

class SyncStateManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return \DateTime
     */
    public function getLastSyncDate()
    {
        $syncState = $this->getSyncState();

        return $syncState->getLastSync();
    }

    /**
     * @param \DateTime $dateTime
     * @param bool      $flush
     */
    public function setLastSyncDate(\DateTime $dateTime, $flush = false)
    {
        $syncState = $this->getSyncState();

        $syncState->setLastSync($dateTime);

        $this->entityManager->persist($syncState);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @return ZendeskSyncState
     */
    protected function getSyncState()
    {
        $repository = $this->entityManager->getRepository('OroCRMZendeskBundle:ZendeskSyncState');

        /**
         * @var ZendeskSyncState $syncState
         */
        $syncState = $repository->find(ZendeskSyncState::STATE_ID);

        if (!$syncState) {
            $syncState = new ZendeskSyncState(ZendeskSyncState::STATE_ID);
        }

        return $syncState;
    }
}
