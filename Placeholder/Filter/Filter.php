<?php

namespace OroCRM\Bundle\ZendeskBundle\Placeholder\Filter;

use Doctrine\ORM\EntityManager;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;

class Filter
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function filter($entity)
    {
        if (!$entity instanceof CaseEntity) {
            return false;
        }

        $repository = $this->entityManager->getRepository('OroCRMZendeskBundle:Ticket');
        $ticket = $repository->findOneBy(array('relatedCase' => $entity));

        return $ticket != null;
    }
}
