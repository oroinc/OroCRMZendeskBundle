<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class CaseControllerTest extends WebTestCase
{
    /**
     * @var int
     */
    protected static $caseWithTicketId;

    /**
     * @var int
     */
    protected static $caseWithoutTicketId;

    /**
     * @var CaseEntity
     */
    protected $caseWithoutTicket;

    /**
     * @var CaseEntity
     */
    protected $caseWithTicket;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(array('OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData'));
        $this->caseWithTicket = $this->getReference('orocrm_zendesk:case_1');
        $this->caseWithoutTicket = $this->getReference('orocrm_zendesk:case_3');
    }

    public function testViewWithoutLinkedTicket()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_case_view', array('id' => $this->caseWithoutTicket->getId()))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($this->caseWithoutTicket->getSubject() . " - Cases - Activities", $crawler->html());
        $this->assertNotContains("Zendesk ticket info", $crawler->html());
        $this->assertCount(1, $crawler->filter('.zendesk-integration-btn-group'));
        $this->assertContains("Publish to Zendesk", $crawler->html());
    }

    public function testViewWithLinkedTicket()
    {
        /** @var Ticket $expectedTicket */
        $expectedTicket = $this->getContainer()->get('doctrine')
            ->getRepository('OroCRMZendeskBundle:Ticket')->findOneByOriginId(42);
        $this->assertNotNull($expectedTicket);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_case_view', array('id' => $this->caseWithTicket->getId()))
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains($this->caseWithTicket->getSubject() . " - Cases - Activities", $crawler->html());
        $this->assertContains("Zendesk ticket info", $crawler->html());
        $this->assertCount(0, $crawler->filter('.zendesk-integration-btn-group'));
        $this->assertNotContains("Publish to Zendesk", $crawler->html());

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
            $this->getUrl('orocrm_case_update', array('id' => $this->caseWithoutTicket->getId()))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains("Publish to Zendesk", $crawler->html());
    }

    public function testEditDoesNotContainSyncControlIfAlreadySynced()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_case_update', array('id' => $this->caseWithTicket->getId()))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertNotContains("Publish to Zendesk", $crawler->html());
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
