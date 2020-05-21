<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider;

use Oro\Bundle\ZendeskBundle\Provider\ChannelType;

class ChannelTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChannelType */
    protected $channel;

    protected function setUp(): void
    {
        $this->channel = new ChannelType();
    }

    public function testPublicInterface()
    {
        $this->assertEquals('oro.zendesk.channel_type.label', $this->channel->getLabel());
    }
}
