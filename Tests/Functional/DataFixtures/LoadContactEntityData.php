<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroCRM\Bundle\ContactBundle\Entity\Contact;

class LoadContactEntityData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var array
     */
    protected $contactsData = array(
        array(
            'firstName' => 'Daniel',
            'lastName'  => 'Case',
            'email'     => 'daniel.case@example.com',
            'reference' => 'orocrm_zendesk_contact'
        )
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $adminUser = $manager->getRepository('OroUserBundle:User')->findOneByUsername('admin');

        foreach ($this->contactsData as $contactData) {
            $contact = new Contact();
            $contact->setOwner($adminUser);
            $contact->setFirstName($contactData['firstName']);
            $contact->setLastName($contactData['lastName']);
            $contact->setEmail($contactData['email']);

            $manager->persist($contact);

            $this->setReference($contactData['reference'], $contact);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
