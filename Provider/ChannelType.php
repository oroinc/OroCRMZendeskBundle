<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class ChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'zendesk';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.zendesk.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/orocrmzendesk/img/zendesk.com.ico';
    }
}
