<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Processor\ImportTicketCommentProcessor;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class ImportTicketCommentProcessorTest extends WebTestCase
{
    /**
     * @var int
     */
    protected static $ticketId;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var ImportTicketCommentProcessor
     */
    protected $processor;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var Channel
     */
    protected $channel;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);

        $this->registry  = $this->getContainer()->get('doctrine');
        $this->processor = $this->getContainer()->get('orocrm_zendesk.importexport.processor.import_ticket_comment');
        $this->context   = $this->getMock('Oro\\Bundle\\ImportExportBundle\\Context\\ContextInterface');
        $this->channel   = $this->getReference('zendesk_channel:first_test_channel');
        $this->processor->setImportExportContext($this->context);
    }

    protected function postFixtureLoad()
    {
        self::$ticketId = $this->getReference('orocrm_zendesk:ticket_42')->getOriginId();
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Imported entity must be instance of OroCRM\Bundle\ZendeskBundle\Entity\TicketComment,
     * stdClass given.
     */
    public function testProcessFailsWithInvalidArgument()
    {
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

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment', $result);

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

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\User', $ticketComment->getAuthor());
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
        $this->assertInstanceOf('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseComment', $comment);
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
        $this->assertInstanceOf('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseComment', $comment);
        $this->assertInstanceOf('Oro\\Bundle\\UserBundle\\Entity\\User', $comment->getOwner());
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
        $this->assertInstanceOf('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseComment', $comment);
        $this->assertInstanceOf('OroCRM\\Bundle\\ContactBundle\\Entity\\Contact', $comment->getContact());
        $this->assertTrue($this->registry->getManager()->contains($comment->getContact()));
        $this->assertEquals($expectedContact->getId(), $comment->getContact()->getId());
    }

    protected function createZendeskTicketComment()
    {
        return new TicketComment();
    }

    protected function createZendeskUser()
    {
        return new ZendeskUser();
    }

    protected function createTicket($originId)
    {
        return (new Ticket())->setOriginId($originId);
    }

    /**
     * @param array $options
     */
    protected function setExpectedContextOptions(array $options)
    {
        $this->context->expects($this->any())
            ->method('getOption')
            ->will(
                $this->returnCallback(
                    function ($name) use ($options) {
                        return isset($options[$name]) ? $options[$name] : null;
                    }
                )
            );
    }
}
