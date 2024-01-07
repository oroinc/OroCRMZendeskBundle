<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;

class LoadZendeskUserData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
        'zendesk_user:fred.taylor@example.com' => [
            'originId' => 1015,
            'url' => 'https://foo.zendesk.com/api/v2/users/1015.json',
            'name' => 'Fred Taylor',
            'email' => 'fred.taylor@example.com',
            'role' => UserRole::ROLE_AGENT,
            'channel' => 'zendesk_channel:first_test_channel',
            'originUpdatedAt' => '2014-06-09T17:45:22Z'
        ],
        'zendesk_user:james.cook@example.com' => [
            'originId' => 1016,
            'url' => 'https://foo.zendesk.com/api/v2/users/1016.json',
            'name' => 'James Cook',
            'email' => 'james.cook@example.com',
            'role' => UserRole::ROLE_AGENT,
            'relatedUser' => 'user:james.cook@example.com',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'zendesk_user:anna.lee@example.com' => [
            'originId' => 1017,
            'url' => 'https://foo.zendesk.com/api/v2/users/1017.json',
            'name' => 'Anna Lee',
            'email' => 'anna.lee@example.com',
            'role' => UserRole::ROLE_AGENT,
            'relatedUser' => 'user:anna.lee@example.com',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'zendesk_user:jim.smith@example.com' => [
            'originId' => 1010,
            'url' => 'https://foo.zendesk.com/api/v2/users/1010.json',
            'name' => 'Jim Smith',
            'email' => 'jim.smith@example.com',
            'role' => UserRole::ROLE_END_USER,
            'relatedContact' => 'contact:jim.smith@example.com',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'zendesk_user:alex.taylor@example.com' => [
            'originId' => 1011,
            'url' => 'https://foo.zendesk.com/api/v2/users/1011.json',
            'name' => 'Alex Taylor',
            'email' => 'alex.taylor@example.com',
            'role' => UserRole::ROLE_END_USER,
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'zendesk_user:sam.rogers@example.com' => [
            'name' => 'Sam Rogers',
            'email' => 'sam.rogers@example.com',
            'role' => UserRole::ROLE_END_USER,
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'zendesk_user:garry.smith@example.com' => [
            'name' => 'Garry Smith',
            'email' => 'garry.smith@example.com',
            'role' => UserRole::ROLE_END_USER,
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'zendesk_user:alex.miller@example.com' => [
            'name' => 'Alex Miller',
            'email' => 'alex.miller@example.com',
            'role' => UserRole::ROLE_END_USER,
            'relatedContact' => 'contact:alex.miller@example.com',
            'channel' => 'zendesk_channel:first_test_channel'
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadContactData::class,
            LoadOroUserData::class,
            LoadChannelData::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $reference => $data) {
            $entity = new User();
            $entity->setName($data['name']);
            $entity->setEmail($data['email']);
            $entity->setRole($manager->find(UserRole::class, $data['role']));
            $entity->setChannel($this->getReference($data['channel']));
            if (isset($data['originId'])) {
                $entity->setOriginId($data['originId']);
            }
            if (isset($data['url'])) {
                $entity->setUrl($data['url']);
            }
            if (isset($data['relatedUser'])) {
                $entity->setRelatedUser($this->getReference($data['relatedUser']));
            }
            if (isset($data['relatedContact'])) {
                $entity->setRelatedContact($this->getReference($data['relatedContact']));
            }
            if (isset($data['originUpdatedAt'])) {
                $entity->setOriginUpdatedAt(new \DateTime($data['originUpdatedAt']));
            }
            $this->setReference($entity->getEmail(), $entity);
            $manager->persist($entity);
            $this->addReference($reference, $entity);
        }
        $manager->flush();
    }
}
