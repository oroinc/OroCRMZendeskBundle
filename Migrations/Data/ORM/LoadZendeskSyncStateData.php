<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskSyncState;

class LoadZendeskSyncStateData extends AbstractTranslatableEntityFixture
{
    /**
     * {@inheritdoc}
     */
    protected function loadEntities(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroCRMZendeskBundle:ZendeskSyncState');
        $syncState = $repository->find(ZendeskSyncState::STATE_ID);
        if (!$syncState) {
            $syncState = new ZendeskSyncState(ZendeskSyncState::STATE_ID);
            $manager->persist($syncState);
            $manager->flush();
        }
    }
}
