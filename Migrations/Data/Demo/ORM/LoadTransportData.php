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
    private const TRANSPORT_SETTINGS = [
        'url' => 'https://demo.zendesk.com',
        'email' => 'demo@mail.com',
        'token' => 'c8541140-fdfe-11e3-a3ac-0800200c9a66',
        'zendeskUserEmail' => 'demo_user@mail.com'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $transport = new ZendeskRestTransport();
        $transport->setUrl(self::TRANSPORT_SETTINGS['url']);
        $transport->setEmail(self::TRANSPORT_SETTINGS['email']);
        $transport->setToken(self::TRANSPORT_SETTINGS['token']);
        $transport->setZendeskUserEmail(self::TRANSPORT_SETTINGS['zendeskUserEmail']);

        $manager->persist($transport);
        $this->setReference('oro_zendesk:zendesk_demo_transport', $transport);

        $manager->flush();
    }
}
