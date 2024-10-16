<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class ChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'zendesk';

    #[\Override]
    public function getLabel()
    {
        return 'oro.zendesk.channel_type.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/orozendesk/img/zendesk.com.ico';
    }
}
