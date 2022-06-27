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
    protected $channelData = [
        [
            'name'         => 'Demo Zendesk integration',
            'type'         => ChannelType::TYPE,
            'connectors'   => [
                TicketConnector::TYPE,
                UserConnector::TYPE,
                TicketCommentConnector::TYPE
            ],
            'enabled'      => 0,
            'transport'    => 'oro_zendesk:zendesk_demo_transport',
            'reference'    => 'oro_zendesk:zendesk_demo_channel',
            'organization' => 'default_organization'
        ]
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

        $data = reset($this->channelData);
        $channel->setTransport($this->getReference($data['transport']));
        $channel->setOrganization($this->getReference($data['organization']));
        $channel->setConnectors($data['connectors']);
        $channel->setEnabled($data['enabled']);
        $channel->setName($data['name']);
        $channel->setType($data['type']);

        $manager->persist($channel);
        $this->setReference('oro_zendesk:zendesk_demo_channel', $channel);
        $manager->flush();
    }
}
