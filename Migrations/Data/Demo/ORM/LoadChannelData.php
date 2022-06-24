<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;
use Oro\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
use Oro\Bundle\ZendeskBundle\Provider\TicketConnector;
use Oro\Bundle\ZendeskBundle\Provider\UserConnector;

/**
 * Load demo data settings for zendesk channel.
 */
class LoadChannelData extends AbstractFixture implements DependentFixtureInterface
{
    private const CHANNEL_DATA = [
        'name' => 'Demo Zendesk integration',
        'type' => ChannelType::TYPE,
        'connectors' => [
            TicketConnector::TYPE,
            UserConnector::TYPE,
            TicketCommentConnector::TYPE,
        ],
        'enabled' => false,
        'transport' => 'oro_zendesk:zendesk_demo_transport',
        'organization' => 'default_organization',
    ];

    public function getDependencies()
    {
        return [
            LoadTransportData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $channel = new Channel();

        $channel->setTransport($this->getReference(self::CHANNEL_DATA['transport']));
        $channel->setOrganization($this->getReference(self::CHANNEL_DATA['organization']));
        $channel->setConnectors(self::CHANNEL_DATA['connectors']);
        $channel->setEnabled(self::CHANNEL_DATA['enabled']);
        $channel->setName(self::CHANNEL_DATA['name']);
        $channel->setType(self::CHANNEL_DATA['type']);

        $manager->persist($channel);
        $this->setReference('oro_zendesk:zendesk_demo_channel', $channel);
        $manager->flush();
    }
}
