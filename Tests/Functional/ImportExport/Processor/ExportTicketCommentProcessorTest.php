<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\ImportExport\Processor\ExportTicketCommentProcessor;

class ExportTicketCommentProcessorTest extends WebTestCase
{
    /**
     * @var ExportTicketCommentProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $previousEmail;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures(['Oro\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);
        $this->context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $this->processor = $this->getContainer()
            ->get('oro_zendesk.importexport.processor.export_ticket_comment');

        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->previousEmail = $this->channel->getTransport()->getZendeskUserEmail();
        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(array('channel', null, $this->channel->getId()))));

        $this->processor->setImportExportContext($this->context);
    }

    protected function tearDown(): void
    {
        // @see testNewCommentWithoutAuthor
        $this->channel->getTransport()->setZendeskUserEmail($this->previousEmail);

        parent::tearDown();
    }

    public function testNewCommentWithoutAuthor()
    {
        $expectedMessage = 'Test body';

        $userWithoutZendeskUser = $this->getReference('user:john.smith@example.com');

        /**
         * @var ZendeskRestTransport $transport
         */
        $transport = $this->channel->getTransport();
        $transport->setZendeskUserEmail('john.smith@example.com');

        $ticketComment = $this->createTicketComment($expectedMessage, $userWithoutZendeskUser, false);
        $actual = $this->processor->process($ticketComment);

        $this->assertNull($actual);
    }

    public function testNewCommentWithDefaultAuthor()
    {
        $expectedMessage = 'Test body';
        $expectedAuthor = $this->getReference('zendesk_user:fred.taylor@example.com');

        $userWithoutZendeskUser = $this->getReference('user:john.smith@example.com');
        $ticketComment = $this->createTicketComment($expectedMessage, $userWithoutZendeskUser);
        $actual = $this->processor->process($ticketComment);

        $this->assertInstanceOf('Oro\Bundle\ZendeskBundle\Entity\TicketComment', $actual);
        $this->assertEquals($expectedMessage, $actual->getBody());
        $this->assertTrue($actual->getPublic());
        $this->assertEquals($expectedAuthor, $actual->getAuthor());
    }

    public function testNewCommentWithExistingAuthor()
    {
        $expectedMessage = 'Test body';
        $expectedAuthor = $this->getReference('zendesk_user:james.cook@example.com');

        $owner = $this->getReference('user:james.cook@example.com');
        $ticketComment = $this->createTicketComment($expectedMessage, $owner);
        $actual = $this->processor->process($ticketComment);

        $this->assertInstanceOf('Oro\Bundle\ZendeskBundle\Entity\TicketComment', $actual);
        $this->assertEquals($expectedMessage, $actual->getBody());
        $this->assertTrue($actual->getPublic());
        $this->assertEquals($expectedAuthor, $actual->getAuthor());
    }

    public function testNewCommentWithoutContactAuthor()
    {
        $expectedMessage = 'Test body';

        $userWithoutZendeskUser = $this->getReference('user:john.smith@example.com');
        $expectedAuthor = $this->getReference('zendesk_user:jim.smith@example.com');
        /**
         * @var ZendeskRestTransport $transport
         */
        $transport = $this->channel->getTransport();
        $transport->setZendeskUserEmail('john.smith@example.com');

        $ticketComment = $this->createTicketComment($expectedMessage, $userWithoutZendeskUser);
        $ticketComment->getRelatedComment()
            ->setContact($this->getReference('contact:jim.smith@example.com'));
        $actual = $this->processor->process($ticketComment);

        $this->assertInstanceOf('Oro\Bundle\ZendeskBundle\Entity\TicketComment', $actual);
        $this->assertEquals($expectedMessage, $actual->getBody());
        $this->assertTrue($actual->getPublic());
        $this->assertEquals($expectedAuthor, $actual->getAuthor());
    }

    public function testProcessReturnExceptionIfInvalidEntityType()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Imported entity must be instance of Oro\Bundle\ZendeskBundle\Entity\TicketComment, stdClass given.'
        );

        $ticketComment = new \StdClass();
        $this->processor->process($ticketComment);
    }

    public function testProcessReturnNullIfCaseIsNull()
    {
        $this->context->expects($this->once())->method('incrementErrorEntriesCount');
        $ticketComment = new TicketComment();
        $ticketComment->setTicket($this->createTicketWithStatus(TicketStatus::STATUS_OPEN));
        $this->assertNull($this->processor->process($ticketComment));
    }

    public function testProcessIfTickedIsClosed()
    {
        $this->context->expects($this->once())->method('incrementErrorEntriesCount');

        $owner = $this->getReference('user:james.cook@example.com');
        $ticketComment = $this->createTicketComment('test', $owner, false, TicketStatus::STATUS_CLOSED);
        $actual = $this->processor->process($ticketComment);

        $this->assertNull($actual);
    }

    /**
     * @param string $expectedMessage
     * @param User   $owner
     * @param bool   $isPublic
     * @return TicketComment
     */
    protected function createTicketComment($expectedMessage, User $owner, $isPublic = true, $tickedStatus = null)
    {
        if (null === $tickedStatus) {
            $tickedStatus = TicketStatus::STATUS_OPEN;
        }

        $ticket = $this->createTicketWithStatus($tickedStatus);

        $ticketComment = new TicketComment();
        $comment = new CaseComment();
        $comment->setPublic($isPublic);
        $comment->setMessage($expectedMessage);
        $comment->setOwner($owner);
        $ticketComment->setRelatedComment($comment);
        $ticketComment->setTicket($ticket);

        return $ticketComment;
    }

    /**
     * @param string $status
     * @return Ticket
     */
    protected function createTicketWithStatus($status)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $ticket = new Ticket();
        $ticket->setStatus($em->find('OroZendeskBundle:TicketStatus', $status));

        return $ticket;
    }
}
