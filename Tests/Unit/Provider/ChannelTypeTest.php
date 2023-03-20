<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider;

use Oro\Bundle\ZendeskBundle\Provider\ChannelType;

class ChannelTypeTest extends \PHPUnit\Framework\TestCase
{
    private ChannelType $channel;

    protected function setUp(): void
    {
        $this->channel = new ChannelType();
    }

    public function testGetLabel()
    {
        $this->assertEquals('oro.zendesk.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIcon()
    {
        $this->assertEquals('bundles/orozendesk/img/zendesk.com.ico', $this->channel->getIcon());
    }
}
