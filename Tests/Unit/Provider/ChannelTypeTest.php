<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider;

use Oro\Bundle\ZendeskBundle\Provider\ChannelType;

class ChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChannelType */
    protected $channel;

    protected function setUp()
    {
        $this->channel = new ChannelType();
    }

    public function testPublicInterface()
    {
        $this->assertEquals('oro.zendesk.channel_type.label', $this->channel->getLabel());
    }
}
