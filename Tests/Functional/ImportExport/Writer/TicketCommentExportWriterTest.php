<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Writer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;
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
    /** @var TicketCommentExportWriter */
    private $writer;

    /** @var ManagerRegistry */
    private $registry;

    /** @var ZendeskTransportInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var string */
    private $logOutput;

    protected function setUp(): void
    {
        $this->initClient();

        $this->transport = $this->createMock(ZendeskTransportInterface::class);
        $this->getContainer()->set('oro_zendesk.tests.transport.rest_transport', $this->transport);

        $this->loadFixtures([LoadTicketData::class]);

        $this->registry = $this->getContainer()->get('doctrine');

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getOption')
            ->with('channel', null)
            ->willReturn($this->getReference('zendesk_channel:first_test_channel')->getId());

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->any())
            ->method('log')
            ->willReturnCallback(function ($level, $message) {
                $this->logOutput .= '[' . $level . '] ' . $message . PHP_EOL;
            });

        $this->writer = $this->getContainer()->get('oro_zendesk.importexport.writer.export_ticket_comment');
        $this->writer->setImportExportContext($context);
        $this->writer->setLogger($logger);
    }

    private function createUser($originId): User
    {
        $result = new User();
        $result->setOriginId($originId);

        return $result;
    }

    public function testWriteCreatesTicketWithUserAuthor()
    {
        $comment = $this->getReference('zendesk_ticket_42_comment_3');

        $expected = (new TicketComment())
            ->setOriginId(20001)
            ->setBody('Updated ticket')
            ->setHtmlBody('<p>Updated Ticket</p>')
            ->setPublic(true)
            ->setAuthor($this->createUser($this->getReference('zendesk_user:james.cook@example.com')->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($comment)
            ->willReturn($expected);

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

        self::assertStringContainsString(
            '[info] Zendesk Ticket Comment [id=' . $comment->getId() . ']:',
            $this->logOutput
        );
        self::assertStringContainsString('Create ticket comment in Zendesk API.', $this->logOutput);
        self::assertStringContainsString(
            'Created ticket comment [origin_id=' . $expected->getOriginId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString('Update ticket comment by response data.', $this->logOutput);
        self::assertStringContainsString('Update related comment.', $this->logOutput);
    }

    public function testWriteCreatesTicketWithContactAuthor()
    {
        $comment = $this->getReference('zendesk_ticket_42_comment_3');

        $expected = (new TicketComment())
            ->setOriginId(20001)
            ->setBody('Updated ticket')
            ->setHtmlBody('<p>Updated Ticket</p>')
            ->setPublic(true)
            ->setAuthor($this->createUser($this->getReference('zendesk_user:jim.smith@example.com')->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($comment)
            ->willReturn($expected);

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
            ->setRole(new UserRole($author->getRole()->getName()));

        $this->transport->expects($this->once())
            ->method('createUser')
            ->with($author)
            ->willReturn($expectedAuthor);

        $expected = (new TicketComment())
            ->setOriginId(20001)
            ->setBody('Updated ticket')
            ->setHtmlBody('<p>Updated Ticket</p>')
            ->setPublic(true)
            ->setAuthor($this->createUser($expectedAuthor->getOriginId()))
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($ticketComment)
            ->willReturn($expected);

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

        self::assertStringContainsString(
            '[info] Zendesk Ticket Comment [id=' . $ticketComment->getId() . ']:',
            $this->logOutput
        );
        self::assertStringContainsString(
            'Create user in Zendesk API [id=' . $author->getId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString(
            'Created user [origin_id=' . $expectedAuthor->getOriginId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString(
            'Create ticket comment in Zendesk API.',
            $this->logOutput
        );
        self::assertStringContainsString(
            'Created ticket comment [origin_id=' . $expected->getOriginId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString('Update ticket comment by response data.', $this->logOutput);
        self::assertStringContainsString('Update related comment.', $this->logOutput);
    }

    public function testWriteProhibitedToCreateEndUser()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $author = $ticketComment->getAuthor();
        $author->setRole($this->registry->getRepository(UserRole::class)->find(UserRole::ROLE_AGENT));

        $this->transport->expects($this->never())
            ->method('createUser');

        $expected = (new TicketComment())
            ->setOriginId(20001)
            ->setBody('Updated ticket')
            ->setHtmlBody('<p>Updated Ticket</p>')
            ->setPublic(true)
            ->setOriginCreatedAt(new \DateTime('2014-06-06T12:24:24+0000'));

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($ticketComment)
            ->willReturn($expected);

        $this->writer->write([$ticketComment]);

        $ticketComment = $this->registry->getRepository(get_class($ticketComment))->find($ticketComment->getId());
        $this->assertEmpty($ticketComment->getAuthor());

        self::assertStringContainsString(
            '[info] Zendesk Ticket Comment [id=' . $ticketComment->getId() . ']:',
            $this->logOutput
        );
        self::assertStringContainsString(
            'Create user in Zendesk API [id=' . $author->getId() . '].',
            $this->logOutput
        );
        self::assertStringContainsString('Not allowed to create user [role=agent] in Zendesk.', $this->logOutput);
    }

    public function testCreateCommentToClosedTicketWithExpectedException()
    {
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $author = $ticketComment->getAuthor();
        $author->setRole($this->registry->getRepository(UserRole::class)->find(UserRole::ROLE_AGENT));

        $this->transport->expects($this->never())
            ->method('createUser');

        $exception = new InvalidRecordException('', 422);

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($ticketComment)
            ->willThrowException($exception);

        try {
            $this->writer->write([$ticketComment]);
        } catch (\Exception $e) {
            $this->fail(sprintf(
                'Unexpected exception. Please check %s:createTicketComment',
                TicketCommentExportWriter::class
            ));
        }
    }

    public function testCreateCommentToClosedTicketWithUnexpectedException()
    {
        $this->expectException(\Exception::class);

        $ticketComment = $this->getReference('zendesk_ticket_42_comment_4');
        $author = $ticketComment->getAuthor();
        $author->setRole($this->registry->getRepository(UserRole::class)->find(UserRole::ROLE_AGENT));

        $this->transport->expects($this->never())
            ->method('createUser');

        $exception = new \Exception();

        $this->transport->expects($this->once())
            ->method('addTicketComment')
            ->with($ticketComment)
            ->willThrowException($exception);

        $this->writer->write([$ticketComment]);
    }
}
