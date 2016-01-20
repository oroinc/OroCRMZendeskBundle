<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\CaseBundle\Entity\CasePriority;
use OroCRM\Bundle\CaseBundle\Entity\CaseStatus;

class LoadCaseEntityData extends AbstractZendeskFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = array(
        array(
            'subject'       => 'Case #1',
            'description'   => 'Case #1: Description',
            'assignedTo'    => 'user:bob.miller@example.com',
            'owner'         => 'user:bob.miller@example.com',
            'reference'     => 'orocrm_zendesk:case_1',
            'comments' => array(
                array(
                    'reference' => 'case_1_comment_1',
                    'message' => 'Comment 1',
                    'public' => true,
                    'owner' => 'user:james.cook@example.com',
                    'createdAt' => '2014-06-10T15:54:22Z',
                ),
                array(
                    'reference' => 'case_1_comment_2',
                    'message' => 'Comment 2',
                    'public' => true,
                    'contact' => 'contact:jim.smith@example.com',
                    'owner' => 'user:admin@example.com',
                    'createdAt' => '2014-06-10T15:54:22Z',
                ),
                array(
                    'reference' => 'case_1_comment_3',
                    'message' => 'Comment 3',
                    'public' => true,
                    'owner' => 'user:admin@example.com',
                    'createdAt' => '2014-06-10T16:54:22Z',
                ),
                array(
                    'reference' => 'case_1_comment_4',
                    'message' => 'Comment 4',
                    'public' => true,
                    'contact' => 'contact:alex.miller@example.com',
                    'owner' => 'user:admin@example.com',
                    'createdAt' => '2014-06-10T16:54:22Z',
                ),
            )
        ),
        array(
            'subject'       => 'Case #2',
            'description'   => 'Case #2: Description',
            'owner'         => 'user:admin@example.com',
            'reference'     => 'orocrm_zendesk:case_2'
        ),
        array(
            'subject'       => 'Case #3',
            'description'   => 'Case #3: Description',
            'owner'         => 'user:admin@example.com',
            'reference'     => 'orocrm_zendesk:case_3'
        ),
        array(
            'subject'       => 'Case #4',
            'description'   => 'Case #4: Description',
            'owner'         => 'user:admin@example.com',
            'reference'     => 'orocrm_zendesk:case_4'
        ),
        array(
            'subject'       => 'Case #5',
            'description'   => 'Case #5: Description',
            'reference'     => 'orocrm_zendesk:case_5',
            'owner'         => 'user:admin@example.com',
            'comments' => array(
                array(
                    'reference' => 'case_5_comment_1',
                    'message' => 'Comment 1',
                    'public' => true,
                    'owner' => 'user:james.cook@example.com',
                    'createdAt' => '2014-06-10T15:54:22Z',
                ),
                array(
                    'reference' => 'case_5_comment_2',
                    'message' => 'Comment 2',
                    'public' => true,
                    'contact' => 'contact:jim.smith@example.com',
                    'owner' => 'user:admin@example.com',
                    'createdAt' => '2014-06-10T15:54:22Z',
                ),
            )
        ),
        array(
            'subject'        => 'Case 6, Zendesk Ticket 44',
            'description'    => 'Case 6, Zendesk Ticket 44 Description',
            'owner'          => 'user:james.cook@example.com',
            'assignedTo'     => 'user:james.cook@example.com',
            'status'         => CaseStatus::STATUS_OPEN,
            'relatedContact' => 'contact:jim.smith@example.com',
            'priority'       => CasePriority::PRIORITY_LOW,
            'reference'      => 'orocrm_zendesk:case_6'
        ),
        array(
            'subject'        => 'Case 7, Zendesk Ticket 45',
            'description'    => 'Case 7, Zendesk Ticket 45 Description',
            'owner'          => 'user:james.cook@example.com',
            'assignedTo'     => 'user:james.cook@example.com',
            'status'         => CaseStatus::STATUS_OPEN,
            'relatedContact' => 'contact:jim.smith@example.com',
            'priority'       => CasePriority::PRIORITY_LOW,
            'reference'      => 'orocrm_zendesk:case_7'
        ),
    );

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function load(ObjectManager $manager)
    {
        $caseManager = $this->container->get('orocrm_case.manager');

        foreach ($this->data as $data) {
            $entity = $caseManager->createCase();

            if (isset($data['reference'])) {
                $this->setReference($data['reference'], $entity);
            }
            if (isset($data['owner'])) {
                $data['owner'] = $this->getReference($data['owner']);
            }
            if (isset($data['assignedTo'])) {
                $data['assignedTo'] = $this->getReference($data['assignedTo']);
            }
            if (isset($data['relatedContact'])) {
                $data['relatedContact'] = $this->getReference($data['relatedContact']);
            }
            if (isset($data['status'])) {
                $data['status'] = $manager->find('OroCRMCaseBundle:CaseStatus', $data['status']);
            }
            if (isset($data['priority'])) {
                $data['priority'] = $manager->find('OroCRMCaseBundle:CasePriority', $data['priority']);
            }
            $this->setEntityPropertyValues($entity, $data, array('reference', 'comments'));

            $manager->persist($entity);

            if (isset($data['comments'])) {
                foreach ($data['comments'] as $commentData) {
                    $comment = $caseManager->createComment($entity);

                    if (isset($commentData['reference'])) {
                        $this->setReference($commentData['reference'], $comment);
                    }
                    if (isset($commentData['owner'])) {
                        $commentData['owner'] = $this->getReference($commentData['owner']);
                    }
                    if (isset($commentData['contact'])) {
                        $commentData['contact'] = $this->getReference($commentData['contact']);
                    }
                    if (isset($commentData['createdAt'])) {
                        $commentData['createdAt'] = new \DateTime($commentData['createdAt']);
                    }

                    $this->setEntityPropertyValues($comment, $commentData, array('reference'));

                    $manager->persist($comment);
                }
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array(
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadOroUserData'
        );
    }
}
