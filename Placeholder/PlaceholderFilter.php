<?php

namespace Oro\Bundle\ZendeskBundle\Placeholder;

use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;

class PlaceholderFilter
{
    /**
     * @var OroEntityProvider
     */
    protected $oroProvider;

    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskProvider;

    public function __construct(OroEntityProvider $oroProvider, ZendeskEntityProvider $zendeskProvider)
    {
        $this->oroProvider = $oroProvider;
        $this->zendeskProvider = $zendeskProvider;
    }

    /**
     * @param mixed $entity
     * @return bool
     */
    public function isTicketAvailable($entity)
    {
        if (!$entity instanceof CaseEntity) {
            return false;
        }

        return null !== $this->zendeskProvider->getTicketByCase($entity);
    }

    /**
     * @param mixed $entity
     * @return bool
     */
    public function isSyncApplicableForCaseEntity($entity)
    {
        if (!$entity instanceof CaseEntity) {
            return false;
        }

        return !$this->isTicketAvailable($entity)
            && count($this->oroProvider->getEnabledTwoWaySyncChannels($entity)) > 0;
    }
}
