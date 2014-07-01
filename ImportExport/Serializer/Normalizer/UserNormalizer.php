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
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\UserRole',
                'context' => array('mode' => self::SHORT_MODE),
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
