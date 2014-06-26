<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;

class ChannelType implements ChannelInterface
{
    const TYPE = 'zendesk';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.zendesk.channel_type.label';
    }
}
