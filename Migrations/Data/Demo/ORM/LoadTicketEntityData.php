<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\CaseBundle\Migrations\Data\Demo\ORM\LoadCaseEntityData;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\User as OroUser;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;
use Oro\Bundle\ZendeskBundle\Model\EntityMapper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadTicketEntityData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const TICKET_COUNT = 10;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $entitiesCount;

    /**
     * @var array|null
     */
    protected $cases = null;

    /**
     * @var int
     */
    protected $ticketOriginId = 1000000;

    /**
     * @var int
     */
    protected $ticketCommentOriginId = 1000000;

    /**
     * @var int
     */
    protected $userOriginId = 1000000;

    protected $zendeskUsers = array();

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->entityManager = $manager;

        $demoZendeskChannel = $this->getReference('oro_zendesk:zendesk_demo_channel');

        for ($i = 0; $i < static::TICKET_COUNT; ++$i) {
            $ticket = $this->createTicketEntity($demoZendeskChannel);

            if (!$ticket) {
                continue;
            }

            $manager->persist($ticket);

            $this->createTicketComments($ticket);
        }

        $manager->flush();
    }

    /**
     * @param Channel $channel
     * @return Ticket|null
     */
    protected function createTicketEntity(Channel $channel)
    {
        $this->ticketOriginId++;
        $case = $this->getCase();
        if (!$case) {
            return null;
        }
        $requester = $this->getZendeskUserByUser($this->getRandomEntity('OroUserBundle:User'));
        $assignee = $this->getZendeskUserByUser($case->getAssignedTo());
        $data = array(
            'originId' => $this->ticketOriginId,
            'url' => "https://company.zendesk.com/api/v2/tickets/{$this->ticketOriginId}.json",
            'recipient' => "{$this->ticketOriginId}_support@company.com",
            'requester' => $requester,
            'assignee'  => $assignee,
            'hasIncidents' => rand(0, 1),
            'dueAt' => $this->getRandomDate(),
            'createdAt' => $this->getRandomDate(),
            'updatedAt' => $this->getRandomDate(),
            'relatedCase' => $case,
            'externalId' => uniqid(),
            'subject' => $case->getSubject(),
            'description' => $case->getDescription(),
            'collaborators' => new ArrayCollection(array($requester))
        );

        if ($data['hasIncidents']) {
            $type = $this->entityManager->getRepository('OroZendeskBundle:TicketType')
                ->findOneBy(array('name' => TicketType::TYPE_PROBLEM));
        } else {
            $type = $this->getRandomEntity('OroZendeskBundle:TicketType');
        }

        /**
         * @var EntityMapper $entityMapper
         */
        $entityMapper = $this->container->get('oro_zendesk.entity_mapper');

        $status = $entityMapper->getTicketStatus($case->getStatus()->getName());

        $priority = $entityMapper->getTicketPriority($case->getPriority()->getName());

        if (!$type || !$status || !$priority) {
            return null;
        }

        $data['type'] = $type;
        $data['status'] = $status;
        $data['priority'] = $priority;

        $data['channel'] = $channel;

        $contact = $case->getRelatedContact();
        if (!$contact) {
            $data['submitter'] = $data['requester'];
        } else {
            $data['submitter'] = $this->getZendeskUserByContact($contact);
        }

        $ticket = new Ticket();

        $this->setObjectValues($data, $ticket);

        return $ticket;
    }

    /**
     * @return \DateTime
     */
    protected function getRandomDate()
    {
        $result = new \DateTime();
        $result->sub(new \DateInterval(sprintf('P%dDT%dM', rand(0, 30), rand(0, 1440))));

        return $result;
    }

    /**
     * @return CaseEntity|false
     */
    protected function getCase()
    {
        if ($this->cases === null) {
            $this->cases = $this->entityManager->createQueryBuilder()
                ->select('e')
                ->from('OroCaseBundle:CaseEntity', 'e')
                ->setMaxResults(LoadCaseEntityData::CASES_COUNT)
                ->getQuery()
                ->execute();
        } else {
            next($this->cases);
        }

        return current($this->cases);
    }

    /**
     * @param OroUser $user
     * @return User
     */
    protected function getZendeskUserByUser(OroUser $user)
    {
        $email = $user->getEmail();
        if (array_key_exists($email, $this->zendeskUsers)) {
            return $this->zendeskUsers[$email];
        }

        $zendeskUser = new User();
        $name = $user->getFirstName().' '.$user->getLastName();
        $roleName = rand(0, 1) ? UserRole::ROLE_AGENT : UserRole::ROLE_ADMIN;
        $role = $this->getRoleByName($roleName);
        $zendeskUser->setOriginId($this->userOriginId++)
            ->setRelatedUser($user)
            ->setEmail($user->getEmail())
            ->setRole($role)
            ->setName($name);

        $this->entityManager->persist($zendeskUser);
        $this->zendeskUsers[$email] = $zendeskUser;

        return $zendeskUser;
    }

    /**
     * @param Contact $contact
     * @return User
     */
    protected function getZendeskUserByContact(Contact $contact)
    {
        $email = $contact->getEmail();
        if (array_key_exists($email, $this->zendeskUsers)) {
            return $this->zendeskUsers[$email];
        }

        $zendeskUser = new User();
        $name = $contact->getFirstName() . ' ' . $contact->getLastName();
        $role = $this->getRoleByName(UserRole::ROLE_END_USER);

        $zendeskUser->setOriginId($this->userOriginId++)
            ->setRelatedContact($contact)
            ->setEmail($email)
            ->setName($name)
            ->setRole($role);

        $this->entityManager->persist($zendeskUser);
        $this->zendeskUsers[$email] = $zendeskUser;

        return $zendeskUser;
    }

    protected function createTicketComments(Ticket $ticket)
    {
        $comments = $ticket->getRelatedCase()->getComments();

        /**
         * @var CaseComment $comment
         */
        foreach ($comments as $comment) {
            $ticketComment = new TicketComment();
            $author = $this->getZendeskUserByUser($comment->getOwner());
            $ticketComment->setOriginId($this->ticketCommentOriginId++)
                ->setAuthor($author)
                ->setBody($comment->getMessage())
                ->setHtmlBody($comment->getMessage())
                ->setCreatedAt($comment->getCreatedAt())
                ->setPublic($comment->isPublic())
                ->setTicket($ticket)
                ->setRelatedComment($comment);

            $this->entityManager->persist($ticketComment);
        }
    }

    /**
     * @param string $entityName
     * @return object|null
     */
    protected function getRandomEntity($entityName)
    {
        $count = $this->getEntityCount($entityName);

        if ($count) {
            return $this->entityManager->createQueryBuilder()
                ->select('e')
                ->from($entityName, 'e')
                ->setFirstResult(rand(0, $count - 1))
                ->setMaxResults(1)
                ->orderBy('e.' . $this->entityManager->getClassMetadata($entityName)->getSingleIdentifierFieldName())
                ->getQuery()
                ->getSingleResult();
        }

        return null;
    }

    /**
     * @param string $entityName
     * @return int
     */
    protected function getEntityCount($entityName)
    {
        if (!isset($this->entitiesCount[$entityName])) {
            $this->entitiesCount[$entityName] = (int)$this->entityManager->createQueryBuilder()
                ->select('COUNT(e)')
                ->from($entityName, 'e')
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->entitiesCount[$entityName];
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array(
            'Oro\Bundle\CaseBundle\Migrations\Data\Demo\ORM\LoadCaseEntityData',
            'Oro\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM\LoadChannelData'
        );
    }

    /**
     * @param array $data
     * @param object $target
     */
    protected function setObjectValues(array $data, $target)
    {
        $reflectionClass = new \ReflectionClass($target);

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();
            if (!array_key_exists($propertyName, $data)) {
                continue;
            }
            $value = $data[$propertyName];
            $reflectionClass->getMethod('set' . ucfirst($propertyName))
                ->invoke($target, $value);
        }
    }

    /**
     * @param $roleName
     * @return null|object
     */
    protected function getRoleByName($roleName)
    {
        $role = $this->entityManager->getRepository('OroZendeskBundle:UserRole')
            ->findOneBy(array('name' => $roleName));

        return $role;
    }
}
