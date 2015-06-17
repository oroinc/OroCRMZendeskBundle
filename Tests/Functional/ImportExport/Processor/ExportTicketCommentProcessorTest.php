<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Processor\ExportTicketCommentProcessor;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ExportTicketCommentProcessorTest extends WebTestCase
{
    /**
     * @var ExportTicketCommentProcessor
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

    /**
     * @var string
     */
    protected $previousEmail;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);
        $this->context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');


        $this->processor = $this->getContainer()
            ->get('orocrm_zendesk.importexport.processor.export_ticket_comment');

        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->previousEmail = $this->channel->getTransport()->getZendeskUserEmail();
        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(array('channel', null, $this->channel->getId()))));

        $this->processor->setImportExportContext($this->context);
    }

    public function tearDown()
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

        $this->assertInstanceOf('OroCRM\Bundle\ZendeskBundle\Entity\TicketComment', $actual);
        $this->assertEquals($expectedMessage, $actual->getBody());
        $this->assertTrue($actual->getPublic());
        $this->assertEquals($expectedAuthor, $actual->getAuthor());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Imported entity must be instance of OroCRM\Bundle\ZendeskBundle\Entity\TicketComment, stdClass given.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessReturnExceptionIfInvalidEntityType()
    {
        $ticketComment = new \StdClass();
        $this->processor->process($ticketComment);
    }

    public function testProcessReturnNullIfCaseIsNull()
    {
        $ticketComment = new TicketComment();
        $this->assertNull($this->processor->process($ticketComment));
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
