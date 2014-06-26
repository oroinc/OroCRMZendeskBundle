<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider;

use OroCRM\Bundle\ZendeskBundle\Provider\ChannelType;

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
        $this->assertEquals('orocrm.zendesk.channel_type.label', $this->channel->getLabel());
    }
}
