<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

class LoadTransportData extends AbstractFixture
{
    private array $transportData = [
        'zendesk_transport:first_test_transport' => [
            'url' => 'https://zendesk.com',
            'zendeskUserEmail' => 'fred.taylor@example.com',
        ],
        'zendesk_transport:second_test_transport' => [
            'url' => 'https://zendesk.com',
            'zendeskUserEmail' => 'fred.taylor@example.com',
        ],
    ];
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->transportData as $reference => $data) {
            $entity = new ZendeskRestTransport();
            $entity->setUrl($data['url']);
            $entity->setZendeskUserEmail($data['zendeskUserEmail']);
            $this->setReference($reference, $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
