<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Processor;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet\ChangeSet;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet\ChangeValue;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\TicketSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Model\SyncState;

class ImportTicketProcessor extends AbstractImportProcessor
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
        $this->helper->refreshTicket($entity, $this->getChannel());

        $existingTicket = $this->helper->findTicket($entity, $this->getChannel());

        if ($existingTicket) {
            // Updating existing
            $this->getLogger()->info("Update found Zendesk ticket.");

            $ticketRemoteChanges = $this->helper->calculateTicketsChanges($existingTicket, $entity);

            if (!isset($ticketRemoteChanges['originUpdatedAt'])) {
                $this->getLogger()->info("Updating is skipped due to updated date is not changed.");
                return null;
            }

            $relatedCaseLocalChanges = $this->helper->calculateRelatedCaseChanges($existingTicket, $this->getChannel());
            $entity = $existingTicket;

            $ticketRemoteChanges->apply();

            $relatedCaseRemoteChanges = $this->helper->calculateRelatedCaseChanges($entity, $this->getChannel());


            $this->applyRemoteChanges($relatedCaseLocalChanges, $relatedCaseRemoteChanges);

            $this->getContext()->incrementUpdateCount();
        } else {
            // Creating new
            $this->getLogger()->info("Add new Zendesk ticket.");
            $this->getContext()->incrementAddCount();

            $relatedCaseChanges = $this->helper->calculateRelatedCaseChanges($entity, $this->getChannel());
            $relatedCaseChanges->apply();
            $contact = $entity->getRelatedCase()
                ->getRelatedContact();
            if ($relatedCaseChanges['relatedContact'] && $contact && $contact->hasAccounts()) {
                $entity->getRelatedCase()
                    ->setRelatedAccount(
                        $contact->getAccounts()
                            ->first()
                    );
            }
        }

        $this->syncState->addTicketId($entity->getOriginId());

        return $entity;
    }

    /**
     * Apply changes depending on sync strategy.
     *
     * @param ChangeSet $localChanges
     * @param ChangeSet $remoteChanges
     */
    protected function applyRemoteChanges(ChangeSet $localChanges, ChangeSet $remoteChanges)
    {
        switch ($this->getSyncPriority()) {
            case TwoWaySyncConnectorInterface::LOCAL_WINS:
                // Skip conflicting changes that are present both in local and remote changes.
                /** @var ChangeValue $changeValue */
                foreach ($localChanges as $targetProperty => $changeValue) {
                    unset($remoteChanges[$targetProperty]);
                }
                $remoteChanges->apply();
                break;
            default:
            case TwoWaySyncConnectorInterface::REMOTE_WINS:
                // Don't care about local changes, override all with remote changes.
                $remoteChanges->apply();
                break;
        }
    }

    /**
     * @return string
     */
    protected function getSyncPriority()
    {
        return $this->getChannel()->getSynchronizationSettings()->offsetGetOr('syncPriority');
    }
}
