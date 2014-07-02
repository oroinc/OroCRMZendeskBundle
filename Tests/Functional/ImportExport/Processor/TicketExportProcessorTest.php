<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\CaseBundle\Entity\CasePriority;
use OroCRM\Bundle\CaseBundle\Entity\CaseStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Processor\TicketExportProcessor;
use OroCRM\Bundle\ZendeskBundle\Model\EntityMapper;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class TicketExportProcessorTest extends WebTestCase
{
    /**
     * @var TicketExportProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var EntityMapper
     */
    protected $entityMapper;

    /**
     * @var Channel
     */
    protected $channel;

    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);
        $this->context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $this->processor = $this->getContainer()->get('orocrm_zendesk.importexport.processor.ticket_export');
        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(array('channel', null, $this->channel->getId()))));
        $this->processor->setImportExportContext($this->context);
        $this->entityMapper = $this->getContainer()->get('orocrm_zendesk.entity_mapper');
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
        $status = $this->entityMapper->getTicketStatus($data['status'], $this->channel);
        $priority = $this->entityMapper->getTicketPriority($data['priority'], $this->channel);

        /**
         * @var Ticket $actual
         */
        $actual = $this->processor->process($entity);

        $this->assertEquals($requester, $actual->getRequester());
        $this->assertEquals($submitter, $actual->getSubmitter());
        $this->assertEquals($assignee, $actual->getAssignee());
        $this->assertEquals($status, $actual->getStatus());
        $this->assertEquals($priority, $actual->getPriority());
        $this->assertEquals($expected['subject'], $actual->getSubject());
        $this->assertEquals($expected['description'], $actual->getDescription());
    }

    public function testProcessorReturnNullIfRequesterDoesNotExist()
    {
        /**
         * @var Ticket $ticket
         */
        $ticket = $this->getReference('orocrm_zendesk:ticket_43');
        $ticket->setRequester(null);
        $actual = $this->processor->process($ticket);

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Ticket must have related Case
     */
    public function testProcessReturnExceptionIfCaseIsNull()
    {
        $ticket = new Ticket();
        $this->processor->process($ticket);
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
                    'requester'   => 'zendesk_user:jim.smith@example.com'
                )
            )
        );
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
