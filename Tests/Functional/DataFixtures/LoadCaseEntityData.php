<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CaseBundle\Entity\CasePriority;
use Oro\Bundle\CaseBundle\Entity\CaseStatus;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadCaseEntityData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    private array $data = [
        'oro_zendesk:case_1' => [
            'subject' => 'Case #1',
            'description' => 'Case #1: Description',
            'assignedTo' => 'user:bob.miller@example.com',
            'owner' => 'user:bob.miller@example.com',
            'comments' => [
                'case_1_comment_1' => [
                    'message' => 'Comment 1',
                    'public' => true,
                    'owner' => 'user:james.cook@example.com',
                    'createdAt' => '2014-06-10T15:54:22Z'
                ],
                'case_1_comment_2' => [
                    'message' => 'Comment 2',
                    'public' => true,
                    'contact' => 'contact:jim.smith@example.com',
                    'owner' => 'user:admin@example.com',
                    'createdAt' => '2014-06-10T15:54:22Z'
                ],
                'case_1_comment_3' => [
                    'message' => 'Comment 3',
                    'public' => true,
                    'owner' => 'user:admin@example.com',
                    'createdAt' => '2014-06-10T16:54:22Z'
                ],
                'case_1_comment_4' => [
                    'message' => 'Comment 4',
                    'public' => true,
                    'contact' => 'contact:alex.miller@example.com',
                    'owner' => 'user:admin@example.com',
                    'createdAt' => '2014-06-10T16:54:22Z'
                ]
            ]
        ],
        'oro_zendesk:case_2' => [
            'subject' => 'Case #2',
            'description' => 'Case #2: Description',
            'owner' => 'user:admin@example.com'
        ],
        'oro_zendesk:case_3' => [
            'subject' => 'Case #3',
            'description' => 'Case #3: Description',
            'owner' => 'user:admin@example.com'
        ],
        'oro_zendesk:case_4' => [
            'subject' => 'Case #4',
            'description' => 'Case #4: Description',
            'owner' => 'user:admin@example.com'
        ],
        'oro_zendesk:case_5' => [
            'subject' => 'Case #5',
            'description' => 'Case #5: Description',
            'owner' => 'user:admin@example.com',
            'comments' => [
                'case_5_comment_1' => [
                    'message' => 'Comment 1',
                    'public' => true,
                    'owner' => 'user:james.cook@example.com',
                    'createdAt' => '2014-06-10T15:54:22Z'
                ],
                'case_5_comment_2' => [
                    'message' => 'Comment 2',
                    'public' => true,
                    'contact' => 'contact:jim.smith@example.com',
                    'owner' => 'user:admin@example.com',
                    'createdAt' => '2014-06-10T15:54:22Z'
                ]
            ]
        ],
        'oro_zendesk:case_6' => [
            'subject' => 'Case 6, Zendesk Ticket 44',
            'description' => 'Case 6, Zendesk Ticket 44 Description',
            'owner' => 'user:james.cook@example.com',
            'assignedTo' => 'user:james.cook@example.com',
            'status' => CaseStatus::STATUS_OPEN,
            'relatedContact' => 'contact:jim.smith@example.com',
            'priority' => CasePriority::PRIORITY_LOW
        ],
        'oro_zendesk:case_7' => [
            'subject' => 'Case 7, Zendesk Ticket 45',
            'description' => 'Case 7, Zendesk Ticket 45 Description',
            'owner' => 'user:james.cook@example.com',
            'assignedTo' => 'user:james.cook@example.com',
            'status' => CaseStatus::STATUS_OPEN,
            'relatedContact' => 'contact:jim.smith@example.com',
            'priority' => CasePriority::PRIORITY_LOW
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadContactData::class, LoadOroUserData::class];
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $caseManager = $this->container->get('oro_case.manager');
        foreach ($this->data as $reference => $data) {
            $entity = $caseManager->createCase();
            $entity->setSubject($data['subject']);
            $entity->setDescription($data['description']);
            $entity->setOwner($this->getReference($data['owner']));
            if (isset($data['assignedTo'])) {
                $entity->setAssignedTo($this->getReference($data['assignedTo']));
            }
            if (isset($data['relatedContact'])) {
                $entity->setRelatedContact($this->getReference($data['relatedContact']));
            }
            if (isset($data['status'])) {
                $entity->setStatus($manager->find(CaseStatus::class, $data['status']));
            }
            if (isset($data['priority'])) {
                $entity->setPriority($manager->find(CasePriority::class, $data['priority']));
            }
            $manager->persist($entity);
            $this->setReference($reference, $entity);

            if (isset($data['comments'])) {
                foreach ($data['comments'] as $commentReference => $commentData) {
                    $comment = $caseManager->createComment($entity);
                    $comment->setMessage($commentData['message']);
                    $comment->setPublic($commentData['public']);
                    $comment->setOwner($this->getReference($commentData['owner']));
                    $comment->setCreatedAt(new \DateTime($commentData['createdAt']));
                    if (isset($commentData['contact'])) {
                        $comment->setContact($this->getReference($commentData['contact']));
                    }
                    $manager->persist($comment);
                    $this->setReference($commentReference, $comment);
                }
            }
        }
        $manager->flush();
    }
}
