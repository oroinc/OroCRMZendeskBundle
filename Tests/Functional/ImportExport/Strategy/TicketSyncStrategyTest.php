<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Strategy;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\CaseBundle\Entity\CasePriority;
use OroCRM\Bundle\CaseBundle\Entity\CaseStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\TicketSyncStrategy;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use OroCRM\Bundle\ZendeskBundle\Model\SyncState;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class TicketSyncStrategyTest extends WebTestCase
{
    /**
     * @var TicketSyncStrategy
     */
    protected $strategy;

    /**
     * @var EntityManager
     */
    protected $entityManager;

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
        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData', ]);

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->strategy = $this->getContainer()->get('orocrm_zendesk.importexport.strategy.ticket_sync');
        $this->context = $this->getMock('Oro\\Bundle\\ImportExportBundle\\Context\\ContextInterface');
        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(array('channel', null, $this->channel->getId()))));
        $this->strategy->setImportExportContext($this->context);
    }

    public function tearDown()
    {
        $this->getSyncStateService()->setTicketIds(array());
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Imported entity must be instance of OroCRM\Bundle\ZendeskBundle\Entity\Ticket,
     * stdClass given.
     */
    public function testProcessInvalidArgumentFails()
    {
        $this->strategy->process(new \stdClass());
    }

    public function testProcessNewZendeskTicket()
    {
        $originId = 1;
        $zendeskTicket = $this->createZendeskTicket()->setOriginId($originId);
        $expected = array($originId);
        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));
        $actual = $this->getSyncStateService()->getTicketIds();
        $this->assertEquals($expected, $actual);
        $this->assertFalse($this->entityManager->contains($zendeskTicket));
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

        $result = $this->strategy->process($zendeskTicket);

        $this->assertNotSame($zendeskTicket, $result);
        $this->assertNotNull($result->getId());
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

        $this->assertFalse($this->entityManager->contains($zendeskTicket));
        $this->assertTrue($this->entityManager->contains($result));
    }

    public function testProcessSkipSyncExistingZendeskTicketIfItAlreadyUpdated()
    {
        $existingTicket = $this->getReference('zendesk_ticket_42');
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

        $result = $this->strategy->process($zendeskTicket);

        $this->assertNull($result);
    }

    public function testProcessLinksProblem()
    {
        $originId = 43;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setProblem($this->createZendeskTicket()->setOriginId($originId));

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket', $zendeskTicket->getProblem());
        $this->assertEquals($originId, $zendeskTicket->getProblem()->getOriginId());
        $this->assertTrue($this->entityManager->contains($zendeskTicket->getProblem()));
    }

    public function testProcessLinksType()
    {
        $name = TicketType::TYPE_INCIDENT;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setType(new TicketType($name));

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketType', $zendeskTicket->getType());
        $this->assertEquals($name, $zendeskTicket->getType()->getName());
        $this->assertTrue($this->entityManager->contains($zendeskTicket->getType()));
    }

    public function testProcessLinksStatus()
    {
        $name = TicketStatus::STATUS_SOLVED;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setStatus(new TicketStatus($name));

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketStatus', $zendeskTicket->getStatus());
        $this->assertEquals($name, $zendeskTicket->getStatus()->getName());
        $this->assertTrue($this->entityManager->contains($zendeskTicket->getStatus()));
    }

    public function testProcessLinksPriority()
    {
        $name = TicketPriority::PRIORITY_LOW;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setPriority(new TicketPriority($name));

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketPriority', $zendeskTicket->getPriority());
        $this->assertEquals($name, $zendeskTicket->getPriority()->getName());
        $this->assertTrue($this->entityManager->contains($zendeskTicket->getPriority()));
    }

    public function testProcessLinksCollaborators()
    {
        $originId = 1016;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->addCollaborator($this->createZendeskUser()->setOriginId(1016));

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $this->assertInstanceOf(
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
            $zendeskTicket->getCollaborators()->first()
        );
        $this->assertEquals($originId, $zendeskTicket->getCollaborators()->first()->getOriginId());
        $this->assertTrue($this->entityManager->contains($zendeskTicket->getCollaborators()->first()));
    }

    public function testProcessLinksRequester()
    {
        $originId = 1010;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setRequester($this->createZendeskUser()->setOriginId($originId));

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\User', $zendeskTicket->getRequester());
        $this->assertEquals($originId, $zendeskTicket->getRequester()->getOriginId());
        $this->assertTrue($this->entityManager->contains($zendeskTicket->getRequester()));
    }

    public function testProcessLinksSubmitter()
    {
        $originId = 1010;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setSubmitter($this->createZendeskUser()->setOriginId($originId));

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\User', $zendeskTicket->getSubmitter());
        $this->assertEquals($originId, $zendeskTicket->getSubmitter()->getOriginId());
        $this->assertTrue($this->entityManager->contains($zendeskTicket->getSubmitter()));
    }

    public function testProcessLinksAssignee()
    {
        $originId = 1010;
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setAssignee($this->createZendeskUser()->setOriginId($originId));

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\User', $zendeskTicket->getAssignee());
        $this->assertEquals($originId, $zendeskTicket->getAssignee()->getOriginId());
        $this->assertTrue($this->entityManager->contains($zendeskTicket->getAssignee()));
    }

    public function testProcessCreatesNewCase()
    {
        $zendeskTicket = $this->createZendeskTicket()
            ->setOriginId(1)
            ->setSubject('Updated subject')
            ->setDescription('Updated description');

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertInstanceOf('OroCRM\\Bundle\\CaseBundle\\Entity\\CaseEntity', $case);
        $this->assertFalse($this->entityManager->contains($case));

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

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

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

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

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

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertNotEmpty($zendeskTicket->$getter()->getRelatedUser());
        $this->assertNotEmpty($case->getOwner());
        $this->assertTrue($this->entityManager->contains($case->getOwner()));
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

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertNotEmpty($zendeskTicket->$getter()->getRelatedUser());
        $this->assertNotEmpty($case->getAssignedTo());
        $this->assertTrue($this->entityManager->contains($case->getAssignedTo()));
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

        $this->assertEquals($zendeskTicket, $this->strategy->process($zendeskTicket));

        $case = $zendeskTicket->getRelatedCase();
        $this->assertNotEmpty($zendeskTicket->$getter()->getRelatedContact());
        $this->assertNotEmpty($case->getRelatedContact());
        $this->assertTrue($this->entityManager->contains($case->getRelatedContact()));
        $this->assertSame(
            $zendeskTicket->$getter()->getRelatedContact(),
            $case->getRelatedContact()
        );
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
}
