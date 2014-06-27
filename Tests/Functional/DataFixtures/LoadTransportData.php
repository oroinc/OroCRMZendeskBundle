<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

class LoadTransportData extends AbstractZendeskFixture
{
    protected $transportData = array(
        array(
            'reference' => 'zendesk_transport:test@mail.com',
            'url' => 'https://zendesk.com',
            'email' => 'test@mail.com',
            'token' => '12e25c5f-ec0b-4578-bf95-6a02ffd44f1c'
        )
    );
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->transportData as $data) {
            $entity = new ZendeskRestTransport();
            $this->setEntityPropertyValues($entity, $data, array('reference'));
            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
