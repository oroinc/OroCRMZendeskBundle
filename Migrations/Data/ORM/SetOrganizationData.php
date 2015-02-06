<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

class SetOrganizationData extends AbstractTranslatableEntityFixture
{
    /**
     * Load entities to DB
     *
     * @param ObjectManager $manager
     */
    protected function loadEntities(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroOrganizationBundle:Organization');
        $organization = $repository->getFirst();

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->isNull('organization'));
        /** @var ArrayCollection $collection */
        $collection = $manager->getRepository('OroCRMContactBundle:Contact')->matching($criteria);

        foreach ($collection as $item) {
            /** @var Contact $item */
            $item->setOrganization($organization);
            $manager->persist($item);
        }

        $manager->flush();
    }
}
