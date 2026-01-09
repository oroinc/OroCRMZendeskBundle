<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Provides Zendesk channel type configuration for the integration bundle.
 */
class ChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    public const TYPE = 'zendesk';

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
