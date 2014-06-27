<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

class TicketConnector extends AbstractZendeskConnector
{
    const IMPORT_ENTITY = 'OroCRM\Bundle\ZendeskBundle\Entity\Ticket';
    const IMPORT_JOB = 'zendesk_ticket_import';
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
        return self::IMPORT_JOB;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
