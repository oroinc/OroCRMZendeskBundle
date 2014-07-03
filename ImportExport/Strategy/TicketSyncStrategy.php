<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Psr\Log\LoggerAwareInterface;

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

        $this->getLogger()->setMessagePrefix("Zendesk Ticket [id={$entity->getOriginId()}]: ");

        $this->helper->setLogger($this->getLogger());
        $this->helper->refreshEntity($entity, $this->getChannel());

        $existingTicket = $this->helper->findEntity($entity, $this->getChannel());
        if ($existingTicket) {
            if ($existingTicket->getOriginUpdatedAt() == $entity->getOriginUpdatedAt()) {
                return null;
            }

            $this->helper->copyEntityProperties($existingTicket, $entity);

            $entity = $existingTicket;

            $this->getLogger()->info("Update found Zendesk ticket.");
            $this->getContext()->incrementUpdateCount();
        } else {
            $this->getLogger()->info("Add new Zendesk ticket.");
            $this->getContext()->incrementAddCount();
        }

        $this->helper->syncRelatedEntities($entity, $this->getChannel());

        $this->syncState->addTicketId($entity->getOriginId());

        return $entity;
    }
}
