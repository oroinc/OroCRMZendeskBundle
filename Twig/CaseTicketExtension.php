<?php

namespace OroCRM\Bundle\ZendeskBundle\Twig;

use Doctrine\ORM\EntityManager;

use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;

class CaseTicketExtension extends \Twig_Extension
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('orocrm_zendesk_get_case_ticket', array($this, 'getCaseTicket')),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'orocrm_zendesk_case_ticket';
    }

    /**
     * @param CaseEntity $case
     * @return Ticket|null
     */
    public function getCaseTicket(CaseEntity $case)
    {
        $repository = $this->entityManager->getRepository('OroCRMZendeskBundle:Ticket');
        return $repository->findOneBy(array('case' => $case));
    }
}
