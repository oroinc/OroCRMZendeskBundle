<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

class UserNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldRules()
    {
        return array(
            'id' => array(
                'primary' => true,
            ),
            'url',
            'external_id',
            'name',
            'details',
            'ticket_restriction',
            'only_private_comments',
            'notes',
            'verified',
            'active',
            'alias',
            'email',
            'phone',
            'time_zone',
            'locale',
            'created_at' => array(
                'type' => 'DateTime',
                'context' => array('type' => 'datetime'),
            ),
            'updated_at' => array(
                'type' => 'DateTime',
                'context' => array('type' => 'datetime'),
            ),
            'role' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\UserRole',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User';
    }
}
