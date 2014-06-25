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
            array(
                'denormalizeName' => 'originId',
                'normalizeName' => 'id',
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
            'originCreatedAt' => array(
                'type' => 'DateTime',
                'normalizeName' => 'created_at',
                'context' => array('type' => 'datetime'),
            ),
            'originUpdatedAt' => array(
                'type' => 'DateTime',
                'normalizeName' => 'updated_at',
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
