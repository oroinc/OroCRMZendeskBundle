<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

/**
 * Comments connector
 */
class TicketCommentConnector extends AbstractZendeskConnector implements TwoWaySyncConnectorInterface
{
    const IMPORT_ENTITY = 'Oro\Bundle\ZendeskBundle\Entity\TicketComment';
    const TYPE = 'ticket_comment';
    const IMPORT_JOB = 'zendesk_ticket_comment_import';
    const EXPORT_JOB = 'zendesk_ticket_comment_export';

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
    public function getLabel(): string
    {
        return 'oro.zendesk.connector.ticket_comment.label';
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
