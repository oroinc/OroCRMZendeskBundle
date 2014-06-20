<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider;

use Doctrine\ORM\EntityManager;

use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;
use OroCRM\Bundle\ZendeskBundle\Entity\User;

class ZendeskEntityProvider
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
     * @param User $user
     * @return User|null
     */
    public function getUser(User $user)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:User')
            ->findOneByOriginId($user->getOriginId());

        return $result;
    }

    /**
     * @param UserRole $role
     * @return UserRole|null
     */
    public function getUserRole(UserRole $role)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:UserRole')->find($role->getName());

        return $result;
    }

    /**
     * @param Ticket $ticket
     * @return Ticket|null
     */
    public function getTicket(Ticket $ticket)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:Ticket')
            ->findOneBy(array('originId' => $ticket->getOriginId()));

        return $result;
    }

    /**
     * @param TicketComment $ticketComment
     * @return TicketComment|null
     */
    public function getTicketComment(TicketComment $ticketComment)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:TicketComment')
            ->findOneBy(array('originId' => $ticketComment->getOriginId()));

        return $result;
    }

    /**
     * @param TicketStatus $ticketStatus
     * @return TicketStatus|null
     */
    public function getTicketStatus(TicketStatus $ticketStatus)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:TicketStatus')
            ->find($ticketStatus->getName());

        return $result;
    }

    /**
     * @param TicketPriority $ticketPriority
     * @return TicketPriority|null
     */
    public function getTicketPriority(TicketPriority $ticketPriority)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:TicketPriority')
            ->find($ticketPriority->getName());

        return $result;
    }

    /**
     * @param TicketType $ticketType
     * @return TicketType|null
     */
    public function getTicketType(TicketType $ticketType)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:TicketType')
            ->find($ticketType->getName());

        return $result;
    }
}
