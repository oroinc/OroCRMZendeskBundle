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
                'denormalized' => 'originId',
                'normalized' => 'id',
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
            'origin_created_at' => array(
                'type' => 'DateTime',
                'denormalized' => 'origin_created_at',
                'normalized' => 'created_at',
                'context' => array('type' => 'datetime'),
            ),
            'origin_updated_at' => array(
                'type' => 'DateTime',
                'normalized' => 'updated_at',
                'denormalized' => 'origin_updated_at',
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
