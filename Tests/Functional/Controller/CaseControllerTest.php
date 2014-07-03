<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Controller;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use Symfony\Component\DomCrawler\Crawler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class CaseControllerTest extends WebTestCase
{
    /**
     * @var string
     */
    protected static $caseWithTicketSubject = 'Case #1';

    /**
     * @var string
     */
    protected static $caseWithoutTicketSubject = 'Case #3';

    /**
     * @var int
     */
    protected static $caseWithTicketId;

    /**
     * @var int
     */
    protected static $caseWithoutTicketId;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());

        $this->loadFixtures(array('OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData'));
    }

    protected function postFixtureLoad()
    {
        $repository = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OroCRMCaseBundle:CaseEntity');

        $case = $repository->findOneBySubject(static::$caseWithTicketSubject);
        $this->assertNotNull($case);

        static::$caseWithTicketId = $case->getId();

        $case = $repository->findOneBySubject(static::$caseWithoutTicketSubject);
        $this->assertNotNull($case);

        static::$caseWithoutTicketId = $case->getId();
    }

    public function testViewContainTicketInfoOnlyIfTicketLinked()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_case_view', array('id' => static::$caseWithoutTicketId))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(static::$caseWithoutTicketSubject." - Cases - Activities", $crawler->html());
        $this->assertNotContains("Zendesk ticket info", $crawler->html());
    }

    public function testViewHaveCorrectFields()
    {
        /** @var Ticket $expectedTicket */
        $expectedTicket = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OroCRMZendeskBundle:Ticket')->findOneByOriginId(42);
        $this->assertNotNull($expectedTicket);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_case_view', array('id' => static::$caseWithTicketId))
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(static::$caseWithTicketSubject." - Cases - Activities", $crawler->html());
        $this->assertContains("Zendesk ticket info", $crawler->html());

        $crawler = $crawler->filterXPath('//span[text()="Zendesk ticket info"]')->parents();

        $externalId = $this->getFieldValue("Ticket Number", $crawler);
        $this->assertContains($expectedTicket->getOriginId(), $externalId->html());

        $problem = $this->getFieldValue("Problem", $crawler);
        $this->assertContains($expectedTicket->getProblem()->getSubject(), $problem->html());

        $recipient = $this->getFieldValue("Recipient email", $crawler);
        $this->assertContains($expectedTicket->getRecipient(), $recipient->html());

        $collaborators = $this->getFieldValue("Collaborators", $crawler);
        $this->assertContains('Fred Taylor', $collaborators->html());
        $this->assertContains('Alex Taylor', $collaborators->html());

        $submitter = $this->getFieldValue("Submitter", $crawler);
        $this->assertContains('Fred Taylor', $submitter->html());

        $assignee = $this->getFieldValue("Assignee", $crawler);
        $this->assertContains('Fred Taylor', $assignee->html());

        $requester = $this->getFieldValue("Requester", $crawler);
        $this->assertContains('Alex Taylor', $requester->html());

        $status = $this->getFieldValue("Status", $crawler);
        $this->assertContains($expectedTicket->getStatus()->getLabel(), $status->html());

        $priority = $this->getFieldValue("Priority", $crawler);
        $this->assertContains($expectedTicket->getPriority()->getLabel(), $priority->html());

        $type = $this->getFieldValue("Type", $crawler);
        $this->assertContains($expectedTicket->getType()->getLabel(), $type->html());
    }

    public function testEditContainSyncControlIfNotSyncedYet()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_case_update', array('id' => static::$caseWithoutTicketId))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains("Sync with Zendesk", $crawler->html());
    }

    public function testEditDoesNotContainSyncControlIfAlreadySynced()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_case_update', array('id' => static::$caseWithTicketId))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertNotContains("Sync with Zendesk", $crawler->html());
    }

    protected function getFieldValue($label, Crawler $crawler)
    {
        $labelNode = $crawler->filterXPath("//label[text()=\"{$label}\"]");
        $this->assertTrue($labelNode->count() > 0, "label({$label}) not found");
        $value = $labelNode->parents()->first()->filterXPath('//div[@class="control-label"]');
        $this->assertTrue($value->count() > 0, "value({$label}) not found");
        return $value;
    }
}
