<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Writer;

use Symfony\Component\Serializer\SerializerInterface;

use Doctrine\ORM\EntityManager;

use OroCRM\Bundle\CaseBundle\Entity\CasePriority;
use OroCRM\Bundle\CaseBundle\Entity\CaseStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Writer\TicketExportWriter;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketCommentConnector;

class TicketExportWriterTest extends WebTestCase
{
    /**
     * @var TicketExportWriter
     */
    protected $writer;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $logOutput;

    protected function setUp()
    {
        $this->initClient();
        $this->client->startTransaction();
        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData'], true);

        $this->channel = $this->getReference('zendesk_channel:first_test_channel');

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->serializer = $this->getContainer()->get('oro_importexport.serializer');
        $this->context = $this->getMock('Oro\\Bundle\\ImportExportBundle\\Context\\ContextInterface');

        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([['channel', null, $this->channel->getId()]]));

        $this->transport =
            $this->getMock('OroCRM\\Bundle\\ZendeskBundle\\Provider\\Transport\\ZendeskTransportInterface');

        $this->logger = $this->getMock('Psr\\Log\\LoggerInterface');

        $this->logger->expects($this->any(0))
            ->method('log')
            ->will(
                $this->returnCallback(
                    function ($level, $message) {
                        $this->logOutput .= '[' . $level . '] ' . $message . PHP_EOL;
                    }
                )
            );

        $this->getContainer()->set('orocrm_zendesk.transport.rest_transport', $this->transport);

