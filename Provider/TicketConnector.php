<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

class TicketConnector extends AbstractZendeskConnector
{
    const IMPORT_ENTITY = 'OroCRM\Bundle\ZendeskBundle\Entity\Ticket';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return $this->transport->getTickets($this->syncState->getLastSyncDate());
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.zendesk.connector.ticket.label';
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
        return 'zendesk_ticket_import';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ticket';
    }
}
