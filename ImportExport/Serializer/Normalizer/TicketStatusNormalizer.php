<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;

/**
 * Normalizer for Zendesk TicketStatus entities with name-based identification
 */
class TicketStatusNormalizer extends AbstractNormalizer
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
        return 'Oro\\Bundle\\ZendeskBundle\\Entity\\TicketStatus';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [TicketStatus::class => true];
    }
}
