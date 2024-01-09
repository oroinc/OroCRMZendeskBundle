<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface
{
    private array $channelData = [
        'zendesk_channel:first_test_channel' => [
            'name' => 'zendesk',
            'type' => 'zendesk',
            'transport' => 'zendesk_transport:first_test_transport',
            'enabled' => true,
            'synchronizationSettings' => ['isTwoWaySyncEnabled' => true]
        ],
        'zendesk_channel:second_test_channel' => [
            'name' => 'zendesk_second',
            'type' => 'zendesk',
            'transport' => 'zendesk_transport:second_test_transport',
            'enabled' => true,
            'synchronizationSettings' => ['isTwoWaySyncEnabled' => false]
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadTransportData::class, LoadOrganization::class, LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->channelData as $reference => $data) {
            $entity = new Channel();
            $entity->setDefaultUserOwner($this->getReference(LoadUser::USER));
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setEnabled($data['enabled']);
            $entity->setTransport($this->getReference($data['transport']));
            foreach ($data['synchronizationSettings'] as $key => $value) {
                $entity->getSynchronizationSettingsReference()->offsetSet($key, $value);
            }
            $this->setReference($reference, $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
