<?php

namespace OroCRM\Bundle\ZendeskBundle\Model\EntityProvider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User as OroUser;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

class ZendeskEntityProvider
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var User[]
     */
    private $userList = array();

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param User    $user
     * @param Channel $channel
     * @param bool    $defaultIfNotExist
     * @return User|null
     */
    public function getUser(User $user, Channel $channel, $defaultIfNotExist = false)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:User')
            ->findOneBy(array('originId' => $user->getOriginId(), 'channel' => $channel));

        if (!$result && $defaultIfNotExist) {
            return $this->getDefaultZendeskUser($channel);
        }

        return $result;
    }

    /**
     * @param OroUser $oroUser
     * @param Channel $channel
     * @param bool    $defaultIfNotExist
     * @return null|User
     */
    public function getUserByOroUser(OroUser $oroUser, Channel $channel, $defaultIfNotExist = false)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:User')
            ->findOneBy(array('relatedUser' => $oroUser, 'channel' => $channel));

        if (!$result && $defaultIfNotExist) {
            return $this->getDefaultZendeskUser($channel);
        }

        return $result;
    }

    /**
     * @param Channel $channel
     * @return null|User
     * @throws \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     */
    public function getDefaultZendeskUser(Channel $channel)
    {
        $transport = $channel->getTransport();
        if (!$transport instanceof ZendeskRestTransport) {
            throw new InvalidArgumentException();
        }

        $email = $transport->getZendeskUserEmail();

        return $this->entityManager->getRepository('OroCRMZendeskBundle:User')
            ->findOneBy(array('email' => $email, 'channel' => $channel));
    }

    /**
     * @param Contact $contact
     * @param Channel $channel
     * @return null|User
     */
    public function getUserByContact(Contact $contact, Channel $channel)
    {
        $userUid = "{$contact->getId()}_{$channel->getId()}";

        if (isset($this->userList[$userUid])) {
            return $this->userList[$userUid];
        }

        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:User')
            ->findOneBy(array('relatedContact' => $contact, 'channel' => $channel));

        if ($result) {
            return $result;
        }

        $user = new User();
        $user->setChannel($channel);
        $email = $contact->getPrimaryEmail();
        if (!$email) {
            $emails = $contact->getEmails();
            if ($emails->count() > 0) {
                $email = $emails->first();
            } else {
                $email = $contact->getEmail();
            }
        }

        if (empty($email)) {
            return null;
        }

        $name = "{$contact->getFirstName()} {$contact->getLastName()}";
        $phone = $contact->getPrimaryPhone();
        if ($phone) {
            $user->setPhone($phone->getPhone());
        }
        $role = $this->getUserRoleByName(UserRole::ROLE_END_USER);
        $user->setEmail($email)
            ->setActive(true)
            ->setName($name)
            ->setRelatedContact($contact)
            ->setRole($role);

        $this->userList[$userUid] = $user;

        return $user;
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
     * @param string $roleName
     * @return UserRole|null
     */
    public function getUserRoleByName($roleName)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:UserRole')
            ->find($roleName);

        return $result;
    }

    /**
     * @param Ticket  $ticket
     * @param Channel $channel
     * @return Ticket|null
     */
    public function getTicket(Ticket $ticket, Channel $channel)
    {
        return $this->getTicketByOriginId($ticket->getOriginId(), $channel);
    }

    /**
     * @param CaseEntity $caseEntity
     * @return null|Ticket
     */
    public function getTicketByCase(CaseEntity $caseEntity)
    {
        return $this->entityManager->getRepository('OroCRMZendeskBundle:Ticket')
            ->findOneBy(array('relatedCase' => $caseEntity));
    }

    /**
     * @param string  $originId
     * @param Channel $channel
     * @return Ticket|null
     */
    public function getTicketByOriginId($originId, Channel $channel)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:Ticket')
            ->findOneBy(array('originId' => $originId, 'channel' => $channel));

        return $result;
    }

    /**
     * @param TicketComment $ticketComment
     * @param Channel       $channel
     * @return TicketComment|null
     */
    public function getTicketComment(TicketComment $ticketComment, Channel $channel)
    {
        $result = $this->entityManager->getRepository('OroCRMZendeskBundle:TicketComment')
            ->findOneBy(array('originId' => $ticketComment->getOriginId(), 'channel' => $channel));

        return $result;
    }

    /**
     * @param CaseComment $caseComment
     * @return null|TicketComment
     */
    public function getTicketCommentByCaseComment(CaseComment $caseComment)
    {
        return $this->entityManager->getRepository('OroCRMZendeskBundle:TicketComment')
            ->findOneBy(array('relatedComment' => $caseComment));
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
