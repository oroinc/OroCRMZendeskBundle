<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Writer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CaseBundle\Entity\CasePriority;
use Oro\Bundle\CaseBundle\Entity\CaseStatus;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Async\Topic\ReverseSyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;
use Oro\Bundle\ZendeskBundle\ImportExport\Writer\TicketExportWriter;
use Oro\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketData;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Psr\Log\LoggerInterface;

/**
 * @dbIsolationPerTest
 */
class TicketExportWriterTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var TicketExportWriter */
    private $writer;

    /** @var ManagerRegistry */
    private $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var Channel */
    private $channel;

    /** @var string */
    private $logOutput;

    protected function setUp(): void
    {
        $this->initClient();

        $this->transport = $this->createMock(ZendeskTransportInterface::class);
        self::getContainer()->set('oro_zendesk.tests.transport.rest_transport', $this->transport);

        $this->loadFixtures([LoadTicketData::class]);

        $this->channel = $this->getReference('zendesk_channel:first_test_channel');

        $this->registry = self::getContainer()->get('doctrine');

        $this->context = $this->createMock(ContextInterface::class);
        $this->context->expects(self::once())
            ->method('getOption')
            ->with('channel', null)
            ->willReturn($this->channel->getId());

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects(self::any())
            ->method('log')
            ->willReturnCallback(function ($level, $message) {
                $this->logOutput .= '[' . $level . '] ' . $message . PHP_EOL;
            });

        $this->writer = self::getContainer()->get('oro_zendesk.importexport.writer.export_ticket');
        $this->writer->setImportExportContext($this->context);
        $this->writer->setLogger($this->logger);
    }

    public function testWriteCreatesTicket(): void
    {
        /** @var Ticket $ticket */
        $ticket = $this->getReference('oro_zendesk:not_synced_ticket');

        $expected = (new Ticket())
            ->setOriginId(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/10001.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType(new TicketType(TicketType::TYPE_TASK))
            ->setStatus(new TicketStatus(TicketStatus::STATUS_OPEN))
            ->setPriority(new TicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setRequester($this->createUser('zendesk_user:james.cook@example.com'))
            ->setSubmitter($this->createUser('zendesk_user:james.cook@example.com'))
            ->setAssignee($this->createUser('zendesk_user:james.cook@example.com'))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $this->transport->expects(self::once())
            ->method('createTicket')
            ->with($ticket)
            ->willReturn(['ticket' => $expected, 'comment' => null]);

        $this->writer->write([$ticket]);

        /** @var Ticket $ticket */
        $ticket = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());

        self::assertEquals($expected->getOriginId(), $ticket->getOriginId());
        self::assertEquals($expected->getUrl(), $ticket->getUrl());
        self::assertEquals($expected->getSubject(), $ticket->getSubject());
        self::assertEquals($expected->getDescription(), $ticket->getDescription());
        self::assertEquals($expected->getType()->getName(), $ticket->getType()->getName());
        self::assertEquals($expected->getStatus()->getName(), $ticket->getStatus()->getName());
        self::assertEquals($expected->getPriority()->getName(), $ticket->getPriority()->getName());
        self::assertEquals($expected->getRequester()->getOriginId(), $ticket->getRequester()->getOriginId());
        self::assertEquals($expected->getSubmitter()->getOriginId(), $ticket->getSubmitter()->getOriginId());
        self::assertEquals($expected->getAssignee()->getOriginId(), $ticket->getAssignee()->getOriginId());
        self::assertEquals($expected->getOriginCreatedAt(), $ticket->getOriginCreatedAt());
        self::assertEquals($expected->getOriginUpdatedAt(), $ticket->getOriginUpdatedAt());

        $relatedCase = $ticket->getRelatedCase();
        self::assertEquals($expected->getSubject(), $relatedCase->getSubject());
        self::assertEquals($expected->getDescription(), $relatedCase->getDescription());
        self::assertEquals(CaseStatus::STATUS_OPEN, $relatedCase->getStatus()->getName());
        self::assertEquals(CasePriority::PRIORITY_NORMAL, $relatedCase->getPriority()->getName());
        self::assertEquals('james.cook@example.com', $relatedCase->getAssignedTo()->getEmail());
        self::assertEquals('james.cook@example.com', $relatedCase->getOwner()->getEmail());

        self::assertStringContainsString('[info] Zendesk Ticket [id=' . $ticket->getId() . ']:', $this->logOutput);
        self::assertStringContainsString('Create ticket in Zendesk API.', $this->logOutput);
        self::assertStringContainsString(
            'Created ticket [origin_id=' . $expected->getOriginId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString('Update ticket by response data.', $this->logOutput);
        self::assertStringContainsString('Update related case.', $this->logOutput);
    }

    public function testWriteCreatesComment(): void
    {
        $ticket = $this->getReference('oro_zendesk:not_synced_ticket');

        $expectedTicket = (new Ticket())
            ->setOriginId(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/10001.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType(new TicketType(TicketType::TYPE_TASK))
            ->setStatus(new TicketStatus(TicketStatus::STATUS_OPEN))
            ->setPriority(new TicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setRequester($this->createUser('zendesk_user:james.cook@example.com'))
            ->setSubmitter($this->createUser('zendesk_user:james.cook@example.com'))
            ->setAssignee($this->createUser('zendesk_user:james.cook@example.com'))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $expectedComment = (new TicketComment())
            ->setOriginId(20001)
            ->setAuthor($expectedTicket->getRequester())
            ->setBody($expectedTicket->getDescription())
            ->setHtmlBody('<p>' . $expectedTicket->getDescription() . '</p>')
            ->setPublic(true)
            ->setTicket((new Ticket())->setOriginId($expectedTicket->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects(self::once())
            ->method('createTicket')
            ->with($ticket)
            ->willReturn(['ticket' => $expectedTicket, 'comment' => $expectedComment]);

        $this->writer->write([$ticket]);

        $ticket = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());

        self::assertEquals(1, $ticket->getComments()->count());
        $comment = $ticket->getComments()->first();

        self::assertEquals($expectedComment->getOriginId(), $comment->getOriginId());
        self::assertEquals($expectedComment->getBody(), $comment->getBody());
        self::assertEquals($expectedComment->getHtmlBody(), $comment->getHtmlBody());
        self::assertEquals($expectedComment->getPublic(), $comment->getPublic());
        self::assertEquals($expectedComment->getAuthor()->getOriginId(), $comment->getAuthor()->getOriginId());
        self::assertEquals($expectedComment->getTicket()->getOriginId(), $comment->getTicket()->getOriginId());
        self::assertEquals($expectedComment->getOriginCreatedAt(), $comment->getOriginCreatedAt());

        $relatedComment = $comment->getRelatedComment();
        self::assertNotNull($relatedComment);
        self::assertEquals($expectedComment->getBody(), $relatedComment->getMessage());
        self::assertEquals($expectedComment->getPublic(), $relatedComment->isPublic());
        self::assertNotEmpty($relatedComment->getOwner());
        self::assertEquals(
            'james.cook@example.com',
            $relatedComment->getOwner()->getEmail()
        );

        self::assertStringContainsString(
            'Created ticket comment [origin_id=' . $expectedComment->getOriginId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString('Update related case comment.', $this->logOutput);
        self::assertStringNotContainsString('Schedule job to sync existing ticket comments.', $this->logOutput);
    }

    public function testWriteCreatesCommentWithExistingContact(): void
    {
        $ticket = $this->getReference('oro_zendesk:not_synced_ticket');

        $expectedTicket = (new Ticket())
            ->setOriginId(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/10001.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType(new TicketType(TicketType::TYPE_TASK))
            ->setStatus(new TicketStatus(TicketStatus::STATUS_OPEN))
            ->setPriority(new TicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setRequester($this->createUser('zendesk_user:jim.smith@example.com'))
            ->setSubmitter($this->createUser('zendesk_user:james.cook@example.com'))
            ->setAssignee($this->createUser('zendesk_user:james.cook@example.com'))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $expectedComment = (new TicketComment())
            ->setOriginId(20001)
            ->setAuthor($expectedTicket->getRequester())
            ->setBody($expectedTicket->getDescription())
            ->setHtmlBody('<p>' . $expectedTicket->getDescription() . '</p>')
            ->setPublic(true)
            ->setTicket((new Ticket())->setOriginId($expectedTicket->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects(self::once())
            ->method('createTicket')
            ->with($ticket)
            ->willReturn(['ticket' => $expectedTicket, 'comment' => $expectedComment]);

        $this->writer->write([$ticket]);

        $ticket = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());
        $comment = $ticket->getComments()->first();

        $relatedComment = $comment->getRelatedComment();
        self::assertNotEmpty($relatedComment->getContact());
        self::assertEquals(
            'jim.smith@example.com',
            $relatedComment->getContact()->getPrimaryEmail()
        );
    }

    public function testWriteSchedulesTicketCommentSync(): void
    {
        $ticket = $this->getReference('oro_zendesk:not_synced_ticket_with_case_comments');

        $expectedTicket = (new Ticket())
            ->setOriginId(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/10001.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType(new TicketType(TicketType::TYPE_TASK))
            ->setStatus(new TicketStatus(TicketStatus::STATUS_OPEN))
            ->setPriority(new TicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setRequester($this->createUser('zendesk_user:jim.smith@example.com'))
            ->setSubmitter($this->createUser('zendesk_user:james.cook@example.com'))
            ->setAssignee($this->createUser('zendesk_user:james.cook@example.com'))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $expectedComment = (new TicketComment())
            ->setOriginId(20001)
            ->setAuthor($expectedTicket->getRequester())
            ->setBody($expectedTicket->getDescription())
            ->setHtmlBody('<p>' . $expectedTicket->getDescription() . '</p>')
            ->setPublic(true)
            ->setTicket((new Ticket())->setOriginId($expectedTicket->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects(self::once())
            ->method('createTicket')
            ->with($ticket)
            ->willReturn(['ticket' => $expectedTicket, 'comment' => $expectedComment]);

        $this->writer->write([$ticket]);

        $ticket = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());
        self::assertEquals(3, $ticket->getComments()->count());

        $commentIds = [];
        foreach ($ticket->getComments() as $comment) {
            if ($comment->getOriginId()) {
                continue;
            }

            $commentIds[] = $comment->getId();
            self::assertNotNull($comment->getRelatedComment(), 'Ticket comment has related case comment.');
        }
        sort($commentIds);

        self::assertStringContainsString('Create ticket comment for case comment', $this->logOutput);

        self::assertStringContainsString('Schedule job to sync existing ticket comments', $this->logOutput);
        self::assertTicketCommentIds($this->logOutput, $commentIds);

        self::assertMessageSent(
            ReverseSyncIntegrationTopic::getName(),
            [
                'integration_id' => $this->channel->getId(),
                'connector_parameters' => [
                    'id' => $commentIds,
                ],
                'connector' => 'ticket_comment',

            ]
        );
        self::assertMessageSentWithPriority(ReverseSyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }

    public function testWriteUpdatesTicket(): void
    {
        /** @var Ticket $ticket */
        $ticket = $this->getReference('oro_zendesk:ticket_43');

        $expected = (new Ticket())
            ->setOriginId($ticket->getOriginId())
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/43.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType(new TicketType(TicketType::TYPE_TASK))
            ->setStatus(new TicketStatus(TicketStatus::STATUS_CLOSED))
            ->setPriority(new TicketPriority(TicketPriority::PRIORITY_LOW))
            ->setRequester($this->createUser('zendesk_user:james.cook@example.com'))
            ->setSubmitter($this->createUser('zendesk_user:james.cook@example.com'))
            ->setAssignee($this->createUser('zendesk_user:james.cook@example.com'))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $this->transport->expects(self::once())
            ->method('updateTicket')
            ->with($ticket)
            ->willReturn($expected);

        $this->writer->write([$ticket]);

        /** @var Ticket $ticket */
        $ticket = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());

        self::assertEquals($expected->getOriginId(), $ticket->getOriginId());
        self::assertEquals($expected->getUrl(), $ticket->getUrl());
        self::assertEquals($expected->getSubject(), $ticket->getSubject());
        self::assertEquals($expected->getDescription(), $ticket->getDescription());
        self::assertEquals($expected->getType()->getName(), $ticket->getType()->getName());
        self::assertEquals($expected->getStatus()->getName(), $ticket->getStatus()->getName());
        self::assertEquals($expected->getPriority()->getName(), $ticket->getPriority()->getName());
        self::assertEquals($expected->getRequester()->getOriginId(), $ticket->getRequester()->getOriginId());
        self::assertEquals($expected->getSubmitter()->getOriginId(), $ticket->getSubmitter()->getOriginId());
        self::assertEquals($expected->getAssignee()->getOriginId(), $ticket->getAssignee()->getOriginId());
        self::assertEquals($expected->getOriginCreatedAt(), $ticket->getOriginCreatedAt());
        self::assertEquals($expected->getOriginUpdatedAt(), $ticket->getOriginUpdatedAt());

        $relatedCase = $ticket->getRelatedCase();
        self::assertEquals($expected->getSubject(), $relatedCase->getSubject());
        self::assertEquals($expected->getDescription(), $relatedCase->getDescription());
        self::assertEquals(CaseStatus::STATUS_CLOSED, $relatedCase->getStatus()->getName());
        self::assertEquals(CasePriority::PRIORITY_LOW, $relatedCase->getPriority()->getName());
        self::assertEquals('james.cook@example.com', $relatedCase->getAssignedTo()->getEmail());
        self::assertEquals('james.cook@example.com', $relatedCase->getOwner()->getEmail());

        self::assertStringContainsString('[info] Zendesk Ticket [id=' . $ticket->getId() . ']:', $this->logOutput);
        self::assertStringContainsString(
            'Update ticket in Zendesk API [origin_id=' . $ticket->getOriginId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString('Update ticket by response data.', $this->logOutput);
        self::assertStringContainsString('Update related case.', $this->logOutput);
    }

    public function testWriteCreatesUsers(): void
    {
        $requester = $this->getReference('zendesk_user:sam.rogers@example.com');
        $submitter = $this->getReference('zendesk_user:garry.smith@example.com');
        $assignee = $submitter;

        $ticket = $this->getReference('oro_zendesk:ticket_43');
        $ticket->setRequester($requester);
        $ticket->setSubmitter($submitter);
        $ticket->setAssignee($assignee);
        $this->registry->getManager()->flush($ticket);

        $expectedRequester = (new User())
            ->setOriginId(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/users/10001.json')
            ->setName($requester->getName())
            ->setEmail($requester->getEmail())
            ->setRole(new UserRole($requester->getRole()->getName()));

        $expectedSubmitter = (new User())
            ->setOriginId(10002)
            ->setUrl('https://foo.zendesk.com/api/v2/users/10002.json')
            ->setName($submitter->getName())
            ->setEmail($submitter->getEmail())
            ->setRole(new UserRole($submitter->getRole()->getName()));

        $this->transport->expects(self::exactly(2))
            ->method('createUser')
            ->withConsecutive([$requester], [$submitter])
            ->willReturnOnConsecutiveCalls($expectedRequester, $expectedSubmitter);

        $expectedTicket = (new Ticket())
            ->setOriginId($ticket->getOriginId())
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/43.json')
            ->setSubject('Updated Subject')
            ->setDescription('Updated Description')
            ->setType(new TicketType(TicketType::TYPE_TASK))
            ->setStatus(new TicketStatus(TicketStatus::STATUS_CLOSED))
            ->setPriority(new TicketPriority(TicketPriority::PRIORITY_LOW))
            ->setRequester((new User())->setOriginId($expectedRequester->getOriginId()))
            ->setSubmitter((new User())->setOriginId($expectedSubmitter->getOriginId()))
            ->setAssignee((new User())->setOriginId($expectedSubmitter->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:23+0000'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-09T13:43:21+0000'));

        $this->transport->expects(self::once())
            ->method('updateTicket')
            ->with(
                self::callback(function ($ticket) use ($expectedRequester, $expectedSubmitter) {
                    $this->assertEquals($expectedRequester->getOriginId(), $ticket->getRequester()->getOriginId());
                    $this->assertEquals($expectedSubmitter->getOriginId(), $ticket->getSubmitter()->getOriginId());
                    $this->assertEquals($expectedSubmitter->getOriginId(), $ticket->getAssignee()->getOriginId());

                    return true;
                })
            )
            ->willReturn($expectedTicket);

        $this->writer->write([$ticket]);

        $ticket = $this->registry->getRepository(get_class($ticket))->find($ticket->getId());
        self::assertNotEmpty($ticket->getRequester());
        self::assertEquals($expectedRequester->getOriginId(), $ticket->getRequester()->getOriginId());

        self::assertNotEmpty($ticket->getSubmitter());
        self::assertEquals($expectedSubmitter->getOriginId(), $ticket->getSubmitter()->getOriginId());

        self::assertNotEmpty($ticket->getAssignee());
        self::assertEquals($expectedSubmitter->getOriginId(), $ticket->getAssignee()->getOriginId());

        self::assertStringContainsString(
            'Create user in Zendesk API [id=' . $requester->getId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString(
            'Created user [origin_id=' . $expectedRequester->getOriginId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString(
            'Create user in Zendesk API [id=' . $submitter->getId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString(
            'Created user [origin_id=' . $expectedSubmitter->getOriginId() . '].',
            $this->logOutput
        );
    }

    private function createUser(string $zendeskUserReference): User
    {
        $user = new User();
        $user->setOriginId($this->getReference($zendeskUserReference)->getOriginId());

        return $user;
    }

    private static function assertTicketCommentIds(string $output, array $expectedIds): void
    {
        preg_match('/Schedule job to sync existing ticket comments \[ids=(.*?)\]/', $output, $matches);
        self::assertArrayHasKey(1, $matches);
        $actualIds = explode(', ', $matches[1]);
        sort($actualIds);
        self::assertEquals($expectedIds, $actualIds);
    }
}
