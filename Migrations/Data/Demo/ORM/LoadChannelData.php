<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\ZendeskBundle\Provider\ChannelType;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketConnector;
use OroCRM\Bundle\ZendeskBundle\Provider\UserConnector;
use OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\AbstractZendeskFixture;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

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
            'transport'    => 'orocrm_zendesk:zendesk_demo_transport',
            'reference'    => 'orocrm_zendesk:zendesk_demo_channel',
            'organization' => null
        )
    );

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array(
            'OroCRM\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM\LoadTransportData'
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
