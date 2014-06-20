<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use OroCRM\Bundle\ContactBundle\Entity\Contact;

use Doctrine\Common\Persistence\ObjectManager;
use OroCRM\Bundle\ContactBundle\Entity\ContactEmail;

class LoadContactData extends AbstractZendeskFixture
{
    /**
     * @var array
     */
    protected $data = array(
        array(
            'firstName' => 'Jim',
            'lastName' => 'Smith',
            'email' => 'jim.smith@contact.com',
        ),
        array(
            'firstName' => 'Mike',
            'lastName' => 'Johnson',
            'email' => 'mike.johnson@contact.com',
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new Contact();

            $this->setEntityPropertyValues($entity, $data);
            $email = new ContactEmail();
            $email->setPrimary(true);
            $email->setEmail($data['email']);
            $entity->addEmail($email);
            $this->setReference($entity->getEmail(), $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
