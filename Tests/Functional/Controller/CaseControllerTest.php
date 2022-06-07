<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\Controller;

use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketData;
use Symfony\Component\DomCrawler\Crawler;

class CaseControllerTest extends WebTestCase
{
    private CaseEntity $caseWithoutTicket;
    private CaseEntity $caseWithTicket;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadTicketData::class]);
        $this->caseWithTicket = $this->getReference('oro_zendesk:case_1');
        $this->caseWithoutTicket = $this->getReference('oro_zendesk:case_3');
    }

    public function testViewWithoutLinkedTicket()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_case_view', ['id' => $this->caseWithoutTicket->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString(
            $this->caseWithoutTicket->getSubject() . ' - Cases - Activities',
            $crawler->html()
        );
        self::assertStringNotContainsString('Zendesk ticket info', $crawler->html());
        self::assertCount(1, $crawler->filter('.zendesk-integration-btn-group'));
        self::assertStringContainsString('Publish to Zendesk', $crawler->html());
    }

    public function testViewWithLinkedTicket()
    {
        /** @var Ticket $expectedTicket */
        $expectedTicket = $this->getContainer()->get('doctrine')
            ->getRepository(Ticket::class)
            ->findOneBy(['originId' => 42]);
        $this->assertNotNull($expectedTicket);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_case_view', ['id' => $this->caseWithTicket->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString(
            $this->caseWithTicket->getSubject() . ' - Cases - Activities',
            $crawler->html()
        );
        self::assertStringContainsString('Zendesk ticket info', $crawler->html());
        self::assertCount(0, $crawler->filter('.zendesk-integration-btn-group'));
        self::assertStringNotContainsString('Publish to Zendesk', $crawler->html());

        $crawler = $crawler->filterXPath('//span[text()="Zendesk ticket info"]')->parents();

        $externalId = $this->getFieldValue('Ticket Number', $crawler);
        self::assertStringContainsString($expectedTicket->getOriginId(), $externalId->html());

        $problem = $this->getFieldValue('Problem', $crawler);
        self::assertStringContainsString($expectedTicket->getProblem()->getSubject(), $problem->html());

        $recipient = $this->getFieldValue('Recipient email', $crawler);
        self::assertStringContainsString($expectedTicket->getRecipient(), $recipient->html());

        $collaborators = $this->getFieldValue('Collaborators', $crawler);
        self::assertStringContainsString('Fred Taylor', $collaborators->html());
        self::assertStringContainsString('Alex Taylor', $collaborators->html());

        $submitter = $this->getFieldValue('Submitter', $crawler);
        self::assertStringContainsString('Fred Taylor', $submitter->html());

        $assignee = $this->getFieldValue('Assignee', $crawler);
        self::assertStringContainsString('Fred Taylor', $assignee->html());

        $requester = $this->getFieldValue('Requester', $crawler);
        self::assertStringContainsString('Alex Taylor', $requester->html());

        $status = $this->getFieldValue('Status', $crawler);
        self::assertStringContainsString($expectedTicket->getStatus()->getLabel(), $status->html());

        $priority = $this->getFieldValue('Priority', $crawler);
        self::assertStringContainsString($expectedTicket->getPriority()->getLabel(), $priority->html());

        $type = $this->getFieldValue('Type', $crawler);
        self::assertStringContainsString($expectedTicket->getType()->getLabel(), $type->html());
    }

    public function testEditContainSyncControlIfNotSyncedYet()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_case_update', ['id' => $this->caseWithoutTicket->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString('Publish to Zendesk', $crawler->html());
    }

    public function testEditDoesNotContainSyncControlIfAlreadySynced()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_case_update', ['id' => $this->caseWithTicket->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringNotContainsString('Publish to Zendesk', $crawler->html());
    }

    private function getFieldValue($label, Crawler $crawler): Crawler
    {
        $labelNode = $crawler->filterXPath("//label[text()=\"{$label}\"]");
        $this->assertTrue($labelNode->count() > 0, "label({$label}) not found");
        $value = $labelNode->parents()->first()->filterXPath('//div[@class="control-label"]');
        $this->assertTrue($value->count() > 0, "value({$label}) not found");

        return $value;
    }
}
