<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;

class LoadOroUserData extends AbstractZendeskFixture
{
    /**
     * @var array
     */
    protected $data = array(
        array(
            'username' => 'bob.miller',
            'email' => 'bob.miller@orouser.com',
            'plainPassword' => 'password',
        ),
        array(
            'username' => 'james.cook',
            'email' => 'james.cook@orouser.com',
            'plainPassword' => 'password',
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        /**
         * @var User $adminUser
         */
        $adminUser = $manager->getRepository('OroUserBundle:User')->findOneByUsername('admin');
        foreach ($this->data as $data) {
            $entity = $userManager->createUser();

            $this->setEntityPropertyValues($entity, $data);
            $userManager->updateUser($entity, false);

            $this->setReference($entity->getEmail(), $entity);
            $entity->setOwner($adminUser->getOwner());//for case controller test
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
