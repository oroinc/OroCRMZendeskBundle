<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadChannelData extends AbstractZendeskFixture implements DependentFixtureInterface
{
    protected $channelData = array(
        array(
            'name' => 'zendesk',
            'type' => 'zendesk',
            'transport' => 'zendesk_transport:test@mail.com',
            'enabled' => true,
            'reference' => 'zendesk_channel:test@mail.com'
        )
    );
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        foreach ($this->channelData as $data) {
            $entity = new Channel();

            $data['transport'] = $this->getReference($data['transport']);

            $entity->setDefaultUserOwner($admin);

            $this->setEntityPropertyValues($entity, $data, array('reference'));
            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array(
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTransportData'
        );
    }
}
