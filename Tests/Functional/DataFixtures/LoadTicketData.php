<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;

class LoadTicketData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
        'oro_zendesk:ticket_43' => [
            'originId' => 43,
            'url' => 'https://foo.zendesk.com/api/v2/tickets/43.json',
            'subject' => 'Zendesk Ticket 43',
            'description' => 'Zendesk Ticket 43 Description',
            'externalId' => '456546544564564564',
            'type' => TicketType::TYPE_PROBLEM,
            'status' => TicketStatus::STATUS_OPEN,
            'priority' => TicketPriority::PRIORITY_NORMAL,
            'requester' => 'zendesk_user:alex.taylor@example.com',
            'submitter' => 'zendesk_user:fred.taylor@example.com',
            'assignee' => 'zendesk_user:fred.taylor@example.com',
            'createdAt' => '2014-06-05T12:24:23Z',
            'updatedAt' => '2014-06-05T13:43:21Z',
            'relatedCase' => 'oro_zendesk:case_2',
            'originUpdatedAt' => '2014-06-09T17:45:22Z',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'oro_zendesk:ticket_42' => [
            'originId' => 42,
            'url' => 'https://foo.zendesk.com/api/v2/tickets/42.json',
            'subject' => 'Zendesk Ticket 42',
            'description' => 'Zendesk Ticket 42 Description',
            'externalId' => '7e24caa0-87f7-44d6-922b-0330ed9fd06c',
            'problem' => 'oro_zendesk:ticket_43',
            'collaborators' => ['zendesk_user:fred.taylor@example.com', 'zendesk_user:alex.taylor@example.com'],
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_PENDING,
            'priority' => TicketPriority::PRIORITY_URGENT,
            'recipient' => 'test@mail.com',
            'requester' => 'zendesk_user:alex.taylor@example.com',
            'submitter' => 'zendesk_user:fred.taylor@example.com',
            'assignee' => 'zendesk_user:fred.taylor@example.com',
            'hasIncidents' => true,
            'createdAt' => '2014-06-10T15:54:22Z',
            'updatedAt' => '2014-06-10T17:45:31Z',
            'dueAt' => '2014-06-11T12:13:11Z',
            'relatedCase' => 'oro_zendesk:case_1',
            'comments' => [
                'zendesk_ticket_42_comment_1' => [
                    'originId' => 1000,
                    'body' => 'Comment 1',
                    'htmlBody' => '<p>Comment 1</p>',
                    'public' => true,
                    'author' => 'zendesk_user:james.cook@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'relatedComment' => 'case_1_comment_1',
                    'channel' => 'zendesk_channel:first_test_channel'
                ],
                'zendesk_ticket_42_comment_2' => [
                    'originId' => 1001,
                    'body' => 'Comment 2',
                    'htmlBody' => '<p>Comment 2</p>',
                    'public' => false,
                    'author' => 'zendesk_user:jim.smith@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'relatedComment' => 'case_1_comment_2',
                    'channel' => 'zendesk_channel:first_test_channel'
                ],
                'zendesk_ticket_42_comment_3' => [
                    'body' => 'Comment 3',
                    'htmlBody' => '<p>Comment 3</p>',
                    'public' => false,
                    'author' => 'zendesk_user:jim.smith@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'relatedComment' => 'case_1_comment_3',
                    'channel' => 'zendesk_channel:first_test_channel'
                ],
                'zendesk_ticket_42_comment_4' => [
                    'body' => 'Comment 4',
                    'htmlBody' => '<p>Comment 4</p>',
                    'public' => false,
                    'author' => 'zendesk_user:alex.miller@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'relatedComment' => 'case_1_comment_4',
                    'channel' => 'zendesk_channel:first_test_channel'
                ]
            ],
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'oro_zendesk:ticket_44_case_6' => [
            'originId' => 44,
            'url' => 'https://foo.zendesk.com/api/v2/tickets/44.json',
            'subject' => 'Case 6, Zendesk Ticket 44',
            'description' => 'Case 6, Zendesk Ticket 44 Description',
            'externalId' => '7e24ca44-87f7-44d6-922b-0330ed9fd06c',
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_OPEN,
            'priority' => TicketPriority::PRIORITY_LOW,
            'recipient' => 'recipient@example.com',
            'requester' => 'zendesk_user:jim.smith@example.com',
            'submitter' => 'zendesk_user:james.cook@example.com',
            'assignee' => 'zendesk_user:james.cook@example.com',
            'createdAt' => '2014-06-10T15:54:22Z',
            'updatedAt' => '2014-06-10T17:45:31Z',
            'dueAt' => '2014-06-11T12:13:11Z',
            'relatedCase' => 'oro_zendesk:case_6',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'oro_zendesk:not_synced_ticket' => [
            'subject' => 'Not Synced Zendesk Ticket',
            'description' => 'Not Synced Zendesk Ticket Description',
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_OPEN,
            'priority' => TicketPriority::PRIORITY_NORMAL,
            'requester' => 'zendesk_user:alex.taylor@example.com',
            'submitter' => 'zendesk_user:fred.taylor@example.com',
            'assignee' => 'zendesk_user:fred.taylor@example.com',
            'createdAt' => '2014-06-05T12:24:23Z',
            'updatedAt' => '2014-06-05T13:43:21Z',
            'relatedCase' => 'oro_zendesk:case_4',
            'originUpdatedAt' => '2014-06-09T17:45:22Z',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'oro_zendesk:not_synced_ticket_with_case_comments' => [
            'subject' => 'Not Synced Zendesk Ticket with Case Comments',
            'description' => 'Not Synced Zendesk Ticket with Case Comments Description',
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_OPEN,
            'priority' => TicketPriority::PRIORITY_LOW,
            'requester' => 'zendesk_user:alex.taylor@example.com',
            'submitter' => 'zendesk_user:fred.taylor@example.com',
            'assignee' => 'zendesk_user:fred.taylor@example.com',
            'createdAt' => '2014-06-05T12:24:23Z',
            'updatedAt' => '2014-06-05T13:43:21Z',
            'relatedCase' => 'oro_zendesk:case_5',
            'originUpdatedAt' => '2014-06-09T17:45:22Z',
            'channel' => 'zendesk_channel:first_test_channel'
        ],
        'oro_zendesk:synced_ticket_with_not_synced_comments' => [
            'subject' => 'Synced Zendesk Ticket with not synced Comments',
            'description' => 'Synced Zendesk Ticket with not synced Comments Description',
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_OPEN,
            'priority' => TicketPriority::PRIORITY_LOW,
            'originId' => 2000,
            'requester' => 'zendesk_user:alex.taylor@example.com',
            'submitter' => 'zendesk_user:fred.taylor@example.com',
            'assignee' => 'zendesk_user:fred.taylor@example.com',
            'createdAt' => '2014-06-05T12:24:23Z',
            'updatedAt' => '2014-06-05T13:43:21Z',
            'relatedCase' => 'oro_zendesk:case_7',
            'originUpdatedAt' => '2014-06-09T17:45:22Z',
            'channel' => 'zendesk_channel:second_test_channel',
            'comments' => [
                'zendesk_ticket_52_comment_1' => [
                    'originId' => 2000,
                    'body' => 'Comment 1',
                    'htmlBody' => '<p>Comment 1</p>',
                    'public' => true,
                    'author' => 'zendesk_user:james.cook@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'channel' => 'zendesk_channel:second_test_channel'
                ],
                'zendesk_ticket_52_comment_2' => [
                    'originId' => 2001,
                    'body' => 'Comment 2',
                    'htmlBody' => '<p>Comment 2</p>',
                    'public' => false,
                    'author' => 'zendesk_user:jim.smith@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'channel' => 'zendesk_channel:second_test_channel'
                ],
                'zendesk_ticket_52_comment_3' => [
                    'body' => 'Comment 3',
                    'htmlBody' => '<p>Comment 3</p>',
                    'public' => false,
                    'author' => 'zendesk_user:jim.smith@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'channel' => 'zendesk_channel:second_test_channel'
                ],
                'zendesk_ticket_52_comment_4' => [
                    'body' => 'Comment 4',
                    'htmlBody' => '<p>Comment 4</p>',
                    'public' => false,
                    'author' => 'zendesk_user:alex.miller@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'channel' => 'zendesk_channel:second_test_channel'
                ]
            ]
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCaseEntityData::class,
            LoadZendeskUserData::class,
            LoadChannelData::class
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $reference => $data) {
            $entity = new Ticket();
            $entity->setSubject($data['subject']);
            $entity->setDescription($data['description']);
            $entity->setType($manager->find(TicketType::class, $data['type']));
            $entity->setStatus($manager->find(TicketStatus::class, $data['status']));
            $entity->setPriority($manager->find(TicketPriority::class, $data['priority']));
            $entity->setRequester($this->getReference($data['requester']));
            $entity->setSubmitter($this->getReference($data['submitter']));
            $entity->setAssignee($this->getReference($data['assignee']));
            $entity->setCreatedAt(new \DateTime($data['createdAt']));
            $entity->setUpdatedAt(new \DateTime($data['updatedAt']));
            $entity->setRelatedCase($this->getReference($data['relatedCase']));
            $entity->setChannel($this->getReference($data['channel']));
            if (isset($data['collaborators'])) {
                foreach ($data['collaborators'] as $user) {
                    $entity->addCollaborator($this->getReference($user));
                }
            }
            if (isset($data['originId'])) {
                $entity->setOriginId($data['originId']);
            }
            if (isset($data['url'])) {
                $entity->setUrl($data['url']);
            }
            if (isset($data['externalId'])) {
                $entity->setExternalId($data['externalId']);
            }
            if (isset($data['problem'])) {
                $entity->setProblem($this->getReference($data['problem']));
            }
            if (isset($data['recipient'])) {
                $entity->setRecipient($data['recipient']);
            }
            if (isset($data['hasIncidents'])) {
                $entity->setHasIncidents($data['hasIncidents']);
            }
            if (isset($data['dueAt'])) {
                $entity->setDueAt(new \DateTime($data['dueAt']));
            }
            if (isset($data['originUpdatedAt'])) {
                $entity->setOriginUpdatedAt(new \DateTime($data['originUpdatedAt']));
            }
            $manager->persist($entity);
            $this->setReference($reference, $entity);

            if (isset($data['comments'])) {
                foreach ($data['comments'] as $commentReference => $commentData) {
                    $comment = new TicketComment();
                    $entity->addComment($comment);
                    $comment->setBody($commentData['body']);
                    $comment->setHtmlBody($commentData['htmlBody']);
                    $comment->setPublic($commentData['public']);
                    $comment->setAuthor($this->getReference($commentData['author']));
                    $comment->setChannel($this->getReference($commentData['channel']));
                    $comment->setCreatedAt(new \DateTime($commentData['createdAt']));
                    if (isset($commentData['relatedComment'])) {
                        $comment->setRelatedComment($this->getReference($commentData['relatedComment']));
                    }
                    if (isset($commentData['originId'])) {
                        $comment->setOriginId($commentData['originId']);
                    }
                    $manager->persist($comment);
                    $this->setReference($commentReference, $comment);
                }
            }
        }
        $manager->flush();
    }
}
