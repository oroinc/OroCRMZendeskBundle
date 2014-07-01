<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;

class TicketCommentNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldRules()
    {
        return array(
            'originId' => array(
                'normalizeName' => 'id',
                'primary' => true,
            ),
            'ticket_id' => array(
                'denormalize' => false,
                'denormalizeName' => 'ticket',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
                'context' => array('mode' => self::SHORT_MODE),
            ),
            'author' => array(
                'normalizeName' => 'author_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
                'context' => array('mode' => self::SHORT_MODE),
            ),
            'body',
            'htmlBody' => array(
                'normalizeName' => 'html_body',
                'normalize' => false,
            ),
            'public',
            'originCreatedAt' => array(
                'type' => 'DateTime',
                'normalizeName' => 'created_at',
                'normalize' => false,
                'context' => array('type' => 'datetime'),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        /**
         * @var TicketComment $ticketComment
         */
        $ticketComment = parent::denormalize($data, $class, $format, $context);
        $ticketComment->setChannel($this->getChannel($context));
        return $ticketComment;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment';
    }
}
