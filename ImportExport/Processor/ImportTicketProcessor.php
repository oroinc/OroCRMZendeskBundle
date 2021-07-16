<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Processor;

use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet\ChangeSet;
use Oro\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet\ChangeValue;
use Oro\Bundle\ZendeskBundle\Model\SyncHelper\TicketSyncHelper;
use Oro\Bundle\ZendeskBundle\Model\SyncState;

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
     * @var OroEntityProvider
     */
    protected $oroProvider;

    public function __construct(TicketSyncHelper $helper, SyncState $syncState, OroEntityProvider $oroEntityProvider)
    {
        $this->helper = $helper;
        $this->syncState = $syncState;
        $this->oroProvider = $oroEntityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof Ticket) {
            throw new InvalidArgumentException(
                sprintf(
                    'Imported entity must be instance of Oro\\Bundle\\ZendeskBundle\\Entity\\Ticket, %s given.',
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
            $this->updateCaseRelatedAccount($entity->getRelatedCase());
        }

        $this->syncState->addTicketId($entity->getOriginId());

        return $entity;
    }

    public function updateCaseRelatedAccount(CaseEntity $caseEntity)
    {
        $contact = $caseEntity->getRelatedContact();
        if (!$contact) {
            return;
        }

        $account = $this->oroProvider->getAccountByContact($contact);
        if (!$account) {
            return;
        }

        $caseEntity->setRelatedAccount($account);
    }

    /**
     * Apply changes depending on sync strategy.
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
