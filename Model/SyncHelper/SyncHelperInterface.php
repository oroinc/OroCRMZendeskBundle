<?php

namespace OroCRM\Bundle\ZendeskBundle\Model\SyncHelper;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface SyncHelperInterface
{
    /**
     * Finds existing Zendesk entity
     *
     * @param mixed $entity
     * @param Channel $channel
     * @return mixed|null
     */
    public function findEntity($entity, Channel $channel);

    /**
     * Refresh properties of Zendesk entity with data managed by entity manager
     *
     * @param mixed $entity
     * @param Channel $channel
     */
    public function refreshEntity($entity, Channel $channel);

    /**
     * Syncs properties of $target Zendesk entity with $source Zendesk entity
     *
     * @param mixed $target
     * @param mixed $source
     */
    public function syncEntities($target, $source);

    /**
     * Syncs related OroCRM entities of Zendesk entity
     *
     * @param mixed $entity
     * @param Channel $channel
     */
    public function syncRelatedEntities($entity, Channel $channel);
}
