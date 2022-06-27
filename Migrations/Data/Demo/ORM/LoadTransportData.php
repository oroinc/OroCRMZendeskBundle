<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

/**
 * Load demo data settings for zendesk transport.
 */
class LoadTransportData extends AbstractFixture
{
    protected $transportData = [
        [
            'url' => 'https://demo.zendesk.com',
            'email' => 'demo@mail.com',
            'token' => 'c8541140-fdfe-11e3-a3ac-0800200c9a66',
            'zendeskUserEmail' => 'demo_user@mail.com',
            'reference' => 'oro_zendesk:zendesk_demo_transport'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $transport = new ZendeskRestTransport();

        $data = reset($this->transportData);
        $transport->setUrl($data['url']);
        $transport->setEmail($data['email']);
        $transport->setToken($data['token']);
        $transport->setZendeskUserEmail($data['zendeskUserEmail']);

        $manager->persist($transport);
        $this->setReference('oro_zendesk:zendesk_demo_transport', $transport);

        $manager->flush();
    }
}
