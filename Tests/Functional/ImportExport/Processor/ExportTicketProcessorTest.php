<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\CaseBundle\Entity\CasePriority;
use Oro\Bundle\CaseBundle\Entity\CaseStatus;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;
use Oro\Bundle\ZendeskBundle\ImportExport\Processor\ExportTicketProcessor;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketData;

class ExportTicketProcessorTest extends WebTestCase
{
    /** @var ExportTicketProcessor */
    private $processor;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var Channel */
    private $channel;

    /** @var string */
    private $previousEmail;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadTicketData::class]);
        $this->context = $this->createMock(ContextInterface::class);

        $this->processor = $this->getContainer()->get('oro_zendesk.importexport.processor.export_ticket');
        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->previousEmail = $this->channel->getTransport()->getZendeskUserEmail();
        $this->context->expects($this->any())
            ->method('getOption')
            ->willReturnMap([['channel', null, $this->channel->getId()]]);
        $this->channel->getTransport()->setZendeskUserEmail('fred.taylor@example.com');
        $this->processor->setImportExportContext($this->context);
    }

    protected function tearDown(): void
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

    public function processDataProvider(): array
    {
        return [
            'new ticket with exist assignee' => [
                'data'     => [
                    'status'      => CaseStatus::STATUS_OPEN,
                    'priority'    => CasePriority::PRIORITY_HIGH,
                    'contact'     => 'contact:jim.smith@example.com',
                    'assignedTo'  => 'user:james.cook@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => null
                ],
                'expected' => [
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_NEW,
                    'priority'    => TicketPriority::PRIORITY_HIGH,
                    'assignee'    => 'zendesk_user:james.cook@example.com',
                    'requester'   => 'zendesk_user:jim.smith@example.com',
                    'submitter'   => 'zendesk_user:jim.smith@example.com'
                ]
            ],
            'new ticket without assignee' => [
                'data'     => [
                    'status'      => CaseStatus::STATUS_OPEN,
                    'priority'    => CasePriority::PRIORITY_HIGH,
                    'owner'       => 'user:james.cook@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => null
                ],
                'expected' => [
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_NEW,
                    'priority'    => TicketPriority::PRIORITY_HIGH,
                    'requester'   => 'zendesk_user:james.cook@example.com'
                ]
            ],
            'new ticket with not exist assignee' => [
                'data'     => [
                    'status'      => CaseStatus::STATUS_OPEN,
                    'priority'    => CasePriority::PRIORITY_HIGH,
                    'contact'     => 'contact:jim.smith@example.com',
                    'assignedTo'  => 'user:john.smith@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => null
                ],
                'expected' => [
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_NEW,
                    'priority'    => TicketPriority::PRIORITY_HIGH,
                    'assignee'    => 'zendesk_user:fred.taylor@example.com',
                    'requester'   => 'zendesk_user:jim.smith@example.com',
                    'submitter'   => 'zendesk_user:jim.smith@example.com'
                ]
            ],
            'new ticket with not exist requester' => [
                'data'     => [
                    'status'      => CaseStatus::STATUS_IN_PROGRESS,
                    'priority'    => CasePriority::PRIORITY_NORMAL,
                    'owner'       => 'user:john.smith@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => null
                ],
                'expected' => [
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_PENDING,
                    'priority'    => TicketPriority::PRIORITY_NORMAL,
                    'requester'   => 'zendesk_user:fred.taylor@example.com'
                ]
            ],
            'Existing ticket with new related contact' => [
                'data'     => [
                    'status'      => CaseStatus::STATUS_RESOLVED,
                    'priority'    => CasePriority::PRIORITY_LOW,
                    'contact'     => 'contact:jim.smith@example.com',
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'originId'    => 1
                ],
                'expected' => [
                    'subject'     => 'test subject',
                    'description' => 'test description',
                    'status'      => TicketStatus::STATUS_SOLVED,
                    'priority'    => TicketPriority::PRIORITY_LOW,
                    'requester'   => 'zendesk_user:jim.smith@example.com'
                ]
            ]
        ];
    }

    public function testProcessorSetDefaultUserAsRequester()
    {
        /**
         * @var Ticket $ticket
         */
        $ticket = $this->getReference('oro_zendesk:ticket_43');
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
        $ticket = $this->getReference('oro_zendesk:ticket_43');
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

    private function createTicket(array $data): Ticket
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
