<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;

class LoadTicketData extends AbstractZendeskFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = array(
        array(
            'reference' => 'zendesk_ticket_43',
            'originId' => 43,
            'url' => 'https://foo.zendesk.com/api/v2/tickets/43.json',
            'subject' => 'Zendesk Ticket 43',
            'description' => 'Zendesk Ticket 43 Description',
            'externalId' => '456546544564564564',
            'type' => TicketType::TYPE_PROBLEM,
            'status' => TicketStatus::STATUS_OPEN,
            'priority' => TicketPriority::PRIORITY_NORMAL,
            'requester' => 'alex.taylor@zendeskagent.com',
            'submitter' => 'fred.taylor@zendeskagent.com',
            'assignee' => 'fred.taylor@zendeskagent.com',
            'createdAt' => '2014-06-05T12:24:23Z',
            'updatedAt' => '2014-06-05T13:43:21Z',
            'relatedCase' => 'orocrm_zendesk_case_2',
        ),
        array(
            'reference' => 'zendesk_ticket_42',
            'originId' => 42,
            'url' => 'https://foo.zendesk.com/api/v2/tickets/42.json',
            'subject' => 'Zendesk Ticket 42',
            'description' => 'Zendesk Ticket 42 Description',
            'externalId' => '7e24caa0-87f7-44d6-922b-0330ed9fd06c',
            'problem' => 'zendesk_ticket_43',
            'collaborators' => array('fred.taylor@zendeskagent.com', 'alex.taylor@zendeskagent.com'),
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_PENDING,
            'priority' => TicketPriority::PRIORITY_URGENT,
            'recipient' => 'test@mail.com',
            'requester' => 'alex.taylor@zendeskagent.com',
            'submitter' => 'fred.taylor@zendeskagent.com',
            'assignee' => 'fred.taylor@zendeskagent.com',
            'hasIncidents' => true,
            'createdAt' => '2014-06-10T15:54:22Z',
            'updatedAt' => '2014-06-10T17:45:31Z',
            'dueAt' => '2014-06-11T12:13:11Z',
            'relatedCase' => 'orocrm_zendesk_case_1',
         ),
    );

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new Ticket();

            if (isset($data['reference'])) {
                $this->addReference($data['reference'], $entity);
            }

            if (isset($data['collaborators'])) {
                $collaborators = new ArrayCollection();
                foreach ($data['collaborators'] as $user) {
                    $collaborators->add($this->getReference($user));
                }
                $data['collaborators'] = $collaborators;
            }

            $data['priority'] = $manager->find('OroCRMZendeskBundle:TicketPriority', $data['priority']);
            $data['status'] = $manager->find('OroCRMZendeskBundle:TicketStatus', $data['status']);
            $data['type'] = $manager->find('OroCRMZendeskBundle:TicketType', $data['type']);

            if (isset($data['createdAt'])) {
                $data['createdAt'] = new \DateTime($data['createdAt']);
            }
            if (isset($data['updatedAt'])) {
                $data['updatedAt'] = new \DateTime($data['updatedAt']);
            }
            if (isset($data['dueAt'])) {
                $data['dueAt'] = new \DateTime($data['dueAt']);
            }
            if (isset($data['problem'])) {
                $data['problem'] = $this->getReference($data['problem']);
            }
            if (isset($data['requester'])) {
                $data['requester'] = $this->getReference($data['requester']);
            }
            if (isset($data['submitter'])) {
                $data['submitter'] = $this->getReference($data['submitter']);
            }
            if (isset($data['assignee'])) {
                $data['assignee'] = $this->getReference($data['assignee']);
            }
            if (isset($data['relatedCase'])) {
                $data['relatedCase'] = $this->getReference($data['relatedCase']);
            }

            $this->setEntityPropertyValues($entity, $data, array('reference'));

            $manager->persist($entity);
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
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadZendeskUserData',
        );
    }
}
