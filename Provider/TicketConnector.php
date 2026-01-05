<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

/**
 * Tickets connector
 */
class TicketConnector extends AbstractZendeskConnector implements TwoWaySyncConnectorInterface
{
    public const IMPORT_ENTITY = 'Oro\Bundle\ZendeskBundle\Entity\Ticket';
    public const IMPORT_JOB = 'zendesk_ticket_import';
    public const EXPORT_JOB = 'zendesk_ticket_export';
    public const TYPE = 'ticket';

    #[\Override]
    protected function getConnectorSource()
    {
        return $this->transport->getTickets($this->getLastSyncDate());
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.zendesk.connector.ticket.label';
    }

    #[\Override]
    public function getImportEntityFQCN()
    {
        return self::IMPORT_ENTITY;
    }

    #[\Override]
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    #[\Override]
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getExportJobName()
    {
        return self::EXPORT_JOB;
    }
}
