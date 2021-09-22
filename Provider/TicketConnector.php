<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

/**
 * Tickets connector
 */
class TicketConnector extends AbstractZendeskConnector implements TwoWaySyncConnectorInterface
{
    const IMPORT_ENTITY = 'Oro\Bundle\ZendeskBundle\Entity\Ticket';
    const IMPORT_JOB = 'zendesk_ticket_import';
    const EXPORT_JOB = 'zendesk_ticket_export';
    const TYPE = 'ticket';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return $this->transport->getTickets($this->getLastSyncDate());
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.zendesk.connector.ticket.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return self::IMPORT_ENTITY;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @return string
     */
    public function getExportJobName()
    {
        return self::EXPORT_JOB;
    }
}
