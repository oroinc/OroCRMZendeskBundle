<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use Oro\Bundle\ZendeskBundle\ImportExport\Processor\ImportTicketCommentProcessor;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketData;

class ImportTicketCommentProcessorTest extends WebTestCase
{
    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;
    private ManagerRegistry $registry;
    private Channel $channel;
    private ImportTicketCommentProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTicketData::class]);

        $this->context = $this->createMock(ContextInterface::class);
        $this->registry = $this->getContainer()->get('doctrine');
        $this->processor = $this->getContainer()->get('oro_zendesk.importexport.processor.import_ticket_comment');
        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->processor->setImportExportContext($this->context);
    }

    public function testProcessFailsWithInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Imported entity must be instance of %s, stdClass given.',
            TicketComment::class
        ));

        $this->processor->process(new \stdClass());
    }

    public function testProcessNewZendeskTicketComment()
    {
        $this->setExpectedContextOptions(['channel' => $this->channel->getId()]);
        $ticketId = 43;
        $ticketComment = $this->createZendeskTicketComment()
            ->setOriginId(1)
            ->setTicket($this->createTicket($ticketId))
            ->setOriginCreatedAt(new \DateTime());

        $this->assertEquals($ticketComment, $this->processor->process($ticketComment));
        $this->assertFalse($this->registry->getManager()->contains($ticketComment));
    }

    public function testProcessExistingZendeskTicketComment()
    {
        $this->setExpectedContextOptions(['channel' => $this->channel->getId()]);

        $ticketId = 43;
        $ticketComment = $this->createZendeskTicketComment()
            ->setOriginId(1000)
            ->setBody('Updated body')
            ->setHtmlBody('Updated body html')
            ->setPublic(false)
            ->setTicket($this->createTicket($ticketId))
            ->setChannel($this->channel)
            ->setOriginCreatedAt(new \DateTime('2014-04-10T12:12:21Z'));

        $result = $this->processor->process($ticketComment);

        $this->assertInstanceOf(TicketComment::class, $result);

        $this->assertNotSame($ticketComment, $result);
        $this->assertNotNull($result->getId());
        $this->assertEquals($ticketComment->getOriginId(), $result->getOriginId());
        $this->assertEquals($ticketComment->getBody(), $result->getBody());
        $this->assertEquals($ticketComment->getHtmlBody(), $result->getHtmlBody());
        $this->assertEquals($ticketComment->getPublic(), $result->getPublic());
        $this->assertEquals($ticketComment->getOriginCreatedAt(), $result->getOriginCreatedAt());

        $this->assertFalse($this->registry->getManager()->contains($ticketComment));
        $this->assertTrue($this->registry->getManager()->contains($result));
    }

    public function testProcessLinksAuthor()
    {
        $this->setExpectedContextOptions(['channel' => $this->channel->getId()]);
        $user = $this->getReference('zendesk_user:james.cook@example.com');
        $ticketId = 43;
        $originId = $user->getOriginId();
        $ticketComment = $this->createZendeskTicketComment()
            ->setOriginId(1)
            ->setTicket($this->createTicket($ticketId))
            ->setAuthor($this->createZendeskUser()->setOriginId($originId))
            ->setOriginCreatedAt(new \DateTime());

        $this->assertSame($ticketComment, $this->processor->process($ticketComment));

        $this->assertInstanceOf(ZendeskUser::class, $ticketComment->getAuthor());
        $this->assertEquals($originId, $ticketComment->getAuthor()->getOriginId());
        $this->assertTrue($this->registry->getManager()->contains($ticketComment->getAuthor()));
    }

    public function testProcessCreatesNewCaseComment()
    {
        $this->setExpectedContextOptions(['channel' => $this->channel->getId()]);
        $ticketId = 43;
        $ticketComment = $this->createZendeskTicketComment()
            ->setOriginId(1)
            ->setBody('Comment body')
            ->setTicket($this->createTicket($ticketId))
            ->setOriginCreatedAt(new \DateTime('2014-04-10T12:12:21Z'))
            ->setPublic(true);

        $this->assertEquals($ticketComment, $this->processor->process($ticketComment));

        $comment = $ticketComment->getRelatedComment();
        $this->assertInstanceOf(CaseComment::class, $comment);
        $this->assertFalse($this->registry->getManager()->contains($comment));

        $this->assertEquals($ticketComment->getBody(), $comment->getMessage());
        $this->assertEquals($ticketComment->getOriginCreatedAt(), $comment->getCreatedAt());
        $this->assertEquals($ticketComment->getPublic(), $comment->isPublic());
    }

    public function testProcessSyncsCaseCommentOwner()
    {
        $this->setExpectedContextOptions(['channel' => $this->channel->getId()]);

        $expectedOwner = $this->getReference('user:james.cook@example.com');
        $agentUser = $this->getReference('zendesk_user:james.cook@example.com');

        $ticketId = 43;

        $ticketComment = $this->createZendeskTicketComment()
            ->setOriginId(1)
            ->setAuthor($agentUser)
            ->setTicket($this->createTicket($ticketId))
            ->setOriginCreatedAt(new \DateTime());

        $this->assertSame($ticketComment, $this->processor->process($ticketComment));

        $comment = $ticketComment->getRelatedComment();
        $this->assertInstanceOf(CaseComment::class, $comment);
        $this->assertInstanceOf(User::class, $comment->getOwner());
        $this->assertTrue($this->registry->getManager()->contains($comment->getOwner()));
        $this->assertEquals($expectedOwner->getId(), $comment->getOwner()->getId());
    }

    public function testProcessSyncsCaseCommentContact()
    {
        $this->setExpectedContextOptions(['channel' => $this->channel->getId()]);

        $expectedContact = $this->getReference('contact:jim.smith@example.com');
        $endUser = $this->getReference('zendesk_user:jim.smith@example.com');

        $ticketId = 43;

        $ticketComment = $this->createZendeskTicketComment()
            ->setOriginId(1)
            ->setAuthor($endUser)
            ->setTicket($this->createTicket($ticketId))
            ->setOriginCreatedAt(new \DateTime());

        $this->assertSame($ticketComment, $this->processor->process($ticketComment));

        $comment = $ticketComment->getRelatedComment();
        $this->assertInstanceOf(CaseComment::class, $comment);
        $this->assertInstanceOf(Contact::class, $comment->getContact());
        $this->assertTrue($this->registry->getManager()->contains($comment->getContact()));
        $this->assertEquals($expectedContact->getId(), $comment->getContact()->getId());
    }

    private function createZendeskTicketComment(): TicketComment
    {
        return new TicketComment();
    }

    private function createZendeskUser(): ZendeskUser
    {
        return new ZendeskUser();
    }

    private function createTicket(int $originId): Ticket
    {
        $ticket = new Ticket();
        $ticket->setOriginId($originId);

        return $ticket;
    }

    private function setExpectedContextOptions(array $options): void
    {
        $this->context->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function ($name) use ($options) {
                return $options[$name] ?? null;
            });
    }
}
