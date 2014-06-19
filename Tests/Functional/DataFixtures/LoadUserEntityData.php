<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;

class LoadUserEntityData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    protected static $userData = array(
        array(
            'id' => 42,
            'reference' => 'orocrm_zendesk_user',
            'contact' => 'orocrm_zendesk_contact',
            'name' => 'alex smith',
            'url' => 'test.com',
            'email' => 'test@mail.com',
            'external_id' => '11d2bb20-cd96-4483-a325-ded55f04887b',
            'role' => UserRole::ROLE_END_USER
        ),
        array(
            'id' => 41,
            'url' => 'test.com',
            'email' => 'secondTest@mail.com',
            'name' => 'john smith',
            'external_id' => '12d2bb20-cd96-4483-a325-ded55f04887b',
            'reference' => 'orocrm_zendesk_user_second',
            'role' => UserRole::ROLE_ADMIN
        )
    );
    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $roleRepository = $manager->getRepository('OroCRMZendeskBundle:UserRole');
        foreach (static::$userData as $userParams) {
            $user = new User();
            $role = $roleRepository->findOneBy(array('name' => $userParams['role']));
            $user->setId($userParams['id'])
                ->setRole($role)
                ->setUrl($userParams['url'])
                ->setEmail($userParams['email'])
                ->setOnlyPrivateComments(true)
                ->setVerified(true)
                ->setActive(true)
                ->setNotes('')
                ->setPhone('')
                ->setLocale('')
                ->setTimeZone('')
                ->setAlias('')
                ->setDetails('')
                ->setTicketRestriction('')
                ->setLastLoginAt(new \DateTime())
                ->setExternalId($userParams['external_id'])
                ->setName($userParams['name'])
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());
            if (isset($userParams['contact'])) {
                $contact = $this->getReference($userParams['contact']);
                $user->setContact($contact);
            } else {
                $adminUser = $manager->getRepository('OroUserBundle:User')->findOneByUsername('admin');
                $user->setUser($adminUser);
            }

            $manager->persist($user);

            $this->setReference($userParams['reference'], $user);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array(
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadContactEntityData'
        );
    }

    /**
     * @return array
     */
    public static function getUserData()
    {
        return self::$userData;
    }
}
