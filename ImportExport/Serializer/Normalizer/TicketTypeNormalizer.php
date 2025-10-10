<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ZendeskBundle\Entity\TicketType;

/**
 * Normalizer for Zendesk TicketType entities with name-based identification
 */
class TicketTypeNormalizer extends AbstractNormalizer
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
        return 'Oro\\Bundle\\ZendeskBundle\\Entity\\TicketType';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [TicketType::class => true];
    }
}
