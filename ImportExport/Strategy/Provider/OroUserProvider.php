<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User as OroUser;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;

class OroUserProvider
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

    /**
     * @param ZendeskUser $user
     * @return OroUser|null
     */
    public function getUser(ZendeskUser $user)
    {
        $oroUser = $this->entityManager->getRepository('OroUserBundle:User')
            ->findOneBy(array('email' => $user->getEmail()));

        return $oroUser;
    }
}
