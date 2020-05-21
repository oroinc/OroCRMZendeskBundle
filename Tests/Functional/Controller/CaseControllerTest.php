<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\Controller;

use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Symfony\Component\DomCrawler\Crawler;

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

    protected function setUp(): void
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(array('Oro\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData'));
        $this->caseWithTicket = $this->getReference('oro_zendesk:case_1');
        $this->caseWithoutTicket = $this->getReference('oro_zendesk:case_3');
    }

    public function testViewWithoutLinkedTicket()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_case_view', array('id' => $this->caseWithoutTicket->getId()))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString(
            $this->caseWithoutTicket->getSubject() . " - Cases - Activities",
            $crawler->html()
        );
        static::assertStringNotContainsString("Zendesk ticket info", $crawler->html());
        $this->assertCount(1, $crawler->filter('.zendesk-integration-btn-group'));
        static::assertStringContainsString("Publish to Zendesk", $crawler->html());
    }

    public function testViewWithLinkedTicket()
    {
        /** @var Ticket $expectedTicket */
        $expectedTicket = $this->getContainer()->get('doctrine')
            ->getRepository('OroZendeskBundle:Ticket')->findOneByOriginId(42);
        $this->assertNotNull($expectedTicket);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_case_view', array('id' => $this->caseWithTicket->getId()))
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString(
            $this->caseWithTicket->getSubject() . " - Cases - Activities",
            $crawler->html()
        );
        static::assertStringContainsString("Zendesk ticket info", $crawler->html());
        $this->assertCount(0, $crawler->filter('.zendesk-integration-btn-group'));
        static::assertStringNotContainsString("Publish to Zendesk", $crawler->html());

        $crawler = $crawler->filterXPath('//span[text()="Zendesk ticket info"]')->parents();

        $externalId = $this->getFieldValue("Ticket Number", $crawler);
        static::assertStringContainsString($expectedTicket->getOriginId(), $externalId->html());

        $problem = $this->getFieldValue("Problem", $crawler);
        static::assertStringContainsString($expectedTicket->getProblem()->getSubject(), $problem->html());

        $recipient = $this->getFieldValue("Recipient email", $crawler);
        static::assertStringContainsString($expectedTicket->getRecipient(), $recipient->html());

        $collaborators = $this->getFieldValue("Collaborators", $crawler);
        static::assertStringContainsString('Fred Taylor', $collaborators->html());
        static::assertStringContainsString('Alex Taylor', $collaborators->html());

        $submitter = $this->getFieldValue("Submitter", $crawler);
        static::assertStringContainsString('Fred Taylor', $submitter->html());

        $assignee = $this->getFieldValue("Assignee", $crawler);
        static::assertStringContainsString('Fred Taylor', $assignee->html());

        $requester = $this->getFieldValue("Requester", $crawler);
        static::assertStringContainsString('Alex Taylor', $requester->html());

        $status = $this->getFieldValue("Status", $crawler);
        static::assertStringContainsString($expectedTicket->getStatus()->getLabel(), $status->html());

        $priority = $this->getFieldValue("Priority", $crawler);
        static::assertStringContainsString($expectedTicket->getPriority()->getLabel(), $priority->html());

        $type = $this->getFieldValue("Type", $crawler);
        static::assertStringContainsString($expectedTicket->getType()->getLabel(), $type->html());
    }

    public function testEditContainSyncControlIfNotSyncedYet()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_case_update', array('id' => $this->caseWithoutTicket->getId()))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString("Publish to Zendesk", $crawler->html());
    }

    public function testEditDoesNotContainSyncControlIfAlreadySynced()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_case_update', array('id' => $this->caseWithTicket->getId()))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringNotContainsString("Publish to Zendesk", $crawler->html());
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
