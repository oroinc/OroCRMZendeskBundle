<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ContactBundle\Entity\ContactEmail;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadContactData extends AbstractZendeskFixture
{
    private array $data = [
        [
            'reference' => 'contact:jim.smith@example.com',
            'firstName' => 'Jim',
            'lastName'  => 'Smith',
            'email'     => 'jim.smith@example.com',
        ],
        [
            'reference' => 'contact:mike.johnson@example.com',
            'firstName' => 'Mike',
            'lastName'  => 'Johnson',
            'email'     => 'mike.johnson@example.com',
        ],
        [
            'reference' => 'contact:alex.johnson@example.com',
            'firstName' => 'Alex',
            'lastName'  => 'Johnson',
            'email'     => 'alex.johnson@example.com',
        ],
        [
            'reference' => 'contact:alex.miller@example.com',
            'firstName' => 'Alex',
            'lastName'  => 'Miller',
            'email'     => 'alex.miller@example.com',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository(Organization::class)->getFirst();
        foreach ($this->data as $data) {
            $entity = new Contact();

            $entity->setOrganization($organization);
            $this->setEntityPropertyValues($entity, $data, ['email', 'reference']);

            if (isset($data['reference'])) {
                $this->setReference($data['reference'], $entity);
            }

            $entity->addEmail($this->createContactEmail($data['email'], true));

            $manager->persist($entity);
        }

        $manager->flush();
    }

    private function createContactEmail(string $email, bool $primary = true): ContactEmail
    {
        $result = new ContactEmail();
        $result->setEmail($email);
        $result->setPrimary($primary);

        return $result;
    }
}
