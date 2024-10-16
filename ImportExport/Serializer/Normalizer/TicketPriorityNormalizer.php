<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

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
}
