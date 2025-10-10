<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;

/**
 * Normalizer for Zendesk TicketPriority entities with name-based identification
 */
class TicketPriorityNormalizer extends AbstractNormalizer
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
        return 'Oro\\Bundle\\ZendeskBundle\\Entity\\TicketPriority';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [TicketPriority::class => true];
    }
}
