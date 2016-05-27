<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Writer;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Writer\TicketCommentExportWriter;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class TicketCommentExportWriterTest extends WebTestCase
{
    /**
     * @var TicketCommentExportWriter
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
        $this->startTransaction();
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

        $this->writer = $this->getContainer()->get('orocrm_zendesk.importexport.writer.export_ticket_comment');
        $this->writer->setImportExportContext($this->context);
        $this->writer->setLogger($this->logger);
    }

    protected function tearDown()
    {
        $this->getContainer()->set('orocrm_zendesk.transport.rest_transport', null);
        $this->getContainer()->set('orocrm_zendesk.importexport.writer.export_ticket_comment', null);
        $this->logOutput = null;

        parent::tearDown();

        self::$loadedFixtures = [];
        $this->rollbackTransaction();
    }

    public function testWriteCreatesTicketWithUserAuthor()
    {
        $comment = $this->getReference('zendesk_ticket_42_comment_3');

        $expected = $this->createTicketComment()
            ->setOriginId(20001)
            ->setBody('Updated ticket')
            ->setHtmlBody('<p>Updated Ticket</p>')
            ->setPublic(true)
            ->setAuthor($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($comment)
            ->will($this->returnValue($expected));

        $this->writer->write([$comment]);

        $comment = $this->registry->getRepository(get_class($comment))->find($comment->getId());

        $this->assertEquals($expected->getOriginId(), $comment->getOriginId());
        $this->assertEquals($expected->getBody(), $comment->getBody());
        $this->assertEquals($expected->getHtmlBody(), $comment->getHtmlBody());
        $this->assertEquals($expected->getPublic(), $comment->getPublic());
        $this->assertEquals($expected->getAuthor()->getOriginId(), $comment->getAuthor()->getOriginId());
        $this->assertEquals($comment->getTicket()->getOriginId(), $comment->getTicket()->getOriginId());
        $this->assertEquals($expected->getOriginCreatedAt(), $comment->getOriginCreatedAt());

        $relatedComment = $comment->getRelatedComment();
        $this->assertEquals($expected->getBody(), $relatedComment->getMessage());
        $this->assertEquals($expected->getPublic(), $relatedComment->isPublic());
        $this->assertEquals($expected->getOriginCreatedAt(), $relatedComment->getCreatedAt());
        $this->assertEquals('james.cook@example.com', $relatedComment->getOwner()->getEmail());
        $this->assertEmpty($relatedComment->getContact());

        $this->assertContains('[info] Zendesk Ticket Comment [id=' . $comment->getId() . ']:', $this->logOutput);
        $this->assertContains('Create ticket comment in Zendesk API.', $this->logOutput);
        $this->assertContains('Created ticket comment [origin_id=' . $expected->getOriginId() . '].', $this->logOutput);
        $this->assertContains('Update ticket comment by response data.', $this->logOutput);
        $this->assertContains('Update related comment.', $this->logOutput);
    }

    public function testWriteCreatesTicketWithContactAuthor()
    {
        $comment = $this->getReference('zendesk_ticket_42_comment_3');

        $expected = $this->createTicketComment()
            ->setOriginId(20001)
            ->setBody('Updated ticket')
            ->setHtmlBody('<p>Updated Ticket</p>')
            ->setPublic(true)
            ->setAuthor($this->createUser($this->getReference('zendesk_user:jim.smith@example.com')->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($comment)
            ->will($this->returnValue($expected));

        $this->writer->write([$comment]);

        $comment = $this->registry->getRepository(get_class($comment))->find($comment->getId());
        $relatedComment = $comment->getRelatedComment();
        $this->assertNotEmpty($relatedComment->getContact());
        $this->assertEquals('jim.smith@example.com', $relatedComment->getContact()->getPrimaryEmail());
    }

    public function testWriteCreatesNewUser()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $author = $ticketComment->getAuthor();

        $expectedAuthor = $this->createUser(10001)
            ->setUrl('https://foo.zendesk.com/api/v2/users/10001.json')
            ->setName($author->getName())
            ->setEmail($author->getEmail())
            ->setRole($this->createUserRole($author->getRole()->getName()));

        $this->transport->expects($this->once())
            ->method('createUser')
            ->with($author)
            ->will($this->returnValue($expectedAuthor));

        $expected = $this->createTicketComment()
            ->setOriginId(20001)
            ->setBody('Updated ticket')
            ->setHtmlBody('<p>Updated Ticket</p>')
            ->setPublic(true)
            ->setAuthor($this->createUser($expectedAuthor->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($ticketComment)
            ->will($this->returnValue($expected));

        $this->writer->write([$ticketComment]);

        $ticketComment = $this->registry->getRepository(get_class($ticketComment))->find($ticketComment->getId());
        $author = $ticketComment->getAuthor();
        $this->assertNotEmpty($ticketComment->getAuthor());
        $this->assertEquals($expectedAuthor->getOriginId(), $author->getOriginId());
        $this->assertEquals($expectedAuthor->getName(), $author->getName());
        $this->assertEquals($expectedAuthor->getEmail(), $author->getEmail());
        $this->assertEquals($expectedAuthor->getRole()->getName(), $author->getRole()->getName());

        $relatedComment = $ticketComment->getRelatedComment();

        $this->assertEquals('admin@example.com', $relatedComment->getOwner()->getEmail());
        $this->assertNotEmpty($relatedComment->getContact());
        $this->assertEquals($expectedAuthor->getEmail(), $relatedComment->getContact()->getPrimaryEmail());
        $this->assertEquals('Alex', $relatedComment->getContact()->getFirstName());
        $this->assertEquals('Miller', $relatedComment->getContact()->getLastName());

        $this->assertContains('[info] Zendesk Ticket Comment [id=' . $ticketComment->getId() . ']:', $this->logOutput);
        $this->assertContains('Create user in Zendesk API [id=' . $author->getId() . '].', $this->logOutput);
        $this->assertContains('Created user [origin_id=' . $expectedAuthor->getOriginId() . '].', $this->logOutput);
        $this->assertContains('Create ticket comment in Zendesk API.', $this->logOutput);
        $this->assertContains(
            'Created ticket comment [origin_id=' . $expected->getOriginId() . '].',
            $this->logOutput
        );
        $this->assertContains('Update ticket comment by response data.', $this->logOutput);
        $this->assertContains('Update related comment.', $this->logOutput);
    }

    public function testWriteProhibitedToCreateEndUser()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $author = $ticketComment->getAuthor();
        $author->setRole($this->registry->getRepository('OroCRMZendeskBundle:UserRole')->find(UserRole::ROLE_AGENT));

        $this->transport->expects($this->never())->method('createUser');

        $expected = $this->createTicketComment()
            ->setOriginId(20001)
            ->setBody('Updated ticket')
            ->setHtmlBody('<p>Updated Ticket</p>')
            ->setPublic(true)
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($ticketComment)
            ->will($this->returnValue($expected));

        $this->writer->write([$ticketComment]);

        $ticketComment = $this->registry->getRepository(get_class($ticketComment))->find($ticketComment->getId());
        $this->assertEmpty($ticketComment->getAuthor());

        $this->assertContains('[info] Zendesk Ticket Comment [id=' . $ticketComment->getId() . ']:', $this->logOutput);
        $this->assertContains('Create user in Zendesk API [id=' . $author->getId() . '].', $this->logOutput);
        $this->assertContains('Not allowed to create user [role=agent] in Zendesk.', $this->logOutput);
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
}
