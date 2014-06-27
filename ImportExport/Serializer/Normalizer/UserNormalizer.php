<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use OroCRM\Bundle\ZendeskBundle\Entity\User;

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
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        /**
         * @var User $user
         */
        $user = parent::denormalize($data, $class, $format, $context);
        $user->setChannel($this->getChannel($context));
        return $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User';
    }
}
