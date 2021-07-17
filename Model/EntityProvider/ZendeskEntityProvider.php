<?php

namespace Oro\Bundle\ZendeskBundle\Model\EntityProvider;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\User as OroUser;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

class ZendeskEntityProvider
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var User[] */
    private $rememberedUsers = [];

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function rememberUser(User $user, Channel $channel)
    {
        if (!$user->getOriginId()) {
            throw new \InvalidArgumentException('Expect user with originId.');
        }

        $key = $user->getOriginId() . '_' . $channel->getId();

        $this->rememberedUsers[$key] = $user;
    }

    /**
     * @param User|int $userOrOriginId
     * @param Channel  $channel
     *
     * @return User $user|null
     */
    protected function getRememberedUser($userOrOriginId, Channel $channel)
    {
        $originId = null;
        if ($userOrOriginId instanceof User) {
            $originId = $userOrOriginId->getOriginId();
        } elseif (is_numeric($userOrOriginId)) {
            $originId = $userOrOriginId;
        }

        if (!$originId) {
            return null;
        }

        $key = $originId . '_' . $channel->getId();

        return isset($this->rememberedUsers[$key]) ? $this->rememberedUsers[$key] : null;
    }

    /**
     * @param User    $user
     * @param Channel $channel
     * @param bool    $defaultIfNotExist
     *
     * @return User|null
     */
    public function getUser(User $user, Channel $channel, $defaultIfNotExist = false)
    {
        $result = $this->registry->getRepository('OroZendeskBundle:User')
            ->findOneBy(array('originId' => $user->getOriginId(), 'channel' => $channel));

        if (!$result) {
            $result = $this->getRememberedUser($user, $channel);
        }

        if (!$result && $defaultIfNotExist) {
            return $this->getDefaultZendeskUser($channel);
        }

        return $result;
    }

    /**
     * @param OroUser $oroUser
     * @param Channel $channel
     * @param bool    $defaultIfNotExist
     *
     * @return null|User
     */
    public function getUserByOroUser(OroUser $oroUser, Channel $channel, $defaultIfNotExist = false)
    {
        $emails = array();
        foreach ($oroUser->getEmails() as $email) {
            $emails[] = $email->getEmail();
        }

        if ($oroUser->getEmail()) {
            $emails[] = $oroUser->getEmail();
        }

        /** @var EntityRepository $repo */
        $repo = $this->registry->getRepository('OroZendeskBundle:User');

        /** @var User|null $user */
        $user = $repo->findOneBy(array('email' => $emails, 'channel' => $channel));

        if (!$user && $defaultIfNotExist) {
            return $this->getDefaultZendeskUser($channel);
        }

        return $user;
    }

    /**
     * @param Channel $channel
     *
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

        return $this->registry->getRepository('OroZendeskBundle:User')
            ->findOneBy(array('email' => $email, 'channel' => $channel));
    }

    /**
     * @param Contact $contact
     * @param Channel $channel
     *
     * @return null|User
     */
    public function getUserByContact(Contact $contact, Channel $channel)
    {
        $rememberUserId = 'contact_' . $contact->getId();

        $result = $this->getRememberedUser($rememberUserId, $channel);

        if ($result) {
            return $result;
        }

        $result = $this->registry->getRepository('OroZendeskBundle:User')
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

        $email = (string)$email;

        $name  = "{$contact->getFirstName()} {$contact->getLastName()}";
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

        $user->setOriginId($rememberUserId);
        $this->rememberUser($user, $channel);
        $user->setOriginId(null);

        return $user;
    }

    /**
     * @param UserRole $role
     *
     * @return UserRole|null
     */
    public function getUserRole(UserRole $role)
    {
        return $this->getUserRoleByName($role->getName());
    }

    /**
     * @param string $roleName
     *
     * @return UserRole|null
     */
    public function getUserRoleByName($roleName)
    {
        return $this->registry->getManager()->find('OroZendeskBundle:UserRole', $roleName);
    }

    /**
     * @param Ticket  $ticket
     * @param Channel $channel
     *
     * @return Ticket|null
     */
    public function getTicket(Ticket $ticket, Channel $channel)
    {
        return $this->getTicketByOriginId($ticket->getOriginId(), $channel);
    }

    /**
     * @param CaseEntity $caseEntity
     *
     * @return null|Ticket
     */
    public function getTicketByCase(CaseEntity $caseEntity)
    {
        return $this->registry->getRepository('OroZendeskBundle:Ticket')
            ->findOneBy(array('relatedCase' => $caseEntity));
    }

    /**
     * @param string  $originId
     * @param Channel $channel
     *
     * @return Ticket|null
     */
    public function getTicketByOriginId($originId, Channel $channel)
    {
        $result = $this->registry->getRepository('OroZendeskBundle:Ticket')
            ->findOneBy(array('originId' => $originId, 'channel' => $channel));

        return $result;
    }

    /**
     * @param Channel $channel
     *
     * @return \Iterator
     */
    public function getNotSyncedTicketComments(Channel $channel)
    {
        $qb = $this->registry->getRepository('OroZendeskBundle:TicketComment')
            ->createQueryBuilder('c')
            ->where('c.channel=:channel')
            ->andWhere('c.originId is NULL')
            ->setParameters(array('channel' => $channel));

        return new BufferedIdentityQueryResultIterator($qb);
    }

    /**
     * @param TicketComment $ticketComment
     * @param Channel       $channel
     *
     * @return TicketComment|null
     */
    public function getTicketComment(TicketComment $ticketComment, Channel $channel)
    {
        $result = $this->registry->getRepository('OroZendeskBundle:TicketComment')
            ->findOneBy(array('originId' => $ticketComment->getOriginId(), 'channel' => $channel));

        return $result;
    }

    /**
     * @param CaseComment $caseComment
     *
     * @return null|TicketComment
     */
    public function getTicketCommentByCaseComment(CaseComment $caseComment)
    {
        return $this->registry->getRepository('OroZendeskBundle:TicketComment')
            ->findOneBy(array('relatedComment' => $caseComment));
    }

    /**
     * @param TicketStatus $ticketStatus
     *
     * @return TicketStatus|null
     */
    public function getTicketStatus(TicketStatus $ticketStatus)
    {
        return $this->registry->getManager()->find('OroZendeskBundle:TicketStatus', $ticketStatus->getName());
    }

    /**
     * @param TicketPriority $ticketPriority
     *
     * @return TicketPriority|null
     */
    public function getTicketPriority(TicketPriority $ticketPriority)
    {
        return $this->registry->getManager()->find('OroZendeskBundle:TicketPriority', $ticketPriority->getName());
    }

    /**
     * @param TicketType $ticketType
     *
     * @return TicketType|null
     */
    public function getTicketType(TicketType $ticketType)
    {
        return $this->registry->getManager()->find('OroZendeskBundle:TicketType', $ticketType->getName());
    }
}
