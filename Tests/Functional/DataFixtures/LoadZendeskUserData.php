<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;

class LoadZendeskUserData extends AbstractZendeskFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = array(
        array(
            'originId' => 1015,
            'url' => 'https://foo.zendesk.com/api/v2/users/1015.json',
            'name' => 'Fred Taylor',
            'email' => 'fred.taylor@zendeskagent.com',
            'role' => UserRole::ROLE_AGENT,
        ),
        array(
            'originId' => 1016,
            'url' => 'https://foo.zendesk.com/api/v2/users/1016.json',
            'name' => 'James Cook',
            'email' => 'james.cook@orouser.com',
            'role' => UserRole::ROLE_AGENT,
            'relatedUser' => 'james.cook@orouser.com',
        ),
        array(
            'originId' => 1010,
            'url' => 'https://foo.zendesk.com/api/v2/users/1010.json',
            'name' => 'Robert Williams',
            'email' => 'robert.williams@zendeskagent.com',
            'role' => UserRole::ROLE_END_USER,
            'relatedContact' => 'jim.smith@contact.com',
        ),
        array(
            'originId' => 1011,
            'url' => 'https://foo.zendesk.com/api/v2/users/1011.json',
            'name' => 'Alex Taylor',
            'email' => 'alex.taylor@zendeskagent.com',
            'role' => UserRole::ROLE_END_USER,
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new User();
            if (isset($data['role'])) {
                $data['role'] = $manager->find('OroCRMZendeskBundle:UserRole', $data['role']);
            }
            if (isset($data['relatedUser'])) {
                $data['relatedUser'] = $this->getReference($data['relatedUser']);
            }
            if (isset($data['relatedContact'])) {
                $data['relatedContact'] = $this->getReference($data['relatedContact']);
            }
            $this->setEntityPropertyValues($entity, $data);
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
        return array(
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadContactData',
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadOroUserData',
        );
    }
}
