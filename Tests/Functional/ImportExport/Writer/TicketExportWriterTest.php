<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Writer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

use OroCRM\Bundle\CaseBundle\Entity\CasePriority;
use OroCRM\Bundle\CaseBundle\Entity\CaseStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Writer\TicketExportWriter;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketCommentConnector;

/**
 * @dbIsolationPerTest
 */
class TicketExportWriterTest extends WebTestCase
{
    /**
     * @var TicketExportWriter
     */
    protected $writer;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData'], true);

        $this->channel = $this->getReference('zendesk_channel:first_test_channel');

        $this->registry = $this->getContainer()->get('doctrine');
        $this->context  = $this->getMock('Oro\\Bundle\\ImportExportBundle\\Context\\ContextInterface');

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

        $this->writer = $this->getContainer()->get('orocrm_zendesk.importexport.writer.export_ticket');
        $this->writer->setImportExportContext($this->context);
        $this->writer->setLogger($this->logger);
    }

    protected function tearDown()
    {
        $this->getContainer()->set('orocrm_zendesk.transport.rest_transport', null);
        $this->getContainer()->set('orocrm_zendesk.importexport.writer.export_ticket', null);
        $this->logOutput = null;

        parent::tearDown();
    }

    public function testWriteCreatesTicket()
    {
        $ticket = $this->getReference('orocrm_zendesk:not_synced_ticket');

        $expected = $this->createTicket()
            ->setOriginId(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/10001.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType($this->createTicketType(TicketType::TYPE_TASK))
            ->setStatus($this->createTicketStatus(TicketStatus::STATUS_OPEN))
            ->setPriority($this->createTicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setRequester($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setSubmitter($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setAssignee($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $this->transport->expects($this->once())
            ->method('createTicket')
            ->with($ticket)
            ->will($this->returnValue(['ticket' => $expected, 'comment' => null]));

          $this->writer->write([$ticket]);

        $ticket = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());

        $this->assertEquals($expected->getOriginId(), $ticket->getOriginId());
        $this->assertEquals($expected->getUrl(), $ticket->getUrl());
        $this->assertEquals($expected->getSubject(), $ticket->getSubject());
        $this->assertEquals($expected->getDescription(), $ticket->getDescription());
        $this->assertEquals($expected->getType()->getName(), $ticket->getType()->getName());
        $this->assertEquals($expected->getStatus()->getName(), $ticket->getStatus()->getName());
        $this->assertEquals($expected->getPriority()->getName(), $ticket->getPriority()->getName());
        $this->assertEquals($expected->getRequester()->getOriginId(), $ticket->getRequester()->getOriginId());
        $this->assertEquals($expected->getSubmitter()->getOriginId(), $ticket->getSubmitter()->getOriginId());
        $this->assertEquals($expected->getAssignee()->getOriginId(), $ticket->getAssignee()->getOriginId());
        $this->assertEquals($expected->getOriginCreatedAt(), $ticket->getOriginCreatedAt());
        $this->assertEquals($expected->getOriginUpdatedAt(), $ticket->getOriginUpdatedAt());

        $relatedCase = $ticket->getRelatedCase();
        $this->assertEquals($expected->getSubject(), $relatedCase->getSubject());
        $this->assertEquals($expected->getDescription(), $relatedCase->getDescription());
        $this->assertEquals(CaseStatus::STATUS_OPEN, $relatedCase->getStatus()->getName());
        $this->assertEquals(CasePriority::PRIORITY_NORMAL, $relatedCase->getPriority()->getName());
        $this->assertEquals('james.cook@example.com', $relatedCase->getAssignedTo()->getEmail());
        $this->assertEquals('james.cook@example.com', $relatedCase->getOwner()->getEmail());

        $this->assertContains('[info] Zendesk Ticket [id=' . $ticket->getId() . ']:', $this->logOutput);
        $this->assertContains('Create ticket in Zendesk API.', $this->logOutput);
        $this->assertContains('Created ticket [origin_id=' . $expected->getOriginId() . '].', $this->logOutput);
        $this->assertContains('Update ticket by response data.', $this->logOutput);
        $this->assertContains('Update related case.', $this->logOutput);
    }

    public function testWriteCreatesComment()
    {
        $ticket = $this->getReference('orocrm_zendesk:not_synced_ticket');

        $expectedTicket = $this->createTicket()
            ->setOriginId(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/10001.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType($this->createTicketType(TicketType::TYPE_TASK))
            ->setStatus($this->createTicketStatus(TicketStatus::STATUS_OPEN))
            ->setPriority($this->createTicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setRequester($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setSubmitter($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setAssignee($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $expectedComment = $this->createTicketComment()
            ->setOriginId(20001)
            ->setAuthor($expectedTicket->getRequester())
            ->setBody($expectedTicket->getDescription())
            ->setHtmlBody('<p>' . $expectedTicket->getDescription() . '</p>')
            ->setPublic(true)
            ->setTicket($this->createTicket()->setOriginId($expectedTicket->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('createTicket')
            ->with($ticket)
            ->will($this->returnValue(['ticket' => $expectedTicket, 'comment' => $expectedComment]));

        $this->writer->write([$ticket]);

        $ticket = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());

        $this->assertEquals(1, $ticket->getComments()->count());
        $comment = $ticket->getComments()->first();

        $this->assertEquals($expectedComment->getOriginId(), $comment->getOriginId());
        $this->assertEquals($expectedComment->getBody(), $comment->getBody());
        $this->assertEquals($expectedComment->getHtmlBody(), $comment->getHtmlBody());
        $this->assertEquals($expectedComment->getPublic(), $comment->getPublic());
        $this->assertEquals($expectedComment->getAuthor()->getOriginId(), $comment->getAuthor()->getOriginId());
        $this->assertEquals($expectedComment->getTicket()->getOriginId(), $comment->getTicket()->getOriginId());
        $this->assertEquals($expectedComment->getOriginCreatedAt(), $comment->getOriginCreatedAt());

        $relatedComment = $comment->getRelatedComment();
        $this->assertNotNull($relatedComment);
        $this->assertEquals($expectedComment->getBody(), $relatedComment->getMessage());
        $this->assertEquals($expectedComment->getPublic(), $relatedComment->isPublic());
        $this->assertNotEmpty($relatedComment->getOwner());
        $this->assertEquals(
            'james.cook@example.com',
            $relatedComment->getOwner()->getEmail()
        );

        $this->assertContains(
            'Created ticket comment [origin_id=' . $expectedComment->getOriginId() . '].',
            $this->logOutput
        );
        $this->assertContains('Update related case comment.', $this->logOutput);
        $this->assertNotContains('Schedule job to sync existing ticket comments.', $this->logOutput);
    }

    public function testWriteCreatesCommentWithExistingContact()
    {
        $ticket = $this->getReference('orocrm_zendesk:not_synced_ticket');

        $expectedTicket = $this->createTicket()
            ->setOriginId(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/10001.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType($this->createTicketType(TicketType::TYPE_TASK))
            ->setStatus($this->createTicketStatus(TicketStatus::STATUS_OPEN))
            ->setPriority($this->createTicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setRequester($this->createUser($this->getReference('zendesk_user:jim.smith@example.com')->getOriginId()))
            ->setSubmitter($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setAssignee($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $expectedComment = $this->createTicketComment()
            ->setOriginId(20001)
            ->setAuthor($expectedTicket->getRequester())
            ->setBody($expectedTicket->getDescription())
            ->setHtmlBody('<p>' . $expectedTicket->getDescription() . '</p>')
            ->setPublic(true)
            ->setTicket($this->createTicket()->setOriginId($expectedTicket->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('createTicket')
            ->with($ticket)
            ->will($this->returnValue(['ticket' => $expectedTicket, 'comment' => $expectedComment]));

        $this->writer->write([$ticket]);

        $ticket  = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());
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

        $expectedTicket = $this->createTicket()
            ->setOriginId(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/10001.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType($this->createTicketType(TicketType::TYPE_TASK))
            ->setStatus($this->createTicketStatus(TicketStatus::STATUS_OPEN))
            ->setPriority($this->createTicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setRequester($this->createUser($this->getReference('zendesk_user:jim.smith@example.com')->getOriginId()))
            ->setSubmitter($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setAssignee($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $expectedComment = $this->createTicketComment()
            ->setOriginId(20001)
            ->setAuthor($expectedTicket->getRequester())
            ->setBody($expectedTicket->getDescription())
            ->setHtmlBody('<p>' . $expectedTicket->getDescription() . '</p>')
            ->setPublic(true)
            ->setTicket($this->createTicket()->setOriginId($expectedTicket->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('createTicket')
            ->with($ticket)
            ->will($this->returnValue(['ticket' => $expectedTicket, 'comment' => $expectedComment]));

        $this->writer->write([$ticket]);

        $ticket = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());
        $this->assertEquals(3, $ticket->getComments()->count());

        $commentIds = [];
        foreach ($ticket->getComments() as $comment) {
            if ($comment->getOriginId()) {
                continue;
            }

            $commentIds[] = $comment->getId();
            $this->assertNotNull($comment->getRelatedComment(), 'Ticket comment has related case comment.');
        }
        sort($commentIds);

        $this->assertContains('Create ticket comment for case comment', $this->logOutput);

        $this->assertContains('Schedule job to sync existing ticket comments', $this->logOutput);
        $this->assertTicketCommentIds($this->logOutput, $commentIds);

        $job = $this->registry->getRepository('JMSJobQueueBundle:Job')
            ->findOneBy(['command' => SyncScheduler::JOB_NAME], ['createdAt' => 'DESC']);

        $this->assertNotEmpty($job, 'Has scheduled JMS job.');
        $arguments = $job->getArgs();
        $this->assertCount(3, $arguments);
        $this->assertContains('--integration=' . $this->channel->getId(), $arguments);
        $this->assertContains('--connector=' . TicketCommentConnector::TYPE, $arguments);
        $this->assertTicketJobParameters($arguments, $commentIds);
    }

    public function testWriteUpdatesTicket()
    {
        $ticket = $this->getReference('orocrm_zendesk:ticket_43');

        $expected = $this->createTicket()
            ->setOriginId($ticket->getOriginId())
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/43.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType($this->createTicketType(TicketType::TYPE_TASK))
            ->setStatus($this->createTicketStatus(TicketStatus::STATUS_CLOSED))
            ->setPriority($this->createTicketPriority(TicketPriority::PRIORITY_LOW))
            ->setRequester($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setSubmitter($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setAssignee($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $this->transport->expects($this->once())
            ->method('updateTicket')
            ->with($ticket)
            ->will($this->returnValue($expected));

        $this->writer->write([$ticket]);

        $ticket  = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());

        $this->assertEquals($expected->getOriginId(), $ticket->getOriginId());
        $this->assertEquals($expected->getUrl(), $ticket->getUrl());
        $this->assertEquals($expected->getSubject(), $ticket->getSubject());
        $this->assertEquals($expected->getDescription(), $ticket->getDescription());
        $this->assertEquals($expected->getType()->getName(), $ticket->getType()->getName());
        $this->assertEquals($expected->getStatus()->getName(), $ticket->getStatus()->getName());
        $this->assertEquals($expected->getPriority()->getName(), $ticket->getPriority()->getName());
        $this->assertEquals($expected->getRequester()->getOriginId(), $ticket->getRequester()->getOriginId());
        $this->assertEquals($expected->getSubmitter()->getOriginId(), $ticket->getSubmitter()->getOriginId());
        $this->assertEquals($expected->getAssignee()->getOriginId(), $ticket->getAssignee()->getOriginId());
        $this->assertEquals($expected->getOriginCreatedAt(), $ticket->getOriginCreatedAt());
        $this->assertEquals($expected->getOriginUpdatedAt(), $ticket->getOriginUpdatedAt());

        $relatedCase = $ticket->getRelatedCase();
        $this->assertEquals($expected->getSubject(), $relatedCase->getSubject());
        $this->assertEquals($expected->getDescription(), $relatedCase->getDescription());
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
        $requester = $this->getReference('zendesk_user:sam.rogers@example.com');
        $submitter = $this->getReference('zendesk_user:garry.smith@example.com');
        $assignee = $submitter;

        $ticket = $this->getReference('orocrm_zendesk:ticket_43');
        $ticket->setRequester($requester);
        $ticket->setSubmitter($submitter);
        $ticket->setAssignee($assignee);
        $this->registry->getManager()->flush($ticket);

        $expectedRequester = $this->createUser(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/users/10001.json')
            ->setName($requester->getName())
            ->setEmail($requester->getEmail())
            ->setRole($this->createUserRole($requester->getRole()->getName()));

        $this->transport->expects($this->at(1))
            ->method('createUser')
            ->with($requester)
            ->will($this->returnValue($expectedRequester));

        $expectedSubmitter = $this->createUser(10002)
            ->setUrl('https://foo.zendesk.com/api/v2/users/10002.json')
            ->setName($submitter->getName())
            ->setEmail($submitter->getEmail())
            ->setRole($this->createUserRole($submitter->getRole()->getName()));

        $this->transport->expects($this->at(2))
            ->method('createUser')
            ->with($submitter)
            ->will($this->returnValue($expectedSubmitter));

        $expectedTicket = $this->createTicket()
            ->setOriginId($ticket->getOriginId())
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/43.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType($this->createTicketType(TicketType::TYPE_TASK))
            ->setStatus($this->createTicketStatus(TicketStatus::STATUS_CLOSED))
            ->setPriority($this->createTicketPriority(TicketPriority::PRIORITY_LOW))
            ->setRequester($this->createUser($expectedRequester->getOriginId()))
            ->setSubmitter($this->createUser($expectedSubmitter->getOriginId()))
            ->setAssignee($this->createUser($expectedSubmitter->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $this->transport->expects($this->at(3))
            ->method('updateTicket')
            ->with(
                $this->callback(
                    function ($ticket) use ($ticket, $expectedRequester, $expectedSubmitter) {
                        $this->assertEquals($expectedRequester->getOriginId(), $ticket->getRequester()->getOriginId());
                        $this->assertEquals($expectedSubmitter->getOriginId(), $ticket->getSubmitter()->getOriginId());
                        $this->assertEquals($expectedSubmitter->getOriginId(), $ticket->getAssignee()->getOriginId());
                        return true;
                    }
                )
            )
            ->will($this->returnValue($expectedTicket));

        $this->writer->write([$ticket]);

        $ticket  = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());
        $this->assertNotEmpty($ticket->getRequester());
        $this->assertEquals($expectedRequester->getOriginId(), $ticket->getRequester()->getOriginId());

        $this->assertNotEmpty($ticket->getSubmitter());
        $this->assertEquals($expectedSubmitter->getOriginId(), $ticket->getSubmitter()->getOriginId());

        $this->assertNotEmpty($ticket->getAssignee());
        $this->assertEquals($expectedSubmitter->getOriginId(), $ticket->getAssignee()->getOriginId());

        $this->assertContains('Create user in Zendesk API [id=' . $requester->getId() . '].', $this->logOutput);
        $this->assertContains('Created user [origin_id=' . $expectedRequester->getOriginId() . '].', $this->logOutput);
        $this->assertContains('Create user in Zendesk API [id=' . $submitter->getId() . '].', $this->logOutput);
        $this->assertContains('Created user [origin_id=' . $expectedSubmitter->getOriginId() . '].', $this->logOutput);
    }

    protected function createTicket()
    {
        return new Ticket();
    }

    protected function createTicketComment()
    {
        return new TicketComment();
    }

    protected function createTicketType($name)
    {
        return new TicketType($name);
    }

    protected function createTicketStatus($name)
    {
        return new TicketStatus($name);
    }

    protected function createTicketPriority($name)
    {
        return new TicketPriority($name);
    }

    protected function createUser($originId)
    {
        $result = new User();

        $result->setOriginId($originId);

        return $result;
    }

    protected function createUserRole($name)
    {
        return new UserRole($name);
    }

    /**
     * @param string $output
     * @param array $expectedIds
     */
    protected function assertTicketCommentIds($output, array $expectedIds)
    {
        preg_match('/Schedule job to sync existing ticket comments \[ids=(.*?)\]/', $output, $matches);
        $this->assertArrayHasKey(1, $matches);
        $actualIds = explode(', ', $matches[1]);
        sort($actualIds);
        $this->assertEquals($expectedIds, $actualIds);
    }

    /**
     * @param array $arguments
     * @param array $expectedIds
     */
    protected function assertTicketJobParameters(array $arguments, array $expectedIds)
    {
        $hasParameters = false;
        foreach ($arguments as $argument) {
            if (strpos($argument, '--params=') === 0) {
                $hasParameters = true;
                $actualParameters = unserialize(substr($argument, 9));
                $this->assertArrayHasKey('id', $actualParameters);
                sort($actualParameters['id']);
                $this->assertEquals(['id' => $expectedIds], $actualParameters);
            }
        }
        $this->assertTrue($hasParameters);
    }
}
