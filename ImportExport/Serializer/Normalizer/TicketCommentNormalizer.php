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
                'normalized' => 'id',
                'primary' => true,
            ),
            'author' => array(
                'normalized' => 'author_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User'
            ),
            'body',
            'htmlBody' => array(
                'normalized' => 'html_body',
            ),
            'public',
            'origin_created_at' => array(
                'type' => 'DateTime',
                'denormalized' => 'origin_created_at',
                'normalized' => 'created_at',
                'context' => array('type' => 'datetime'),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment';
    }
}
