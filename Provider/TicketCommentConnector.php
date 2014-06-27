<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

class TicketCommentConnector extends AbstractZendeskConnector
{
    const IMPORT_ENTITY = 'OroCRM\Bundle\ZendeskBundle\Entity\TicketComment';
    const TYPE = 'ticket_comment';
    const IMPORT_JOB = 'zendesk_ticket_comment_import';

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
