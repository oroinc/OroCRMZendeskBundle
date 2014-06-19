<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroCRM\Bundle\CaseBundle\Migrations\Data\Demo\ORM\LoadCaseEntityData;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\UserBundle\Entity\User as OroUser;

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
    protected $startId = 100;

    /**
     * @var int
     */
    protected $startUserId = 100;

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

        for ($i = 0; $i < static::TICKET_COUNT; ++$i) {
            $ticket = $this->createTicketEntity();

            if (!$ticket) {
                continue;
            }

            $manager->persist($ticket);
        }

        $manager->flush();
    }

    /**
     * @return Ticket|null
     */
    protected function createTicketEntity()
    {
        $this->startId++;
        $case = $this->getCase();
        if (!$case) {
            return null;
        }
        $requester = $this->getZendeskUserByUser($this->getRandomEntity('OroUserBundle:User'));
        $assignee = $this->getZendeskUserByUser($case->getAssignedTo());
        $data = array(
            'origin_id' => $this->startId,
            'url' => "https://company.zendesk.com/api/v2/tickets/{$this->startId}.json",
            'recipient' => "{$this->startId}_support@company.com",
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
            'collaborators' => new ArrayCollection(array($requester, $assignee))
        );

        if ($data['hasIncidents']) {
            $type = $this->entityManager->getRepository('OroCRMZendeskBundle:TicketType')
                ->findOneBy(array('name' => TicketType::TYPE_PROBLEM));
        } else {
            $type = $this->getRandomEntity('OroCRMZendeskBundle:TicketType');
        }

        $mapper = $this->container->get('orocrm_zendesk.mapper');

        $status = $this->entityManager->getRepository('OroCRMZendeskBundle:TicketStatus')
            ->findOneBy(array('name' => $mapper->getTicketStatus($case->getStatus()->getName())));

        $priority = $this->entityManager->getRepository('OroCRMZendeskBundle:TicketPriority')
            ->findOneBy(array('name' => $mapper->getTicketPriority($case->getPriority()->getName())));

        if (!$type || !$status || !$priority) {
            return null;
        }

        $data['type'] = $type;
        $data['status'] = $status;
        $data['priority'] = $priority;

        $contact = $case->getRelatedContact();
        if (!$contact) {
            $data['submitter'] = $data['requester'];
        } else {
            $data['submitter'] = $this->getZendeskUserByContact($contact);
        }

        $ticket = new Ticket();

        if (!$data['hasIncidents']) {
            $ticket->setProblem($ticket);
        }

        $this->setter($data, $ticket);

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
                ->from('OroCRMCaseBundle:CaseEntity', 'e')
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
        $zendeskUser = new User();
        $name = $user->getFirstName().' '.$user->getLastName();
        $zendeskUser->setOriginId($this->startUserId++)
            ->setRelatedUser($user)
            ->setEmail($user->getEmail())
            ->setName($name);

        $this->entityManager->persist($zendeskUser);

        return $zendeskUser;
    }

    /**
     * @param Contact $contact
     * @return User
     */
    protected function getZendeskUserByContact(Contact $contact)
    {
        $zendeskUser = new User();
        $name = $contact->getFirstName().' '.$contact->getLastName();
        $zendeskUser->setOriginId($this->startUserId++)
            ->setRelatedContact($contact)
            ->setEmail($contact->getEmail())
            ->setName($name);

        $this->entityManager->persist($zendeskUser);

        return $zendeskUser;
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
            'OroCRM\Bundle\CaseBundle\Migrations\Data\Demo\ORM\LoadCaseEntityData'
        );
    }

    /**
     * @param array $data
     * @param object $target
     */
    protected function setter(array $data, $target)
    {
        $reflectionClass = new \ReflectionClass($target);

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();
            if (!isset($data[$propertyName])) {
                continue;
            }
            $value = $data[$propertyName];
            $reflectionClass->getMethod('set' . ucfirst($propertyName))
                ->invoke($target, $value);
        }
    }
}
