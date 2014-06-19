<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;

class LoadTicketEntityData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    protected static $ticketsData = array(
        array(
            'id' => 42,
            'assignee' => 'orocrm_zendesk_user',
            'requester' => 'orocrm_zendesk_user_second',
            'submitter' => 'orocrm_zendesk_user',
            'recipient' => 'test@mail.com',
            'externalId' => '7e24caa0-87f7-44d6-922b-0330ed9fd06c',
            'url' => 'test.com',
            'hasIncidents' => true,
            'case' => 'orocrm_zendesk_case',
            'priority' => TicketPriority::PRIORITY_URGENT,
            'priority_label' => null,
            'status' => TicketStatus::STATUS_PENDING,
            'status_label' => null,
            'type' => TicketType::TYPE_PROBLEM,
            'type_label' => null,
            'collaborators' => array('orocrm_zendesk_user', 'orocrm_zendesk_user_second')
         )
    );

    /**
     * @var ContainerInterface
     */
    protected $container;

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
        $priorityRepository = $manager->getRepository('OroCRMZendeskBundle:TicketPriority');
        $statusRepository = $manager->getRepository('OroCRMZendeskBundle:TicketStatus');
        $typeRepository = $manager->getRepository('OroCRMZendeskBundle:TicketType');

        foreach (static::$ticketsData as &$ticketParams) {
            $ticket = new Ticket();
            $collaborators = new ArrayCollection();
            foreach ($ticketParams['collaborators'] as $collaborator) {
                $user = $this->getReference($collaborator);
                $collaborators->add($user);
            }
            $priority = $priorityRepository->findOneBy(array('name' => $ticketParams['priority']));
            $status = $statusRepository->findOneBy(array('name' => $ticketParams['status']));
            $type = $typeRepository->findOneBy(array('name' => $ticketParams['type']));
            $ticketParams['priority_label'] = $priority->getLabel();
            $ticketParams['status_label'] = $status->getLabel();
            $ticketParams['type_label'] = $type->getLabel();
            $ticket->setId($ticketParams['id'])
                ->setAssignee($this->getReference($ticketParams['assignee']))
                ->setRecipient($ticketParams['recipient'])
                ->setRequester($this->getReference($ticketParams['requester']))
                ->setSubmitter($this->getReference($ticketParams['submitter']))
                ->setExternalId($ticketParams['externalId'])
                ->setHasIncidents($ticketParams['hasIncidents'])
                ->setPriority($priority)
                ->setStatus($status)
                ->setSubject($this->getReference($ticketParams['case'])->getSubject())
                ->setType($type)
                ->setUrl($ticketParams['url'])
                ->setDueAt(new \DateTime())
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime())
                ->setCollaborators($collaborators)
                ->setCase($this->getReference($ticketParams['case']))
                ->setProblem($ticket);

            $manager->persist($ticket);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array(
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadCaseEntityData',
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadUserEntityData',
        );
    }

    /**
     * @return array
     */
    public static function getTicketsData()
    {
        return self::$ticketsData;
    }
}
