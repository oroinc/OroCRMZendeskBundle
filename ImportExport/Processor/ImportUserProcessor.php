<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Processor;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use Oro\Bundle\ZendeskBundle\Model\SyncHelper\UserSyncHelper;

class ImportUserProcessor extends AbstractImportProcessor
{
    /**
     * @var UserSyncHelper
     */
    protected $helper;

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
                    'Imported entity must be instance of Oro\\Bundle\\ZendeskBundle\\Entity\\User, %s given.',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        if (!$this->validateOriginId($entity)) {
            return null;
        }

        $this->getLogger()->setMessagePrefix("Zendesk User [origin_id={$entity->getOriginId()}]: ");

        $this->helper->setLogger($this->getLogger());
        $this->helper->refreshTicket($entity, $this->getChannel());

        $existingUser = $this->helper->findUser($entity, $this->getChannel());
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

        $this->helper->syncRelatedEntities($entity);

        return $entity;
    }
}
