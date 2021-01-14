<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Writer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;
use Oro\Bundle\ZendeskBundle\Handler\ExceptionHandlerInterface;
use Oro\Bundle\ZendeskBundle\ImportExport\Writer\TicketCommentExportWriter;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\InvalidRecordException;
use Oro\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketData;
use Psr\Log\LoggerInterface;

/**
 * @dbIsolationPerTest
 */
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
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
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

    /** @var  ExceptionHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $exceptionHandler;

    protected function setUp(): void
    {
        $this->initClient();

        $this->transport = $this->createMock(ZendeskTransportInterface::class);
        $this->getContainer()->set('oro_zendesk.tests.transport.rest_transport', $this->transport);

        $this->loadFixtures([LoadTicketData::class]);

        $this->channel = $this->getReference('zendesk_channel:first_test_channel');

        $this->registry = $this->getContainer()->get('doctrine');
        $this->context  = $this->createMock(ContextInterface::class);

        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([['channel', null, $this->channel->getId()]]));

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->logger->expects($this->any(0))
            ->method('log')
            ->will(
                $this->returnCallback(
                    function ($level, $message) {
                        $this->logOutput .= '[' . $level . '] ' . $message . PHP_EOL;
                    }
                )
            );

        $this->writer = $this->getContainer()->get('oro_zendesk.importexport.writer.export_ticket_comment');
        $this->writer->setImportExportContext($this->context);
        $this->writer->setLogger($this->logger);
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

        static::assertStringContainsString(
            '[info] Zendesk Ticket Comment [id=' . $comment->getId() . ']:',
            $this->logOutput
        );
        static::assertStringContainsString('Create ticket comment in Zendesk API.', $this->logOutput);
        static::assertStringContainsString(
            'Created ticket comment [origin_id=' . $expected->getOriginId() . '].',
            $this->logOutput
        );
        static::assertStringContainsString('Update ticket comment by response data.', $this->logOutput);
        static::assertStringContainsString('Update related comment.', $this->logOutput);
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

        static::assertStringContainsString(
            '[info] Zendesk Ticket Comment [id=' . $ticketComment->getId() . ']:',
            $this->logOutput
        );
        static::assertStringContainsString(
            'Create user in Zendesk API [id=' . $author->getId() . '].',
            $this->logOutput
        );
        static::assertStringContainsString(
            'Created user [origin_id=' . $expectedAuthor->getOriginId() . '].',
            $this->logOutput
        );
        static::assertStringContainsString(
            'Create ticket comment in Zendesk API.',
            $this->logOutput
        );
        static::assertStringContainsString(
            'Created ticket comment [origin_id=' . $expected->getOriginId() . '].',
            $this->logOutput
        );
        static::assertStringContainsString('Update ticket comment by response data.', $this->logOutput);
        static::assertStringContainsString('Update related comment.', $this->logOutput);
    }

    public function testWriteProhibitedToCreateEndUser()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $author = $ticketComment->getAuthor();
        $author->setRole($this->registry->getRepository('OroZendeskBundle:UserRole')->find(UserRole::ROLE_AGENT));

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

        static::assertStringContainsString(
            '[info] Zendesk Ticket Comment [id=' . $ticketComment->getId() . ']:',
            $this->logOutput
        );
        static::assertStringContainsString(
            'Create user in Zendesk API [id=' . $author->getId() . '].',
            $this->logOutput
        );
        static::assertStringContainsString('Not allowed to create user [role=agent] in Zendesk.', $this->logOutput);
    }

    public function testCreateCommentToClosedTicketWithExpectedException()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $author = $ticketComment->getAuthor();
        $author->setRole($this->registry->getRepository('OroZendeskBundle:UserRole')->find(UserRole::ROLE_AGENT));

        $this->transport->expects($this->never())->method('createUser');

        $exception = new InvalidRecordException('', 422);

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($ticketComment)
            ->willThrowException($exception);

        try {
            $this->writer->write([$ticketComment]);
        } catch (\Exception $e) {
            $this->fail(
                sprintf(
                    "Unexpected exception. Please check %s:createTicketComment",
                    TicketCommentExportWriter::class
                )
            );
        }
    }

    public function testCreateCommentToClosedTicketWithUnexpectedException()
    {
        $this->expectException(\Exception::class);

        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $author = $ticketComment->getAuthor();
        $author->setRole($this->registry->getRepository('OroZendeskBundle:UserRole')->find(UserRole::ROLE_AGENT));

        $this->transport->expects($this->never())->method('createUser');

        $exception = new \Exception();

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($ticketComment)
            ->willThrowException($exception);

        $this->writer->write([$ticketComment]);
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
