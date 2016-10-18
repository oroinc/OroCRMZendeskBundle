<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

class UserNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldRules()
    {
        return array(
            'id' => array(
                'denormalizeName' => 'originId',
                'normalizeName' => 'id',
                'primary' => true,
            ),
            'url' => array(
                'normalize' => false,
            ),
            'external_id',
            'name',
            'details',
            'ticket_restriction',
            'only_private_comments',
            'notes',
            'verified' => array(
                'normalize' => false,
            ),
            'active',
            'alias',
            'email',
            'phone',
            'time_zone',
            'locale',
            'originCreatedAt' => array(
                'type' => 'DateTime',
                'normalize' => false,
                'normalizeName' => 'created_at',
                'context' => array('type' => 'datetime'),
            ),
            'originUpdatedAt' => array(
                'type' => 'DateTime',
                'normalize' => false,
                'normalizeName' => 'updated_at',
                'context' => array('type' => 'datetime'),
            ),
            'role' => array(
                'type' => 'Oro\\Bundle\\ZendeskBundle\\Entity\\UserRole',
                'context' => array('mode' => self::SHORT_MODE),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return 'Oro\\Bundle\\ZendeskBundle\\Entity\\User';
    }
}
