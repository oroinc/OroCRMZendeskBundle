<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Writer;

use Symfony\Component\Serializer\SerializerInterface;

use OroCRM\Bundle\CaseBundle\Entity\CasePriority;
use OroCRM\Bundle\CaseBundle\Entity\CaseStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Writer\TicketExportWriter;

/**
 * @dbIsolation
 */
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
        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);

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
                    function ($level, $message, array $context) {
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
    }

    public function testWriteCreatesTicket()
    {
        $ticket = $this->getReference('orocrm_zendesk:not_synced_ticket');
        $ticket->setOriginId(null);

        $requestData = $this->serializer->serialize($ticket, null);
        $expectedResponseData = [
            'ticket' => [
                'id' => 10001,
                'url' => 'https://foo.zendesk.com/api/v2/tickets/10001.json',
                'subject' => 'Updated Subject',
                'description' => 'Updated Description',
                'type' => TicketType::TYPE_TASK,
                'status' => TicketStatus::STATUS_OPEN,
                'priority' => TicketPriority::PRIORITY_NORMAL,
                'requester_id' => $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
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

        $this->assertContains('[info] Zendesk Ticket [id=' . $ticket->getId() . ']:', $this->logOutput);
        $this->assertContains('Create ticket in Zendesk API.', $this->logOutput);
        $this->assertContains('Created ticket [origin_id=' . $expected['id'] . '].', $this->logOutput);
        $this->assertContains('Update ticket by response data.', $this->logOutput);
        $this->assertContains('Update related case.', $this->logOutput);

        // @todo Check case contact and user
    }

    public function testWriteCreatesComment()
    {
        $ticket = $this->getReference('orocrm_zendesk:not_synced_ticket');
        $ticket->setOriginId(null);

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

        // @todo Check comment owner and contact

        $this->assertContains('Created ticket comment [origin_id=' . $expected['id'] . '].', $this->logOutput);
        $this->assertContains('Update related case comment.', $this->logOutput);
        $this->assertNotContains('Schedule job to sync existing ticket comments.', $this->logOutput);
    }
}
