<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadChannelData extends AbstractZendeskFixture implements DependentFixtureInterface
{
    private array $channelData = [
        [
            'name' => 'zendesk',
            'type' => 'zendesk',
            'transport' => 'zendesk_transport:first_test_transport',
            'enabled' => true,
            'reference' => 'zendesk_channel:first_test_channel',
            'synchronizationSettings' => [
                'isTwoWaySyncEnabled' => true
            ],
        ],
        [
            'name' => 'zendesk_second',
            'type' => 'zendesk',
            'transport' => 'zendesk_transport:second_test_transport',
            'enabled' => true,
            'reference' => 'zendesk_channel:second_test_channel',
            'synchronizationSettings' => [
                'isTwoWaySyncEnabled' => false
            ],
        ]
    ];
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        $organization = $manager->getRepository(Organization::class)->getFirst();
        foreach ($this->channelData as $data) {
            $entity = new Channel();

            $data['transport'] = $this->getReference($data['transport']);

            $entity->setDefaultUserOwner($admin);
            $entity->setOrganization($organization);

            $this->setEntityPropertyValues($entity, $data, ['reference', 'synchronizationSettings']);
            $this->setReference($data['reference'], $entity);

            if (isset($data['synchronizationSettings'])) {
                foreach ($data['synchronizationSettings'] as $key => $value) {
                    $entity->getSynchronizationSettingsReference()->offsetSet($key, $value);
                }
            }

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTransportData'
        ];
    }
}
