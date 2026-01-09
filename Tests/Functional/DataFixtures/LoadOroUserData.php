<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadOroUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    private array $data = [
        'user:bob.miller@example.com' => [
            'username' => 'bob.miller',
            'email' => 'bob.miller@example.com',
            'plainPassword' => 'password'
        ],
        'user:james.cook@example.com' => [
            'username' => 'james.cook',
            'email' => 'james.cook@example.com',
            'plainPassword' => 'password'
        ],
        'user:john.smith@example.com' => [
            'username' => 'john.smith',
            'email' => 'john.smith@example.com',
            'plainPassword' => 'password'
        ],
        'user:anna.lee@example.com' => [
            'username' => 'anna.lee',
            'email' => 'anna.lee@example.com',
            'plainPassword' => 'password'
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $userManager = $this->container->get('oro_user.manager');
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]);
        /** @var User $admin */
        $admin = $this->getReference(LoadUser::USER);
        $this->setReference('user:admin@example.com', $admin);
        foreach ($this->data as $reference => $data) {
            /** @var User $entity */
            $entity = $userManager->createUser();
            $entity->setUsername($data['username']);
            $entity->setEmail($data['email']);
            $entity->setPlainPassword($data['plainPassword']);
            $entity->addUserRole($role);
            $entity->setOwner($admin->getOwner()); //for case controller test
            $this->setReference($reference, $entity);
            $userManager->updateUser($entity, false);
        }
        $manager->flush();
    }
}
