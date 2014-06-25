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
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User'
            ),
            'body',
            'htmlBody' => array(
                'normalizeName' => 'html_body',
            ),
            'public',
            'originCreatedAt' => array(
                'type' => 'DateTime',
                'normalizeName' => 'created_at',
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
