<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\UserSyncHelper;

class UserSyncStrategy extends AbstractSyncStrategy
{
    /**
     * @var UserSyncHelper
     */
    protected $helper;

    /**
     * @param UserSyncHelper $helper
     */
    public function __construct(UserSyncHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof ZendeskUser) {
            throw new InvalidArgumentException(
                sprintf(
                    'Imported entity must be instance of OroCRM\\Bundle\\ZendeskBundle\\Entity\\User, %s given.',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        if (!$this->validateOriginId($entity)) {
            return null;
        }

        $this->getLogger()->setMessagePrefix("Zendesk User [id={$entity->getOriginId()}]: ");

        $this->helper->setLogger($this->getLogger());
        $this->helper->refreshEntity($entity, $this->getChannel());

        $existingUser = $this->helper->findEntity($entity, $this->getChannel());
        if ($existingUser) {
            if ($existingUser->getOriginUpdatedAt() == $entity->getOriginUpdatedAt()) {
                return null;
            }

            $this->helper->copyEntityProperties($existingUser, $entity);
            $entity = $existingUser;

            $this->getLogger()->info("Update found Zendesk user.");
            $this->getContext()->incrementUpdateCount();
        } else {
            $this->getLogger()->info("Add new Zendesk user.");
            $this->getContext()->incrementAddCount();
        }

        $this->helper->syncRelatedEntities($entity, $this->getChannel());

        return $entity;
    }
}
