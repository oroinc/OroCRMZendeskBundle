<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

class TicketCommentConnector extends AbstractZendeskConnector
{
    const IMPORT_ENTITY = 'OroCRM\Bundle\ZendeskBundle\Entity\TicketComment';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $comments = new \AppendIterator();
        foreach ($this->syncState->getTicketIds() as $ticketId) {
            $ticketComments = $this->transport->getTicketComments($ticketId);
            $comments->append($ticketComments);
        }
        return $comments;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.zendesk.connector.ticket_comment.label';
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
        return 'zendesk_ticket_comment_import';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ticket_comment';
    }
}
