<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

class LoadTransportData extends AbstractZendeskFixture
{
    private array $transportData = [
        [
            'reference' => 'zendesk_transport:first_test_transport',
            'url' => 'https://zendesk.com',
            'email' => 'test@mail.com',
            'token' => '12e25c5f-ec0b-4578-bf95-6a02ffd44f1c',
            'zendeskUserEmail' => 'fred.taylor@example.com'
        ],
        [
            'reference' => 'zendesk_transport:second_test_transport',
            'url' => 'https://zendesk.com',
            'email' => 'test@mail.com',
            'token' => '12e25c5f-ec0b-4578-bf95-6a02ffd44f1c',
            'zendeskUserEmail' => 'fred.taylor@example.com'
        ]
    ];
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->transportData as $data) {
            $entity = new ZendeskRestTransport();
            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
