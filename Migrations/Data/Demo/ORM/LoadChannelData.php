<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;
use Oro\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
use Oro\Bundle\ZendeskBundle\Provider\TicketConnector;
use Oro\Bundle\ZendeskBundle\Provider\UserConnector;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\AbstractZendeskFixture;

class LoadChannelData extends AbstractZendeskFixture implements DependentFixtureInterface
{
    protected $channelData = array(
        array(
            'name'         => 'Demo Zendesk integration',
            'type'         => ChannelType::TYPE,
            'connectors'   => array(
                TicketConnector::TYPE,
                UserConnector::TYPE,
                TicketCommentConnector::TYPE
            ),
            'enabled'      => 0,
            'transport'    => 'oro_zendesk:zendesk_demo_transport',
            'reference'    => 'oro_zendesk:zendesk_demo_channel',
            'organization' => null
        )
    );

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array(
            'Oro\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM\LoadTransportData'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->channelData as $data) {
            $channel = new Channel();

            $data['transport']    = $this->getReference($data['transport']);
            $data['organization'] = $this->getReference('default_organization');

            $this->setEntityPropertyValues($channel, $data, array('reference'));
            $manager->persist($channel);

            $this->setReference($data['reference'], $channel);
        }

        $manager->flush();
    }
}
