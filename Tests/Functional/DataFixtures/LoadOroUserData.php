<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadOroUserData extends AbstractZendeskFixture
{
    /**
     * @var array
     */
    protected $data = array(
        array(
            'reference' => 'user:bob.miller@example.com',
            'username' => 'bob.miller',
            'email' => 'bob.miller@example.com',
            'plainPassword' => 'password',
        ),
        array(
            'reference' => 'user:james.cook@example.com',
            'username' => 'james.cook',
            'email' => 'james.cook@example.com',
            'plainPassword' => 'password',
        ),
        array(
            'reference' => 'user:john.smith@example.com',
            'username' => 'john.smith',
            'email' => 'john.smith@example.com',
            'plainPassword' => 'password',
        ),
        array(
            'reference' => 'user:anna.lee@example.com',
            'username' => 'anna.lee',
            'email' => 'anna.lee@example.com',
            'plainPassword' => 'password',
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');

        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        if ($admin) {
            $this->setReference('user:' . LoadAdminUserData::DEFAULT_ADMIN_EMAIL, $admin);
        }

        foreach ($this->data as $data) {
            $entity = $userManager->createUser();

            if (isset($data['reference'])) {
                $this->setReference($data['reference'], $entity);
            }

            $this->setEntityPropertyValues($entity, $data, array('reference'));

            $userManager->updateUser($entity, false);

            $entity->setOwner($admin->getOwner()); //for case controller test
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
