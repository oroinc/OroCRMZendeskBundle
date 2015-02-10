<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

class SetOrganizationData extends AbstractTranslatableEntityFixture
{
    const MAX_RESULTS = 1000;

    /**
     * Load entities to DB
     *
     * @param ObjectManager $manager
     */
    protected function loadEntities(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroOrganizationBundle:Organization');
        $defaultOrganization = $repository->getFirst();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $manager->getRepository('OroCRMContactBundle:Contact')->createQueryBuilder('entity');
        $criteria = new Criteria();
        $criteria->where($criteria->expr()->isNull('organization'));
        $queryBuilder->addCriteria($criteria);
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(self::MAX_RESULTS);

        $result = $queryBuilder->getQuery()->execute();
        while(count($result)) {
            foreach ($result as $entity) {
                /** @var Contact $entity */
                if ($entity->getOwner() && $entity->getOwner()->getOrganization()) {
                    $organization = $entity->getOwner()->getOrganization();
                } else {
                    $organization = $defaultOrganization;
                }
                $entity->setOrganization($organization);
                $manager->persist($entity);
            }
            $manager->flush();
            $manager->clear();
            $result = $queryBuilder->getQuery()->execute();
        }
    }
}
