<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use OroCRM\Bundle\CaseBundle\Entity\CasePriority;
use OroCRM\Bundle\CaseBundle\Entity\CaseStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Processor\ImportTicketProcessor;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use OroCRM\Bundle\ZendeskBundle\Model\SyncState;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ImportTicketProcessorTest extends WebTestCase
{
    /**
     * @var ImportTicketProcessor
     */
    protected $processor;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
        $this->initClient([], [], true);

        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData'], true);

        $this->registry  = $this->getContainer()->get('doctrine');
        $this->processor = $this->getContainer()->get('orocrm_zendesk.importexport.processor.import_ticket');
        $this->context   = $this->getMock('Oro\\Bundle\\ImportExportBundle\\Context\\ContextInterface');
        $this->channel   = $this->getReference('zendesk_channel:first_test_channel');
        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(array('channel', null, $this->channel->getId()))));
        $this->processor->setImportExportContext($this->context);
    }

    public function tearDown()
    {
        $this->getSyncStateService()->setTicketIds(array());

        parent::tearDown();
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Imported entity must be instance of OroCRM\Bundle\ZendeskBundle\Entity\Ticket,
     * stdClass given.
     */
    public function testProcessInvalidArgumentFails()
    {
        $this->processor->process(new \stdClass());
    }

    public function testProcessNewZendeskTicket()
    {
        $originId = 1;
        $zendeskTicket = $this->createZendeskTicket()->setOriginId($originId);
        $expected = array($originId);
        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));
        $actual = $this->getSyncStateService()->getTicketIds();
        $this->assertEquals($expected, $actual);
        $this->assertFalse($this->registry->getManager()->contains($zendeskTicket));
    }

    public function testProcessExistingZendeskTicket()
    {
        $originId = 42;
        $expected = array($originId);
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId($originId)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/42.json?1')
            ->setSubject('Updated subject')
            ->setDescription('Updated description')
            ->setExternalId('123456')
            ->setRecipient('user@example.com')
            ->setHasIncidents(false)
            ->setOriginCreatedAt(new \DateTime('2014-06-10T12:12:21Z'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-10T17:45:22Z'))
            ->setDueAt(new \DateTime('2014-06-11T15:26:11Z'))
            ->setChannel($this->channel);

        $result = $this->processor->process($zendeskTicket);

        $this->assertNotEmpty($result);
        $this->assertNotNull($result->getId());
        $this->assertNotEquals($zendeskTicket->getId(), $result->getId());
        $this->assertEquals($zendeskTicket->getOriginId(), $result->getOriginId());
        $this->assertEquals($zendeskTicket->getUrl(), $result->getUrl());
        $this->assertEquals($zendeskTicket->getSubject(), $result->getSubject());
        $this->assertEquals($zendeskTicket->getDescription(), $result->getDescription());
        $this->assertEquals($zendeskTicket->getExternalId(), $result->getExternalId());
        $this->assertEquals($zendeskTicket->getRecipient(), $result->getRecipient());
        $this->assertEquals($zendeskTicket->getHasIncidents(), $result->getHasIncidents());
        $this->assertEquals($zendeskTicket->getOriginCreatedAt(), $result->getOriginCreatedAt());
        $this->assertEquals($zendeskTicket->getOriginUpdatedAt(), $result->getOriginUpdatedAt());
        $this->assertEquals($zendeskTicket->getDueAt(), $result->getDueAt());

        $actual = $this->getSyncStateService()->getTicketIds();
        $this->assertEquals($expected, $actual);

        $this->assertFalse($this->registry->getManager()->contains($zendeskTicket));
        $this->assertTrue($this->registry->getManager()->contains($result));
    }

    public function testProcessSkipSyncExistingZendeskTicketIfItAlreadyUpdated()
    {
        $existingTicket = $this->getReference('orocrm_zendesk:ticket_42');
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(42)
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/42.json?1')
            ->setSubject('Updated subject')
            ->setDescription('Updated description')
            ->setExternalId('123456')
            ->setRecipient('user@example.com')
            ->setHasIncidents(false)
            ->setOriginCreatedAt(new \DateTime('2014-06-10T12:12:21Z'))
            ->setOriginUpdatedAt($existingTicket->getOriginUpdatedAt())
            ->setDueAt(new \DateTime('2014-06-11T15:26:11Z'));

        $result = $this->processor->process($zendeskTicket);

        $this->assertEmpty($result);
    }

    public function testProcessLinksProblem()
    {
        $originId = 43;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setProblem($this->createZendeskTicket()->setOriginId($originId));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket', $zendeskTicket->getProblem());
        $this->assertEquals($originId, $zendeskTicket->getProblem()->getOriginId());
        $this->assertTrue($this->registry->getManager()->contains($zendeskTicket->getProblem()));
    }

    public function testProcessLinksType()
    {
        $name = TicketType::TYPE_INCIDENT;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setType(new TicketType($name));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketType', $zendeskTicket->getType());
        $this->assertEquals($name, $zendeskTicket->getType()->getName());
        $this->assertTrue($this->registry->getManager()->contains($zendeskTicket->getType()));
    }

    public function testProcessLinksStatus()
    {
        $name = TicketStatus::STATUS_SOLVED;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setStatus(new TicketStatus($name));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketStatus', $zendeskTicket->getStatus());
        $this->assertEquals($name, $zendeskTicket->getStatus()->getName());
        $this->assertTrue($this->registry->getManager()->contains($zendeskTicket->getStatus()));
    }

    public function testProcessLinksPriority()
    {
        $name = TicketPriority::PRIORITY_LOW;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setPriority(new TicketPriority($name));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketPriority', $zendeskTicket->getPriority());
        $this->assertEquals($name, $zendeskTicket->getPriority()->getName());
        $this->assertTrue($this->registry->getManager()->contains($zendeskTicket->getPriority()));
    }

    public function testProcessLinksCollaborators()
    {
        $originId = 1016;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->addCollaborator($this->createZendeskUser()->setOriginId(1016));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $this->assertInstanceOf(
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
            $zendeskTicket->getCollaborators()->first()
        );
        $this->assertEquals($originId, $zendeskTicket->getCollaborators()->first()->getOriginId());
        $this->assertTrue($this->registry->getManager()->contains($zendeskTicket->getCollaborators()->first()));
    }

    public function testProcessLinksRequester()
    {
        $originId = 1010;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setRequester($this->createZendeskUser()->setOriginId($originId));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\User', $zendeskTicket->getRequester());
        $this->assertEquals($originId, $zendeskTicket->getRequester()->getOriginId());
        $this->assertTrue($this->registry->getManager()->contains($zendeskTicket->getRequester()));
    }

    public function testProcessLinksSubmitter()
    {
        $originId = 1010;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setSubmitter($this->createZendeskUser()->setOriginId($originId));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\User', $zendeskTicket->getSubmitter());
        $this->assertEquals($originId, $zendeskTicket->getSubmitter()->getOriginId());
        $this->assertTrue($this->registry->getManager()->contains($zendeskTicket->getSubmitter()));
    }

    public function testProcessLinksAssignee()
    {
        $originId = 1010;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setAssignee($this->createZendeskUser()->setOriginId($originId));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\User', $zendeskTicket->getAssignee());
        $this->assertEquals($originId, $zendeskTicket->getAssignee()->getOriginId());
        $this->assertTrue($this->registry->getManager()->contains($zendeskTicket->getAssignee()));
    }

    public function testProcessCreatesNewCase()
    {
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setSubject('Updated subject')
            ->setDescription('Updated description');

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertInstanceOf('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseEntity', $case);
        $this->assertFalse($this->registry->getManager()->contains($case));

        $this->assertEquals($zendeskTicket->getSubject(), $case->getSubject());
        $this->assertEquals($zendeskTicket->getDescription(), $case->getDescription());
    }

    /**
     * @dataProvider statusMappingDataProvider
     */
    public function testProcessMapsCaseStatus($ticketStatus, $caseStatus)
    {
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setStatus(new TicketStatus($ticketStatus));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertEquals($caseStatus, $case->getStatus()->getName());
    }

    public function statusMappingDataProvider()
    {
        return array(
            array(
                TicketStatus::STATUS_NEW,
                CaseStatus::STATUS_OPEN,
            ),
            array(
                TicketStatus::STATUS_OPEN,
                CaseStatus::STATUS_OPEN,
            ),
            array(
                TicketStatus::STATUS_PENDING,
                CaseStatus::STATUS_IN_PROGRESS,
            ),
            array(
                TicketStatus::STATUS_HOLD,
                CaseStatus::STATUS_OPEN,
            ),
            array(
                TicketStatus::STATUS_SOLVED,
                CaseStatus::STATUS_RESOLVED,
            ),
            array(
                TicketStatus::STATUS_CLOSED,
                CaseStatus::STATUS_CLOSED,
            ),
        );
    }

    /**
     * @dataProvider priorityMappingDataProvider
     */
    public function testProcessMapsCasePriority($ticketPriority, $casePriority)
    {
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setPriority(new TicketPriority($ticketPriority));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertEquals($casePriority, $case->getPriority()->getName());
    }

    public function priorityMappingDataProvider()
    {
        return array(
            array(
                TicketPriority::PRIORITY_LOW,
                CasePriority::PRIORITY_LOW,
            ),
            array(
                TicketPriority::PRIORITY_NORMAL,
                CasePriority::PRIORITY_NORMAL,
            ),
            array(
                TicketPriority::PRIORITY_HIGH,
                CasePriority::PRIORITY_HIGH,
            ),
            array(
                TicketPriority::PRIORITY_URGENT,
                CasePriority::PRIORITY_HIGH,
            ),
        );
    }

    /**
     * @dataProvider caseOwnerTicketFieldsDataProvider
     */
    public function testProcessSyncsCaseOwner($fieldName)
    {
        $setter = 'set' . ucfirst($fieldName);
        $getter = 'get' . ucfirst($fieldName);
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->$setter($this->createZendeskUser()->setOriginId(1016));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertNotEmpty($zendeskTicket->$getter()->getRelatedUser());
        $this->assertNotEmpty($case->getOwner());
        $this->assertTrue($this->registry->getManager()->contains($case->getOwner()));
        $this->assertSame(
            $zendeskTicket->$getter()->getRelatedUser(),
            $case->getOwner()
        );
    }

    public function caseOwnerTicketFieldsDataProvider()
    {
        return array(
            'submitter' => array('submitter'),
            'requester' => array('requester'),
            'assignee' => array('assignee'),
        );
    }

    /**
     * @dataProvider caseAssignedToTicketFieldsDataProvider
     */
    public function testProcessSyncsCaseAssignedTo($fieldName)
    {
        $setter = 'set' . ucfirst($fieldName);
        $getter = 'get' . ucfirst($fieldName);
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->$setter($this->createZendeskUser()->setOriginId(1016));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertNotEmpty($zendeskTicket->$getter()->getRelatedUser());
        $this->assertNotEmpty($case->getAssignedTo());
        $this->assertTrue($this->registry->getManager()->contains($case->getAssignedTo()));
        $this->assertSame(
            $zendeskTicket->$getter()->getRelatedUser(),
            $case->getAssignedTo()
        );
    }

    public function caseAssignedToTicketFieldsDataProvider()
    {
        return array(
            'submitter' => array('submitter'),
            'requester' => array('requester'),
            'assignee' => array('assignee'),
        );
    }

    /**
     * @dataProvider caseRelatedContactTicketFieldsDataProvider
     */
    public function testProcessSyncsCaseRelatedContact($fieldName)
    {
        $setter = 'set' . ucfirst($fieldName);
        $getter = 'get' . ucfirst($fieldName);
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->$setter($this->createZendeskUser()->setOriginId(1010));

        $this->assertEquals($zendeskTicket, $this->processor->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertNotEmpty($zendeskTicket->$getter()->getRelatedContact());
        $this->assertNotEmpty($case->getRelatedContact());
        $this->assertTrue($this->registry->getManager()->contains($case->getRelatedContact()));
        $this->assertSame(
            $zendeskTicket->$getter()->getRelatedContact(),
            $case->getRelatedContact()
        );
    }

    public function testProcessLocalWinsAppliesZendeskTicketRemoteChanges()
    {
        $zendeskTicket = $this->getReference('orocrm_zendesk:ticket_44_case_6');

        $relatedCase = $this->getReference('orocrm_zendesk:case_6');
        $relatedCase->setSubject('Local subject');
        $relatedCase->setDescription('Local description');
        $relatedCase->setRelatedContact($this->getReference('contact:alex.miller@example.com'));
        $relatedCase->setAssignedTo($this->getReference('user:bob.miller@example.com'));
        $relatedCase->setOwner($this->getReference('user:bob.miller@example.com'));
        $relatedCase->setStatus($this->getCaseStatus(CaseStatus::STATUS_IN_PROGRESS));
        $relatedCase->setPriority($this->getCasePriority(CasePriority::PRIORITY_HIGH));

        $this->channel->getSynchronizationSettingsReference()
            ->offsetSet('isTwoWaySyncEnabled', true)
            ->offsetSet('syncPriority', TwoWaySyncConnectorInterface::LOCAL_WINS);

        $processZendeskTicket = $this->createZendeskTicket()
            ->setOriginId($expectedOriginId = $zendeskTicket->getOriginId())
            ->setUrl($expectedUrl = 'https://foo.zendesk.com/api/v2/tickets/42.json?1')
            ->setSubject($expectedSubject = 'Updated subject')
            ->setDescription($expectedDescription = 'Updated description')
            ->setExternalId($expectedExternalId = '1234567')
            ->setRecipient($expectedRecipient = 'updated-user@example.com')
            ->setHasIncidents($expectedHasIncidents = false)
            ->setOriginCreatedAt($expectedOriginCreatedAt = new \DateTime('2014-06-10T12:12:21Z'))
            ->setOriginUpdatedAt($expectedOriginUpdatedAt = new \DateTime('2014-06-10T17:45:23Z'))
            ->setDueAt($expectedDueAt = new \DateTime('2014-06-11T15:26:11Z'))
            ->setRequester(
                $this->createZendeskUser()->setOriginId(
                    $expectedRequesterId = $this->getReference('zendesk_user:fred.taylor@example.com')->getOriginId()
                )
            )
            ->setAssignee(
                $this->createZendeskUser()->setOriginId(
                    $expectedAssigneeId = $this->getReference('zendesk_user:fred.taylor@example.com')->getOriginId()
                )
            )
            ->setSubmitter(
                $this->createZendeskUser()->setOriginId(
                    $expectedSubmitterId = $this->getReference('zendesk_user:fred.taylor@example.com')->getOriginId()
                )
            )
            ->setStatus(new TicketStatus($expectedStatus = TicketStatus::STATUS_CLOSED))
            ->setPriority(new TicketPriority($expectedPriority = TicketPriority::PRIORITY_NORMAL))
            ->setChannel($this->channel);

        $result = $this->processor->process($processZendeskTicket);

        $this->assertNotEmpty($result);
        $this->assertEquals($result->getId(), $zendeskTicket->getId());

        $this->assertEquals($expectedOriginId, $processZendeskTicket->getOriginId());
        $this->assertEquals($expectedUrl, $processZendeskTicket->getUrl());
        $this->assertEquals($expectedSubject, $processZendeskTicket->getSubject());
        $this->assertEquals($expectedDescription, $processZendeskTicket->getDescription());
        $this->assertEquals($expectedExternalId, $processZendeskTicket->getExternalId());
        $this->assertEquals($expectedRecipient, $processZendeskTicket->getRecipient());
        $this->assertEquals($expectedHasIncidents, $processZendeskTicket->getHasIncidents());
        $this->assertEquals($expectedOriginCreatedAt, $processZendeskTicket->getOriginCreatedAt());
        $this->assertEquals($expectedOriginUpdatedAt, $processZendeskTicket->getOriginUpdatedAt());
        $this->assertEquals($expectedDueAt, $processZendeskTicket->getDueAt());
        $this->assertEquals($expectedRequesterId, $processZendeskTicket->getRequester()->getOriginId());
        $this->assertEquals($expectedAssigneeId, $processZendeskTicket->getAssignee()->getOriginId());
        $this->assertEquals($expectedSubmitterId, $processZendeskTicket->getSubmitter()->getOriginId());
        $this->assertEquals($expectedStatus, $processZendeskTicket->getStatus()->getName());
        $this->assertEquals($expectedPriority, $processZendeskTicket->getPriority()->getName());

        $this->assertTrue($this->registry->getManager()->contains($result));
    }

    public function testProcessLocalWinsIgnoresRelatedCaseRemoteConflictChanges()
    {
        $zendeskTicket = $this->getReference('orocrm_zendesk:ticket_44_case_6');
        $relatedCase = $this->getReference('orocrm_zendesk:case_6');
        $relatedCase->setSubject($expectedSubject = 'Local subject');
        $relatedCase->setDescription($expectedDescription = 'Local description');
        $relatedCase->setRelatedContact($expectedContact = $this->getReference('contact:alex.miller@example.com'));
        $relatedCase->setAssignedTo($expectedAssignedTo = $this->getReference('user:bob.miller@example.com'));
        $relatedCase->setOwner($expectedOwner = $this->getReference('user:bob.miller@example.com'));
        $relatedCase->setStatus($expectedStatus = $this->getCaseStatus(CaseStatus::STATUS_IN_PROGRESS));
        $relatedCase->setPriority($expectedPriority = $this->getCasePriority(CasePriority::PRIORITY_HIGH));

        $this->channel->getSynchronizationSettingsReference()
            ->offsetSet('isTwoWaySyncEnabled', true)
            ->offsetSet('syncPriority', TwoWaySyncConnectorInterface::LOCAL_WINS);

        $processZendeskTicket = $this->createZendeskTicket()
            ->setOriginId($zendeskTicket->getOriginId())
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/42.json?1')
            ->setSubject('Updated subject')
            ->setDescription('Updated description')
            ->setExternalId('123456')
            ->setRecipient('user@example.com')
            ->setHasIncidents(false)
            ->setOriginCreatedAt(new \DateTime('2014-06-10T12:12:21Z'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-10T17:45:23Z'))
            ->setDueAt(new \DateTime('2014-06-11T15:26:11Z'))
            ->setRequester(
                $this->createZendeskUser()->setOriginId(
                    $this->getReference('zendesk_user:fred.taylor@example.com')->getOriginId()
                )
            )
            ->setAssignee(
                $this->createZendeskUser()->setOriginId(
                    $this->getReference('zendesk_user:fred.taylor@example.com')->getOriginId()
                )
            )
            ->setSubmitter(
                $this->createZendeskUser()->setOriginId(
                    $this->getReference('zendesk_user:fred.taylor@example.com')->getOriginId()
                )
            )
            ->setStatus(new TicketStatus(TicketStatus::STATUS_CLOSED))
            ->setPriority(new TicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setChannel($this->channel);

        $result = $this->processor->process($processZendeskTicket);

        $this->assertNotEmpty($result);
        $this->assertEquals($result->getId(), $zendeskTicket->getId());

        $this->assertTrue($this->registry->getManager()->contains($result));

        $this->assertEquals($expectedSubject, $relatedCase->getSubject());
        $this->assertEquals($expectedDescription, $relatedCase->getDescription());

        $this->assertNotEmpty($relatedCase->getRelatedContact());
        $this->assertEquals($expectedContact->getId(), $relatedCase->getRelatedContact()->getId());

        $this->assertNotEmpty($relatedCase->getAssignedTo());
        $this->assertEquals($expectedAssignedTo->getId(), $relatedCase->getAssignedTo()->getId());

        $this->assertNotEmpty($relatedCase->getOwner());
        $this->assertEquals($expectedAssignedTo->getId(), $relatedCase->getOwner()->getId());

        $this->assertNotEmpty($relatedCase->getStatus());
        $this->assertEquals($expectedStatus->getName(), $relatedCase->getStatus()->getName());

        $this->assertNotEmpty($relatedCase->getPriority());
        $this->assertEquals($expectedPriority->getName(), $relatedCase->getPriority()->getName());
    }

    public function testProcessLocalWinsAppliesRelatedCaseRemoteChanges()
    {
        $zendeskTicket = $this->getReference('orocrm_zendesk:ticket_44_case_6');
        $relatedCase = $this->getReference('orocrm_zendesk:case_6');

        $this->channel->getSynchronizationSettingsReference()
            ->offsetSet('isTwoWaySyncEnabled', true)
            ->offsetSet('syncPriority', TwoWaySyncConnectorInterface::LOCAL_WINS);

        $expectedContactId = $this->getReference('contact:jim.smith@example.com')->getId();
        $expectedAssignedToId = $this->getReference('user:anna.lee@example.com')->getId();
        $expectedOwnerId = $this->getReference('user:anna.lee@example.com')->getId();
        $expectedStatusName = CaseStatus::STATUS_CLOSED;
        $expectedPriorirtyName = CasePriority::PRIORITY_NORMAL;

        $processZendeskTicket = $this->createZendeskTicket()
            ->setOriginId($zendeskTicket->getOriginId())
            ->setUrl('https://foo.zendesk.com/api/v2/tickets/42.json?1')
            ->setSubject($expectedSubject = 'Updated subject')
            ->setDescription($expectedDescription = 'Updated description')
            ->setExternalId('123456')
            ->setRecipient('user@example.com')
            ->setHasIncidents(false)
            ->setOriginCreatedAt(new \DateTime('2014-06-10T12:12:21Z'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-10T17:45:23Z'))
            ->setDueAt(new \DateTime('2014-06-11T15:26:11Z'))
            ->setRequester(
                $this->createZendeskUser()->setOriginId(
                    $this->getReference('zendesk_user:jim.smith@example.com')->getOriginId()
                )
            )
            ->setAssignee(
                $this->createZendeskUser()->setOriginId(
                    $this->getReference('zendesk_user:anna.lee@example.com')->getOriginId()
                )
            )
            ->setSubmitter(
                $this->createZendeskUser()->setOriginId(
                    $this->getReference('zendesk_user:anna.lee@example.com')->getOriginId()
                )
            )
            ->setStatus(new TicketStatus(TicketStatus::STATUS_CLOSED))
            ->setPriority(new TicketPriority(TicketPriority::PRIORITY_NORMAL))
            ->setChannel($this->channel);

        $result = $this->processor->process($processZendeskTicket);

        $this->assertNotEmpty($result);
        $this->assertEquals($result->getId(), $zendeskTicket->getId());

        $this->assertTrue($this->registry->getManager()->contains($result));

        $this->assertEquals($expectedSubject, $relatedCase->getSubject());
        $this->assertEquals($expectedDescription, $relatedCase->getDescription());

        $this->assertNotEmpty($relatedCase->getRelatedContact());
        $this->assertEquals($expectedContactId, $relatedCase->getRelatedContact()->getId());

        $this->assertNotEmpty($relatedCase->getAssignedTo());
        $this->assertEquals($expectedAssignedToId, $relatedCase->getAssignedTo()->getId());

        $this->assertNotEmpty($relatedCase->getOwner());
        $this->assertEquals($expectedOwnerId, $relatedCase->getOwner()->getId());

        $this->assertNotEmpty($relatedCase->getStatus());
        $this->assertEquals($expectedStatusName, $relatedCase->getStatus()->getName());

        $this->assertNotEmpty($relatedCase->getPriority());
        $this->assertEquals($expectedPriorirtyName, $relatedCase->getPriority()->getName());
    }

    public function caseRelatedContactTicketFieldsDataProvider()
    {
        return array(
            'submitter' => array('submitter'),
            'requester' => array('requester'),
            'assignee' => array('assignee'),
        );
    }

    protected function createZendeskTicket()
    {
        return new Ticket();
    }

    protected function createZendeskUser()
    {
        return new ZendeskUser();
    }

    /**
     * @return SyncState
     */
    protected function getSyncStateService()
    {
        return $this->getContainer()->get('orocrm_zendesk.sync_state');
    }

    /**
     * @param string $name
     * @return CaseStatus|null
     */
    protected function getCaseStatus($name)
    {
        return $this->registry
            ->getRepository('OroCRMCaseBundle:CaseStatus')
            ->find($name);
    }

    /**
     * @param string $name
     * @return CasePriority|null
     */
    protected function getCasePriority($name)
    {
        return $this->registry
            ->getRepository('OroCRMCaseBundle:CasePriority')
            ->find($name);
    }
}
