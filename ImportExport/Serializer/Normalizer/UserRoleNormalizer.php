<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ZendeskBundle\Entity\UserRole;

/**
 * Normalizer for Zendesk UserRole entities with name-based identification
 */
class UserRoleNormalizer extends AbstractNormalizer
{
    #[\Override]
    protected function getFieldRules()
    {
        return array(
            'name' => array(
                'primary' => true
            ),
        );
    }

    #[\Override]
    protected function getTargetClassName()
    {
        return 'Oro\\Bundle\\ZendeskBundle\\Entity\\UserRole';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [UserRole::class => true];
    }
}
