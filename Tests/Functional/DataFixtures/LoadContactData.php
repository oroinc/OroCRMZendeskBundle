<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ContactBundle\Entity\ContactEmail;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadContactData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
        'contact:jim.smith@example.com' => [
            'firstName' => 'Jim',
            'lastName' => 'Smith',
            'email' => 'jim.smith@example.com'
        ],
        'contact:mike.johnson@example.com' => [
            'firstName' => 'Mike',
            'lastName' => 'Johnson',
            'email' => 'mike.johnson@example.com'
        ],
        'contact:alex.johnson@example.com' => [
            'firstName' => 'Alex',
            'lastName' => 'Johnson',
            'email' => 'alex.johnson@example.com'
        ],
        'contact:alex.miller@example.com' => [
            'firstName' => 'Alex',
            'lastName' => 'Miller',
            'email' => 'alex.miller@example.com'
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $reference => $data) {
            $entity = new Contact();
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $entity->setFirstName($data['firstName']);
            $entity->setLastName($data['lastName']);
            $entity->addEmail($this->createContactEmail($data['email']));
            $manager->persist($entity);
            $this->setReference($reference, $entity);
        }
        $manager->flush();
    }

    private function createContactEmail(string $email): ContactEmail
    {
        $result = new ContactEmail();
        $result->setEmail($email);
        $result->setPrimary(true);

        return $result;
    }
}
