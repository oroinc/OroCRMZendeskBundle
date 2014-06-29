<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\AbstractZendeskFixture;

class LoadTransportData extends AbstractZendeskFixture
{
    protected $transportData = array(
        array(
            'url' => 'https://demo.zendesk.com',
            'email' => 'demo@mail.com',
            'token' => 'c8541140-fdfe-11e3-a3ac-0800200c9a66',
            'zendeskUserEmail' => 'demo_user@mail.com',
            'reference' => 'orocrm_zendesk:zendesk_demo_transport'
        )
    );
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->transportData as $data) {
            $transport = new ZendeskRestTransport();

            $this->setEntityPropertyValues($transport, $data, array('reference'));
            $manager->persist($transport);
            $this->setReference($data['reference'], $transport);
        }

        $manager->flush();
    }
}
