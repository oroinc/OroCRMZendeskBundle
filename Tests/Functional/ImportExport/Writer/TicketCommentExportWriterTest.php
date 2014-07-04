<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Writer;

use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;
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
use OroCRM\Bundle\ZendeskBundle\ImportExport\Writer\TicketCommentExportWriter;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketCommentConnector;

class TicketCommentExportWriterTest extends WebTestCase
{
    /**
     * @var TicketCommentExportWriter
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

        $this->writer = $this->getContainer()->get('orocrm_zendesk.importexport.writer.ticket_comment_export');
        $this->writer->setImportExportContext($this->context);
        $this->writer->setLogger($this->logger);
    }

    protected function tearDown()
    {
        $this->getContainer()->set('orocrm_zendesk.transport.rest_transport', null);
        $this->getContainer()->set('orocrm_zendesk.importexport.writer.ticket_comment_export', null);
        $this->logOutput = null;
        $this->client->rollbackTransaction();
    }

    public function testWriteCreatesTicketWithUserAuthor()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_3');

        $requestData = $this->serializer->serialize($ticketComment, null);
        $expected = [
            'id' => 20001,
            'body' => 'Updated ticket',
            'html_body' => '<p>Updated Ticket</p>',
            'public' => true,
            'author_id' => $requesterId = $this->getReference('zendesk_user:james.cook@example.com')->getOriginId(),
            'ticket_id' => $ticketComment->getTicket()->getOriginId(),
            'created_at' => '2014-06-06T12:24:24+0000',
        ];

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($requestData)
            ->will($this->returnValue($expected));

        $this->writer->write([$ticketComment]);

        $ticketComment = $this->entityManager->find(get_class($ticketComment), $ticketComment->getId());

        $this->assertEquals($expected['id'], $ticketComment->getOriginId());
        $this->assertEquals($expected['body'], $ticketComment->getBody());
        $this->assertEquals($expected['html_body'], $ticketComment->getHtmlBody());
        $this->assertEquals($expected['public'], $ticketComment->getPublic());
        $this->assertEquals($expected['author_id'], $ticketComment->getAuthor()->getOriginId());
        $this->assertEquals($expected['ticket_id'], $ticketComment->getTicket()->getOriginId());
        $this->assertEquals($expected['created_at'], $ticketComment->getOriginCreatedAt()->format(\DateTime::ISO8601));

        $relatedComment = $ticketComment->getRelatedComment();
        $this->assertEquals($expected['body'], $relatedComment->getMessage());
        $this->assertEquals($expected['public'], $relatedComment->isPublic());
        $this->assertEquals($expected['created_at'], $relatedComment->getCreatedAt()->format(\DateTime::ISO8601));
        $this->assertEquals('james.cook@example.com', $relatedComment->getOwner()->getEmail());
        $this->assertEmpty($relatedComment->getContact());

        $this->assertContains('[info] Zendesk Ticket Comment [id=' . $ticketComment->getId() . ']:', $this->logOutput);
        $this->assertContains('Create ticket comment in Zendesk API.', $this->logOutput);
        $this->assertContains('Created ticket comment [origin_id=' . $expected['id'] . '].', $this->logOutput);
        $this->assertContains('Update ticket comment by response data.', $this->logOutput);
        $this->assertContains('Update related comment.', $this->logOutput);
    }

    public function testWriteCreatesTicketWithContactAuthor()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_3');

        $requestData = $this->serializer->serialize($ticketComment, null);
        $expected = [
            'id' => 20001,
            'body' => 'Updated ticket',
            'html_body' => '<p>Updated Ticket</p>',
            'public' => true,
            'author_id' => $requesterId = $this->getReference('zendesk_user:jim.smith@example.com')->getOriginId(),
            'ticket_id' => $ticketComment->getTicket()->getOriginId(),
            'created_at' => '2014-06-06T12:24:24+0000',
        ];

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($requestData)
            ->will($this->returnValue($expected));

        $this->writer->write([$ticketComment]);

        $ticketComment = $this->entityManager->find(get_class($ticketComment), $ticketComment->getId());
        $relatedComment = $ticketComment->getRelatedComment();
        $this->assertNotEmpty($relatedComment->getContact());
        $this->assertEquals('jim.smith@example.com', $relatedComment->getContact()->getPrimaryEmail());
    }

    public function testWriteCreatesNewUser()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $authorUser = $ticketComment->getAuthor();

        $requestUserData = $this->serializer->serialize($authorUser, null);
        $expectedUserResponseData = [
            'id' => $authorId = 10001,
            'url' => $url = 'https://foo.zendesk.com/api/v2/users/10001.json',
            'name' => $authorUser->getName(),
            'email' => $authorUser->getEmail(),
            'role' => $authorUser->getRole()->getName(),
        ];

        $this->transport->expects($this->once())
            ->method('createUser')
            ->with($requestUserData)
            ->will($this->returnValue($expectedUserResponseData));

        $requestCommentData = $this->serializer->serialize($ticketComment, null);
        $requestCommentData['author_id'] = $authorId;
        $expectedCommentResponseData = [
            'id' => 20001,
            'body' => 'Updated ticket',
            'html_body' => '<p>Updated Ticket</p>',
            'public' => true,
            'author_id' => $authorId,
            'ticket_id' => $ticketComment->getTicket()->getOriginId(),
            'created_at' => '2014-06-06T12:24:24+0000',
        ];

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($requestCommentData)
            ->will($this->returnValue($expectedCommentResponseData));

        $expected = $expectedCommentResponseData;

        $this->writer->write([$ticketComment]);

        $ticketComment = $this->entityManager->find(get_class($ticketComment), $ticketComment->getId());
        $authorUser = $ticketComment->getAuthor();
        $this->assertNotEmpty($authorId, $authorUser);
        $this->assertEquals($authorId, $authorUser->getOriginId());

        $relatedComment = $ticketComment->getRelatedComment();

        $this->assertEquals('admin@example.com', $relatedComment->getOwner()->getEmail());
        $this->assertNotEmpty($relatedComment->getContact());
        $this->assertEquals($authorUser->getEmail(), $relatedComment->getContact()->getPrimaryEmail());

        $this->assertContains('[info] Zendesk Ticket Comment [id=' . $ticketComment->getId() . ']:', $this->logOutput);
        $this->assertContains('Create user in Zendesk API [id=' . $authorUser->getId() . '].', $this->logOutput);
        $this->assertContains('Created user [origin_id=' . $authorId . '].', $this->logOutput);
        $this->assertContains('Create ticket comment in Zendesk API.', $this->logOutput);
        $this->assertContains('Created ticket comment [origin_id=' . $expected['id'] . '].', $this->logOutput);
        $this->assertContains('Update ticket comment by response data.', $this->logOutput);
        $this->assertContains('Update related comment.', $this->logOutput);
    }

    public function testWriteProhibitedToCreateEndUser()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $authorUser = $ticketComment->getAuthor();
        $authorUser->setRole($this->entityManager->find('OroCRMZendeskBundle:UserRole', UserRole::ROLE_AGENT));

        $this->transport->expects($this->never())->method('createUser');

        $requestCommentData = $this->serializer->serialize($ticketComment, null);
        $requestCommentData['author_id'] = null;
        $expectedCommentResponseData = [
            'id' => 20001,
            'body' => 'Updated ticket',
            'html_body' => '<p>Updated Ticket</p>',
            'public' => true,
            'ticket_id' => $ticketComment->getTicket()->getOriginId(),
            'created_at' => '2014-06-06T12:24:24+0000',
        ];

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($requestCommentData)
            ->will($this->returnValue($expectedCommentResponseData));

        $expected = $expectedCommentResponseData;

        $this->writer->write([$ticketComment]);

        $ticketComment = $this->entityManager->find(get_class($ticketComment), $ticketComment->getId());
        $this->assertEmpty($ticketComment->getAuthor());

        $this->assertContains('[info] Zendesk Ticket Comment [id=' . $ticketComment->getId() . ']:', $this->logOutput);
        $this->assertContains('Create user in Zendesk API [id=' . $authorUser->getId() . '].', $this->logOutput);
        $this->assertContains('Not allowed to create user [role=agent] in Zendesk.', $this->logOutput);
    }
}
