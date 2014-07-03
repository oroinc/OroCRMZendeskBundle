<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Processor\TicketCommentExportProcessor;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class TicketCommentExportProcessorTest extends WebTestCase
{
    /**
     * @var TicketCommentExportProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var Channel
     */
    protected $channel;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);
        $this->context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');


        $this->processor = $this->getContainer()
            ->get('orocrm_zendesk.importexport.processor.ticket_comment_export');

        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(array('channel', null, $this->channel->getId()))));
        $this->channel->getTransport()->setZendeskUserEmail('fred.taylor@example.com');
        $this->processor->setImportExportContext($this->context);
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

        $this->assertInstanceOf('OroCRM\Bundle\ZendeskBundle\Entity\TicketComment', $actual);
        $this->assertEquals($expectedMessage, $actual->getBody());
        $this->assertFalse($actual->getPublic());
        $this->assertNull($actual->getAuthor());
    }

    public function testNewCommentWithDefaultAuthor()
    {
        $expectedMessage = 'Test body';
        $expectedAuthor = $this->getReference('zendesk_user:fred.taylor@example.com');

        $userWithoutZendeskUser = $this->getReference('user:john.smith@example.com');
        $ticketComment = $this->createTicketComment($expectedMessage, $userWithoutZendeskUser);
        $actual = $this->processor->process($ticketComment);

        $this->assertInstanceOf('OroCRM\Bundle\ZendeskBundle\Entity\TicketComment', $actual);
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

        $this->assertInstanceOf('OroCRM\Bundle\ZendeskBundle\Entity\TicketComment', $actual);
        $this->assertEquals($expectedMessage, $actual->getBody());
        $this->assertTrue($actual->getPublic());
        $this->assertEquals($expectedAuthor, $actual->getAuthor());
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Imported entity must be instance of
     * OroCRM\Bundle\ZendeskBundle\Entity\TicketComment, stdClass given.
     */
    public function testProcessReturnExceptionIfInvalidEntityType()
    {
        $ticketComment = new \StdClass();
        $this->processor->process($ticketComment);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Ticket Comment must have related Comment
     */
    public function testProcessReturnExceptionIfCaseIsNull()
    {
        $ticketComment = new TicketComment();
        $this->processor->process($ticketComment);
    }

    /**
     * @param string $expectedMessage
     * @param User   $owner
     * @param bool   $isPublic
     * @return TicketComment
     */
    protected function createTicketComment($expectedMessage, User $owner, $isPublic = true)
    {
        $ticketComment = new TicketComment();
        $comment = new CaseComment();
        $comment->setPublic($isPublic);
        $comment->setMessage($expectedMessage);
        $comment->setOwner($owner);
        $ticketComment->setRelatedComment($comment);

        return $ticketComment;
    }
}
