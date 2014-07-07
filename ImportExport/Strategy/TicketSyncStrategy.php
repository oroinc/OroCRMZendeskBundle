<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\TicketSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Model\SyncState;

class TicketSyncStrategy extends AbstractSyncStrategy
{
    /**
     * @var SyncState
     */
    protected $syncState;

    /**
     * @var TicketSyncHelper
     */
    protected $helper;

    /**
     * @param TicketSyncHelper $helper
     * @param SyncState $syncState
     */
    public function __construct(TicketSyncHelper $helper, SyncState $syncState)
    {
        $this->helper = $helper;
        $this->syncState = $syncState;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof Ticket) {
            throw new InvalidArgumentException(
                sprintf(
                    'Imported entity must be instance of OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket, %s given.',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }
        if (!$this->validateOriginId($entity)) {
            return null;
        }

        $this->getLogger()->setMessagePrefix("Zendesk Ticket [origin_id={$entity->getOriginId()}]: ");

        $this->helper->setLogger($this->getLogger());
        $this->helper->refreshEntity($entity, $this->getChannel());

        $existingTicket = $this->helper->findEntity($entity, $this->getChannel());
        if ($existingTicket) {
            $this->getLogger()->info("Update found Zendesk ticket.");

            if ($existingTicket->getOriginUpdatedAt() == $entity->getOriginUpdatedAt()) {
                $this->getLogger()->info("Updating is skipped due to updated date is not changed.");
                return null;
            }

            $this->getContext()->incrementUpdateCount();

            $syncPriority = $this->isTwoWaySyncEnabled() ? $this->getSyncPriority() : null;
            $this->helper->mergeTickets($existingTicket, $entity, $this->getChannel(), $syncPriority);

            $entity = $existingTicket;
        } else {
            $this->getLogger()->info("Add new Zendesk ticket.");
            $this->getContext()->incrementAddCount();

            $this->helper->syncRelatedEntities($entity, $this->getChannel());
        }

        $this->syncState->addTicketId($entity->getOriginId());

        return $entity;
    }

    /**
     * @return bool
     */
    protected function isTwoWaySyncEnabled()
    {
        $channel = $this->getChannel();
        return $channel && $channel->getSynchronizationSettings()->offsetGetOr('isTwoWaySyncEnabled', false);
    }

    /**
     * @return string
     */
    protected function getSyncPriority()
    {
        return $this->getChannel()->getSynchronizationSettings()->offsetGetOr('syncPriority');
    }
}
