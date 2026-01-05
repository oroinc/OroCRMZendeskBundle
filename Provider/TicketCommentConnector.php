<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

/**
 * Comments connector
 */
class TicketCommentConnector extends AbstractZendeskConnector implements TwoWaySyncConnectorInterface
{
    public const IMPORT_ENTITY = 'Oro\Bundle\ZendeskBundle\Entity\TicketComment';
    public const TYPE = 'ticket_comment';
    public const IMPORT_JOB = 'zendesk_ticket_comment_import';
    public const EXPORT_JOB = 'zendesk_ticket_comment_export';

    #[\Override]
    protected function getConnectorSource()
    {
        $comments = new \AppendIterator();
        foreach ($this->syncState->getTicketIds() as $ticketId) {
            $ticketComments = $this->transport->getTicketComments($ticketId);
            $comments->append($ticketComments);
        }
        return $comments;
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.zendesk.connector.ticket_comment.label';
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
