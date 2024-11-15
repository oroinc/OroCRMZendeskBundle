<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

class LoadTransportData extends AbstractFixture
{
    private array $transportData = [
        'zendesk_transport:first_test_transport' => [
            'url' => 'https://zendesk.com',
            'email' => 'test@mail.com',
            'token' => '12e25c5f-ec0b-4578-bf95-6a02ffd44f1c',
            'zendeskUserEmail' => 'fred.taylor@example.com'
        ],
        'zendesk_transport:second_test_transport' => [
            'url' => 'https://zendesk.com',
            'email' => 'test@mail.com',
            'token' => '12e25c5f-ec0b-4578-bf95-6a02ffd44f1c',
            'zendeskUserEmail' => 'fred.taylor@example.com'
        ]
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->transportData as $reference => $data) {
            $entity = new ZendeskRestTransport();
            $entity->setUrl($data['url']);
            $entity->setEmail($data['email']);
            $entity->setToken($data['token']);
            $entity->setZendeskUserEmail($data['zendeskUserEmail']);
            $this->setReference($reference, $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
