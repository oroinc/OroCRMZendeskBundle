<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;

class LoadZendeskUserData extends AbstractZendeskFixture implements DependentFixtureInterface
{
    private array $data = [
        [
            'reference' => 'zendesk_user:fred.taylor@example.com',
            'originId' => 1015,
            'url' => 'https://foo.zendesk.com/api/v2/users/1015.json',
            'name' => 'Fred Taylor',
            'email' => 'fred.taylor@example.com',
            'role' => UserRole::ROLE_AGENT,
            'channel' => 'zendesk_channel:first_test_channel',
            'originUpdatedAt' => '2014-06-09T17:45:22Z'
        ],
        [
            'reference' => 'zendesk_user:james.cook@example.com',
            'originId' => 1016,
            'url' => 'https://foo.zendesk.com/api/v2/users/1016.json',
            'name' => 'James Cook',
            'email' => 'james.cook@example.com',
            'role' => UserRole::ROLE_AGENT,
            'relatedUser' => 'user:james.cook@example.com',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        [
            'reference' => 'zendesk_user:anna.lee@example.com',
            'originId' => 1017,
            'url' => 'https://foo.zendesk.com/api/v2/users/1017.json',
            'name' => 'Anna Lee',
            'email' => 'anna.lee@example.com',
            'role' => UserRole::ROLE_AGENT,
            'relatedUser' => 'user:anna.lee@example.com',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        [
            'reference' => 'zendesk_user:jim.smith@example.com',
            'originId' => 1010,
            'url' => 'https://foo.zendesk.com/api/v2/users/1010.json',
            'name' => 'Jim Smith',
            'email' => 'jim.smith@example.com',
            'role' => UserRole::ROLE_END_USER,
            'relatedContact' => 'contact:jim.smith@example.com',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        [
            'reference' => 'zendesk_user:alex.taylor@example.com',
            'originId' => 1011,
            'url' => 'https://foo.zendesk.com/api/v2/users/1011.json',
            'name' => 'Alex Taylor',
            'email' => 'alex.taylor@example.com',
            'role' => UserRole::ROLE_END_USER,
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        [
            'reference' => 'zendesk_user:sam.rogers@example.com',
            'name' => 'Sam Rogers',
            'email' => 'sam.rogers@example.com',
            'role' => UserRole::ROLE_END_USER,
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        [
            'reference' => 'zendesk_user:garry.smith@example.com',
            'name' => 'Garry Smith',
            'email' => 'garry.smith@example.com',
            'role' => UserRole::ROLE_END_USER,
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        [
            'reference' => 'zendesk_user:alex.miller@example.com',
            'name' => 'Alex Miller',
            'email' => 'alex.miller@example.com',
            'role' => UserRole::ROLE_END_USER,
            'relatedContact' => 'contact:alex.miller@example.com',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new User();
            if (isset($data['reference'])) {
                $this->addReference($data['reference'], $entity);
            }
            if (isset($data['role'])) {
                $data['role'] = $manager->find(UserRole::class, $data['role']);
            }
            if (isset($data['relatedUser'])) {
                $data['relatedUser'] = $this->getReference($data['relatedUser']);
            }
            if (isset($data['channel'])) {
                $data['channel'] = $this->getReference($data['channel']);
            }
            if (isset($data['relatedContact'])) {
                $data['relatedContact'] = $this->getReference($data['relatedContact']);
            }
            if (isset($data['originUpdatedAt'])) {
                $data['originUpdatedAt'] = new \DateTime($data['originUpdatedAt']);
            }
            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $this->setReference($entity->getEmail(), $entity);

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
            'Oro\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadContactData',
            'Oro\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadOroUserData',
            'Oro\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadChannelData'
        ];
    }
}
