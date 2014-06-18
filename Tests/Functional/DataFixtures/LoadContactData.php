<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use OroCRM\Bundle\ContactBundle\Entity\Contact;

use Doctrine\Common\Persistence\ObjectManager;

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
            $this->setReference($entity->getEmail(), $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
