<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

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
            'ticket' => array(
                'normalizeName' => 'ticket_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
                'context' => array('mode' => self::SHORT_MODE),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (is_array($data) && isset($context['ticket_id'])) {
            $data['ticket_id'] = $context['ticket_id'];
        }
        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment';
    }
}
