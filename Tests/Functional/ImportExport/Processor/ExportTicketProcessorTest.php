<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\CaseBundle\Entity\CasePriority;
use OroCRM\Bundle\CaseBundle\Entity\CaseStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Processor\ExportTicketProcessor;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ExportTicketProcessorTest extends WebTestCase
{
    /**
     * @var ExportTicketProcessor
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

    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);
        $this->context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $this->processor = $this->getContainer()->get('orocrm_zendesk.importexport.processor.export_ticket');
        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->previousEmail = $this->channel->getTransport()->getZendeskUserEmail();
        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(array('channel', null, $this->channel->getId()))));
        $this->channel->getTransport()->setZendeskUserEmail('fred.taylor@example.com');
        $this->processor->setImportExportContext($this->context);
    }

    public function tearDown()
    {
        //see testProcessorReturnNullIfRequesterDoesNotFoundAndDefaultUserNotExist
        $this->channel->getTransport()->setZendeskUserEmail($this->previousEmail);

        parent::tearDown();
    }
    /**
     * @dataProvider processDataProvider
     */
    public function testProcess($data, $expected)
    {
        $data['status'] = new CaseStatus($data['status']);
        $data['priority'] = new CasePriority($data['priority']);

        $entity = $this->createTicket($data);

        $assignee = isset($expected['assignee']) ? $this->getReference($expected['assignee']) : null;
        $submitter = isset($expected['submitter']) ? $this->getReference($expected['submitter']) : null;
        $requester = $this->getReference($expected['requester']);

        /**
         * @var Ticket $actual
         */
        $actual = $this->processor->process($entity);

        $this->assertNotEmpty($actual->getRequester());
        $this->assertEquals($requester->getId(), $actual->getRequester()->getId());

        if ($submitter) {
            $this->assertNotEmpty($actual->getSubmitter());
            $this->assertEquals($submitter->getId(), $actual->getSubmitter()->getId());
        } else {
            $this->assertEmpty($actual->getSubmitter());
        }

        if ($assignee) {
            $this->assertNotEmpty($actual->getAssignee());
            $this->assertEquals($assignee->getId(), $actual->getAssignee()->getId());
        } else {
            $this->assertEmpty($actual->getAssignee());
        }

        $this->assertEquals($expected['status'], $actual->getStatus()->getName());
        $this->assertEquals($expected['priority'], $actual->getPriority()->getName());
        $this->assertEquals($expected['subject'], $actual->getSubject());
        $this->assertEquals($expected['description'], $actual->getDescription());
    }

    public function processDataProvider()
    {
        return array(
            'new ticket with exist assignee' => array(
                'data'     => array(
                    'status'      => CaseStatus::STATUS_OPEN,
                    'priority'    => CasePriority::PRIORITY_HIGH,
                    'contact'     => 'contact:jim.smith@example.com',
                    'assignedTo'  => 'user:james.cook@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => null
                ),
                'expected' => array(
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_NEW,
                    'priority'    => TicketPriority::PRIORITY_HIGH,
                    'assignee'    => 'zendesk_user:james.cook@example.com',
                    'requester'   => 'zendesk_user:jim.smith@example.com',
                    'submitter'   => 'zendesk_user:jim.smith@example.com'
                )
            ),
            'new ticket without assignee' => array(
                'data'     => array(
                    'status'      => CaseStatus::STATUS_OPEN,
                    'priority'    => CasePriority::PRIORITY_HIGH,
                    'owner'       => 'user:james.cook@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => null
                ),
                'expected' => array(
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_NEW,
                    'priority'    => TicketPriority::PRIORITY_HIGH,
                    'requester'   => 'zendesk_user:james.cook@example.com'
                )
            ),
            'new ticket with not exist assignee' => array(
                'data'     => array(
                    'status'      => CaseStatus::STATUS_OPEN,
                    'priority'    => CasePriority::PRIORITY_HIGH,
                    'contact'     => 'contact:jim.smith@example.com',
                    'assignedTo'  => 'user:john.smith@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => null
                ),
                'expected' => array(
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_NEW,
                    'priority'    => TicketPriority::PRIORITY_HIGH,
                    'assignee'    => 'zendesk_user:fred.taylor@example.com',
                    'requester'   => 'zendesk_user:jim.smith@example.com',
                    'submitter'   => 'zendesk_user:jim.smith@example.com'
                )
            ),
            'new ticket with not exist requester' => array(
                'data'     => array(
                    'status'      => CaseStatus::STATUS_IN_PROGRESS,
                    'priority'    => CasePriority::PRIORITY_NORMAL,
                    'owner'       => 'user:john.smith@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => null
                ),
                'expected' => array(
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_PENDING,
                    'priority'    => TicketPriority::PRIORITY_NORMAL,
                    'requester'   => 'zendesk_user:fred.taylor@example.com'
                )
            ),
            'Existing ticket with new related contact' => array(
                'data'     => array(
                    'status'      => CaseStatus::STATUS_RESOLVED,
                    'priority'    => CasePriority::PRIORITY_LOW,
                    'contact'     => 'contact:jim.smith@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => 1
                ),
                'expected' => array(
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_SOLVED,
                    'priority'    => TicketPriority::PRIORITY_LOW,
                    'requester'   => 'zendesk_user:jim.smith@example.com'
                )
            )
        );
    }

    public function testProcessorSetDefaultUserAsRequester()
    {
        /**
         * @var Ticket $ticket
         */
        $ticket = $this->getReference('orocrm_zendesk:ticket_43');
        $ticket->setRequester(null);
        $actual = $this->processor->process($ticket);
        $this->assertNotEmpty($actual);
        $this->assertEquals($actual->getRequester(), $this->getReference('zendesk_user:fred.taylor@example.com'));
    }

    public function testProcessorCreateNewZendeskUserIfContactNotExist()
    {
        /**
         * @var Ticket $ticket
         */
        $ticket = $this->getReference('orocrm_zendesk:ticket_43');
        $ticket->getRelatedCase()->setRelatedContact($this->getReference('contact:alex.johnson@example.com'));
        $actual = $this->processor->process($ticket);
        $this->assertNotNull($actual->getRequester());
        $requester = $actual->getRequester();
        $this->assertEmpty($requester->getId());
        $this->assertEmpty($requester->getOriginId());
        $this->assertEquals($requester->getChannel(), $this->channel);
        $this->assertEquals($requester->getEmail(), 'alex.johnson@example.com');
        $this->assertEquals($requester->getRole()->getName(), UserRole::ROLE_END_USER);
    }

    public function testProcessReturnNullIfCaseIsNull()
    {
        $ticket = new Ticket();
        $this->context->expects($this->once())
            ->method('addError')
            ->with('Ticket must have related Case.');
        $this->assertNull($this->processor->process($ticket));
    }

    /**
     * @param $data
     * @return Ticket
     */
    protected function createTicket($data)
    {
        $entity = new Ticket();
        $entity->setOriginId($data['originId']);
        $case = new CaseEntity();

        if (isset($data['contact'])) {
            $contact = $this->getReference($data['contact']);
            $case->setRelatedContact($contact);
        }
        if (isset($data['assignedTo'])) {
            $assignedTo = $this->getReference($data['assignedTo']);
            $case->setAssignedTo($assignedTo);
        }
        $case->setStatus($data['status']);
        $case->setPriority($data['priority']);

        if (isset($data['owner'])) {
            $owner = $this->getReference($data['owner']);
            $case->setOwner($owner);
        }

        $case->setSubject($data['subject']);
        $case->setDescription($data['description']);
        $entity->setRelatedCase($case);
        return $entity;
    }
}
