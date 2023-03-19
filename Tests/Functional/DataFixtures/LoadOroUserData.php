<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadOroUserData extends AbstractZendeskFixture
{
    private array $data = [
        [
            'reference' => 'user:bob.miller@example.com',
            'username' => 'bob.miller',
            'email' => 'bob.miller@example.com',
            'plainPassword' => 'password',
        ],
        [
            'reference' => 'user:james.cook@example.com',
            'username' => 'james.cook',
            'email' => 'james.cook@example.com',
            'plainPassword' => 'password',
        ],
        [
            'reference' => 'user:john.smith@example.com',
            'username' => 'john.smith',
            'email' => 'john.smith@example.com',
            'plainPassword' => 'password',
        ],
        [
            'reference' => 'user:anna.lee@example.com',
            'username' => 'anna.lee',
            'email' => 'anna.lee@example.com',
            'plainPassword' => 'password',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]);

        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        if ($admin) {
            $this->setReference('user:' . LoadAdminUserData::DEFAULT_ADMIN_EMAIL, $admin);
        }

        foreach ($this->data as $data) {
            $entity = $userManager->createUser();
            if (isset($data['reference'])) {
                $this->setReference($data['reference'], $entity);
            }
            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $entity->addUserRole($role);
            $entity->setOwner($admin->getOwner()); //for case controller test

            $userManager->updateUser($entity, false);
        }

        $manager->flush();
    }
}