        $this->writer = $this->getContainer()->get('orocrm_zendesk.importexport.writer.ticket_export');
        $this->writer->setImportExportContext($this->context);
        $this->writer->setLogger($this->logger);
    }

    protected function tearDown()
    {
        $this->getContainer()->set('orocrm_zendesk.transport.rest_transport', null);
        $this->getContainer()->set('orocrm_zendesk.importexport.writer.ticket_export', null);
        $this->logOutput = null;
        $this->client->rollbackTransaction();
    }

    public function testWriteCreatesTicket()
    {
        $ticket = $this->getReference('orocrm_zendesk:not_synced_ticket');

        $requestData = $this->serializer->serialize($ticket, null);
        $expectedResponseData = [
            'ticket' => [
                'id' => $ticketId = 10001,
                'url' => 'https://foo.zendesk.com/api/v2/tickets/10001.json',
                'subject' => 'Updated Subject',
                'description' => $description = 'Updated Description',
                'type' => TicketType::TYPE_TASK,
                'status' => TicketStatus::STATUS_OPEN,
                'priority' => TicketPriority::PRIORITY_NORMAL,
                'requester_id' => $requesterId =
                        $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'submitter_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'assignee_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'created_at' => '2014-06-06T12:24:23+0000',
                'updated_at' => '2014-06-09T13:43:21+0000',
            ],
            'comment' => null,
        ];

        $this->transport->expects($this->once())
            ->method('createTicket')
            ->with($requestData)
            ->will($this->returnValue($expectedResponseData));

        $expected = $expectedResponseData['ticket'];

        $this->writer->write([$ticket]);

        $ticket = $this->entityManager->find(get_class($ticket), $ticket->getId());

        $this->assertEquals($expected['id'], $ticket->getOriginId());
        $this->assertEquals($expected['url'], $ticket->getUrl());
        $this->assertEquals($expected['subject'], $ticket->getSubject());
        $this->assertEquals($expected['description'], $ticket->getDescription());
        $this->assertEquals($expected['type'], $ticket->getType()->getName());
        $this->assertEquals($expected['status'], $ticket->getStatus()->getName());
        $this->assertEquals($expected['priority'], $ticket->getPriority()->getName());
        $this->assertEquals($expected['requester_id'], $ticket->getRequester()->getOriginId());
        $this->assertEquals($expected['submitter_id'], $ticket->getSubmitter()->getOriginId());
        $this->assertEquals($expected['assignee_id'], $ticket->getAssignee()->getOriginId());
        $this->assertEquals($expected['created_at'], $ticket->getOriginCreatedAt()->format(\DateTime::ISO8601));
        $this->assertEquals($expected['updated_at'], $ticket->getOriginUpdatedAt()->format(\DateTime::ISO8601));

        $relatedCase = $ticket->getRelatedCase();
        $this->assertEquals($expected['subject'], $relatedCase->getSubject());
        $this->assertEquals($expected['description'], $relatedCase->getDescription());
        $this->assertEquals(CaseStatus::STATUS_OPEN, $relatedCase->getStatus()->getName());
        $this->assertEquals(CasePriority::PRIORITY_NORMAL, $relatedCase->getPriority()->getName());
        $this->assertEquals('james.cook@example.com', $relatedCase->getAssignedTo()->getEmail());
        $this->assertEquals('james.cook@example.com', $relatedCase->getOwner()->getEmail());

        $this->assertContains('[info] Zendesk Ticket [id=' . $ticket->getId() . ']:', $this->logOutput);
        $this->assertContains('Create ticket in Zendesk API.', $this->logOutput);
        $this->assertContains('Created ticket [origin_id=' . $expected['id'] . '].', $this->logOutput);
        $this->assertContains('Update ticket by response data.', $this->logOutput);
        $this->assertContains('Update related case.', $this->logOutput);
    }

    public function testWriteCreatesComment()
    {
        $ticket = $this->getReference('orocrm_zendesk:not_synced_ticket');

        $requestData = $this->serializer->serialize($ticket, null);
        $expectedResponseData = [
            'ticket' => [
                'id' => $ticketId = 10001,
                'url' => 'https://foo.zendesk.com/api/v2/tickets/10001.json',
                'subject' => 'Updated Subject',
                'description' => $description = 'Updated Description',
                'type' => TicketType::TYPE_TASK,
                'status' => TicketStatus::STATUS_OPEN,
                'priority' => TicketPriority::PRIORITY_NORMAL,
                'requester_id' => $requesterId =
                    $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'submitter_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'assignee_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'created_at' => '2014-06-06T12:24:23+0000',
                'updated_at' => '2014-06-09T13:43:21+0000',
            ],
            'comment' => [
                'id' => 20001,
                'author_id' => $requesterId,
                'body' => $description,
                'html_body' => '<p>' . $description . '</p>',
                'public' => true,
                'ticket_id' => $ticketId,
                'created_at' => '2014-06-06T12:24:24+0000',
            ],
        ];

        $this->transport->expects($this->once())
            ->method('createTicket')
            ->with($requestData)
            ->will($this->returnValue($expectedResponseData));

        $expected = $expectedResponseData['comment'];

        $this->writer->write([$ticket]);

        $ticket = $this->entityManager->find(get_class($ticket), $ticket->getId());

        $this->assertEquals(1, $ticket->getComments()->count());
        $comment = $ticket->getComments()->first();

        $this->assertEquals($expected['id'], $comment->getOriginId());
        $this->assertEquals($expected['body'], $comment->getBody());
        $this->assertEquals($expected['html_body'], $comment->getHtmlBody());
        $this->assertEquals($expected['public'], $comment->getPublic());
        $this->assertEquals($expected['author_id'], $comment->getAuthor()->getOriginId());
        $this->assertEquals($expected['ticket_id'], $comment->getTicket()->getOriginId());
        $this->assertEquals($expected['created_at'], $comment->getOriginCreatedAt()->format(\DateTime::ISO8601));

        $relatedComment = $comment->getRelatedComment();
        $this->assertNotNull($relatedComment);
        $this->assertEquals($expected['body'], $relatedComment->getMessage());
        $this->assertEquals($expected['public'], $relatedComment->isPublic());
        $this->assertNotEmpty($relatedComment->getOwner());
        $this->assertEquals(
            'james.cook@example.com',
            $relatedComment->getOwner()->getEmail()
        );

        $this->assertContains('Created ticket comment [origin_id=' . $expected['id'] . '].', $this->logOutput);
        $this->assertContains('Update related case comment.', $this->logOutput);
        $this->assertNotContains('Schedule job to sync existing ticket comments.', $this->logOutput);
    }

    public function testWriteCreatesCommentWithExistingContact()
    {
        $ticket = $this->getReference('orocrm_zendesk:not_synced_ticket');

        $requestData = $this->serializer->serialize($ticket, null);
        $expectedResponseData = [
            'ticket' => [
                'id' => $ticketId = 10001,
                'url' => 'https://foo.zendesk.com/api/v2/tickets/10001.json',
                'subject' => 'Updated Subject',
                'description' => $description = 'Updated Description',
                'type' => TicketType::TYPE_TASK,
                'status' => TicketStatus::STATUS_OPEN,
                'priority' => TicketPriority::PRIORITY_NORMAL,
                'requester_id' => $requesterId =
                    $this->getReference('zendesk_user:jim.smith@example.com')->getOriginId(),
                'submitter_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'assignee_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'created_at' => '2014-06-06T12:24:23+0000',
                'updated_at' => '2014-06-09T13:43:21+0000',
            ],
            'comment' => [
                'id' => 20001,
                'author_id' => $requesterId,
                'body' => $description,
                'html_body' => '<p>' . $description . '</p>',
                'public' => true,
                'ticket_id' => $ticketId,
                'created_at' => '2014-06-06T12:24:24+0000',
            ],
        ];

        $this->transport->expects($this->once())
            ->method('createTicket')
            ->with($requestData)
            ->will($this->returnValue($expectedResponseData));

        $this->writer->write([$ticket]);

        $ticket = $this->entityManager->find(get_class($ticket), $ticket->getId());
        $comment = $ticket->getComments()->first();

        $relatedComment = $comment->getRelatedComment();
        $this->assertNotEmpty($relatedComment->getContact());
        $this->assertEquals(
            'jim.smith@example.com',
            $relatedComment->getContact()->getPrimaryEmail()
        );
    }

    public function testWriteSchedulesTicketCommentSync()
    {
        $ticket = $this->getReference('orocrm_zendesk:not_synced_ticket_with_case_comments');

        $requestData = $this->serializer->serialize($ticket, null);
        $expectedResponseData = [
            'ticket' => [
                'id' => $ticketId = 10001,
                'url' => 'https://foo.zendesk.com/api/v2/tickets/10001.json',
                'subject' => 'Updated Subject',
                'description' => $description = 'Updated Description',
                'type' => TicketType::TYPE_TASK,
                'status' => TicketStatus::STATUS_OPEN,
                'priority' => TicketPriority::PRIORITY_NORMAL,
                'requester_id' => $requesterId =
                    $this->getReference('zendesk_user:jim.smith@example.com')->getOriginId(),
                'submitter_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'assignee_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
                'created_at' => '2014-06-06T12:24:23+0000',
                'updated_at' => '2014-06-09T13:43:21+0000',
            ],
            'comment' => [
                'id' => 20001,
                'author_id' => $requesterId,
                'body' => $description,
                'html_body' => '<p>' . $description . '</p>',
                'public' => true,
                'ticket_id' => $ticketId,
                'created_at' => '2014-06-06T12:24:24+0000',
            ],
        ];

        $this->transport->expects($this->once())
            ->method('createTicket')
            ->with($requestData)
            ->will($this->returnValue($expectedResponseData));

        $this->writer->write([$ticket]);

        $ticket = $this->entityManager->find(get_class($ticket), $ticket->getId());
        $this->assertEquals(3, $ticket->getComments()->count());

        $commentIds = [];
        foreach ($ticket->getComments() as $comment) {
            $commentIds[] = $comment->getId();
            $this->assertNotNull($comment->getRelatedComment(), 'Ticket comment has related case comment.');
        }

        $this->assertContains('Create ticket comment for case comment', $this->logOutput);

        $this->assertContains(
            sprintf('Schedule job to sync existing ticket comments [ids=%s].', implode(', ', $commentIds)),
            $this->logOutput
        );

        $job = $this->entityManager->getRepository('JMSJobQueueBundle:Job')
            ->findOneBy(['command' => SyncScheduler::JOB_NAME], ['createdAt' => 'DESC']);

        $this->assertNotEmpty($job, 'Has scheduled JMS job.');
        $this->assertEquals(
            [
                '--integration=' . $this->channel->getId(),
                '--connector=' . TicketCommentConnector::TYPE,
                '--params=' . serialize(['id' => $commentIds]),
            ],
            $job->getArgs()
        );
    }

    public function testWriteUpdatesTicket()
    {
        $ticket = $this->getReference('orocrm_zendesk:ticket_43');

        $requestData = $this->serializer->serialize($ticket, null);
        $expectedResponseData = [
            'id' => $ticket->getOriginId(),
            'url' => 'https://foo.zendesk.com/api/v2/tickets/43.json',
            'subject' => 'Updated Subject',
            'description' => $description = 'Updated Description',
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_CLOSED,
            'priority' => TicketPriority::PRIORITY_LOW,
            'requester_id' => $requesterId =
                $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
            'submitter_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
            'assignee_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
            'created_at' => '2014-06-06T12:24:23+0000',
            'updated_at' => '2014-06-09T13:43:21+0000',
        ];

        $this->transport->expects($this->once())
            ->method('updateTicket')
            ->with($requestData)
            ->will($this->returnValue($expectedResponseData));

        $expected = $expectedResponseData;

        $this->writer->write([$ticket]);

        $ticket = $this->entityManager->find(get_class($ticket), $ticket->getId());

        $this->assertEquals($expected['id'], $ticket->getOriginId());
        $this->assertEquals($expected['url'], $ticket->getUrl());
        $this->assertEquals($expected['subject'], $ticket->getSubject());
        $this->assertEquals($expected['description'], $ticket->getDescription());
        $this->assertEquals($expected['type'], $ticket->getType()->getName());
        $this->assertEquals($expected['status'], $ticket->getStatus()->getName());
        $this->assertEquals($expected['priority'], $ticket->getPriority()->getName());
        $this->assertEquals($expected['requester_id'], $ticket->getRequester()->getOriginId());
        $this->assertEquals($expected['submitter_id'], $ticket->getSubmitter()->getOriginId());
        $this->assertEquals($expected['assignee_id'], $ticket->getAssignee()->getOriginId());
        $this->assertEquals($expected['created_at'], $ticket->getOriginCreatedAt()->format(\DateTime::ISO8601));
        $this->assertEquals($expected['updated_at'], $ticket->getOriginUpdatedAt()->format(\DateTime::ISO8601));

        $relatedCase = $ticket->getRelatedCase();
        $this->assertEquals($expected['subject'], $relatedCase->getSubject());
        $this->assertEquals($expected['description'], $relatedCase->getDescription());
        $this->assertEquals(CaseStatus::STATUS_CLOSED, $relatedCase->getStatus()->getName());
        $this->assertEquals(CasePriority::PRIORITY_LOW, $relatedCase->getPriority()->getName());
        $this->assertEquals('james.cook@example.com', $relatedCase->getAssignedTo()->getEmail());
        $this->assertEquals('james.cook@example.com', $relatedCase->getOwner()->getEmail());

        $this->assertContains('[info] Zendesk Ticket [id=' . $ticket->getId() . ']:', $this->logOutput);
        $this->assertContains(
            'Update ticket in Zendesk API [origin_id=' . $ticket->getOriginId() . '].',
            $this->logOutput
        );
        $this->assertContains('Update ticket by response data.', $this->logOutput);
        $this->assertContains('Update related case.', $this->logOutput);
    }

    public function testWriteCreatesUsers()
    {
        $requesterUser = $this->getReference('zendesk_user:sam.rogers@example.com');
        $submitterUser = $this->getReference('zendesk_user:garry.smith@example.com');
        $assigneeUser = $submitterUser;

        $ticket = $this->getReference('orocrm_zendesk:ticket_43');
        $ticket->setRequester($requesterUser);
        $ticket->setSubmitter($submitterUser);
        $ticket->setAssignee($assigneeUser);
        $this->entityManager->flush($ticket);

        $requesterUserRequestData = $this->serializer->serialize($requesterUser, null);
        $expectedRequesterUserResponseData = [
            'id' => $requesterId = 10001,
            'url' => $url = 'https://foo.zendesk.com/api/v2/users/10001.json',
            'name' => $requesterUser->getName(),
            'email' => $requesterUser->getEmail(),
            'role' => $requesterUser->getRole()->getName(),
        ];
        $this->transport->expects($this->at(0))
            ->method('createUser')
            ->with($requesterUserRequestData)
            ->will($this->returnValue($expectedRequesterUserResponseData));

        $submitterUserRequestData = $this->serializer->serialize($submitterUser, null);
        $expectedSubmitterUserResponseData = [
            'id' => $submitterId = 10002,
            'url' => $url = 'https://foo.zendesk.com/api/v2/users/10002.json',
            'name' => $submitterUser->getName(),
            'email' => $submitterUser->getEmail(),
            'role' => $submitterUser->getRole()->getName(),
        ];
        $this->transport->expects($this->at(1))
            ->method('createUser')
            ->with($submitterUserRequestData)
            ->will($this->returnValue($expectedSubmitterUserResponseData));

        $ticketRequestData = $this->serializer->serialize($ticket, null);
        $ticketRequestData['requester_id'] = $requesterId;
        $ticketRequestData['submitter_id'] = $submitterId;
        $ticketRequestData['assignee_id'] = $submitterId;
        $expectedTicketResponseData = [
            'id' => $ticket->getOriginId(),
            'url' => 'https://foo.zendesk.com/api/v2/tickets/43.json',
            'subject' => 'Updated Subject',
            'description' => $description = 'Updated Description',
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_CLOSED,
            'priority' => TicketPriority::PRIORITY_LOW,
            'requester_id' => $requesterId,
            'submitter_id' => $submitterId,
            'assignee_id' => $submitterId,
            'created_at' => '2014-06-06T12:24:23+0000',
            'updated_at' => '2014-06-09T13:43:21+0000',
        ];

        $this->transport->expects($this->at(2))
            ->method('updateTicket')
            ->with($ticketRequestData)
            ->will($this->returnValue($expectedTicketResponseData));

        $this->writer->write([$ticket]);

        $ticket = $this->entityManager->find(get_class($ticket), $ticket->getId());
        $this->assertNotEmpty($ticket->getRequester());
        $this->assertEquals($requesterId, $ticket->getRequester()->getOriginId());

        $this->assertNotEmpty($ticket->getSubmitter());
        $this->assertEquals($submitterId, $ticket->getSubmitter()->getOriginId());

        $this->assertNotEmpty($ticket->getAssignee());
        $this->assertEquals($submitterId, $ticket->getAssignee()->getOriginId());

        $this->assertContains('Create user in Zendesk API [id=' . $requesterUser->getId() . '].', $this->logOutput);
        $this->assertContains('Created user [origin_id=' . $requesterId . '].', $this->logOutput);
        $this->assertContains('Create user in Zendesk API [id=' . $submitterUser->getId() . '].', $this->logOutput);
        $this->assertContains('Created user [origin_id=' . $submitterId . '].', $this->logOutput);
    }
}
