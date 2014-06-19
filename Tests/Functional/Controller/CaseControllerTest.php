<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadCaseEntityData;
use OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketEntityData;
use OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadUserEntityData;
use Symfony\Component\DomCrawler\Crawler;

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
    protected static $caseId;

    /**
     * @var string
     */
    protected static $caseSubject;

    /**
     * @var int
     */
    protected static $secondCaseId;

    /**
     * @var string
     */
    protected static $secondCaseSubject;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());

        $this->loadFixtures(array('OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketEntityData'));
    }

    protected function postFixtureLoad()
    {
        $caseData = LoadCaseEntityData::getCaseData();

        $repository = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OroCRMCaseBundle:CaseEntity');
        static::$caseSubject = $caseData[0]['subject'];
        $case = $repository->findOneBySubject(static::$caseSubject);

        $this->assertNotNull($case);

        static::$caseId = $case->getId();

        static::$secondCaseSubject = $caseData[1]['subject'];
        $case = $repository->findOneBySubject(static::$secondCaseSubject);

        $this->assertNotNull($case);

        static::$secondCaseId = $case->getId();
    }

    public function testViewContainTicketInfoOnlyIfTicketLinked()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_case_view', array('id' => static::$secondCaseId))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(static::$secondCaseSubject." - Cases - Activities", $crawler->html());
        $this->assertNotContains("Zendesk ticket info", $crawler->html());
    }

    public function testViewHaveCorrectFields()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_case_view', array('id' => static::$caseId))
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(static::$caseSubject." - Cases - Activities", $crawler->html());
        $this->assertContains("Zendesk ticket info", $crawler->html());

        $crawler = $crawler->filterXPath('//span[text()="Zendesk ticket info"]')->parents();

        $ticketData = LoadTicketEntityData::getTicketsData();
        $expectedTicket = $ticketData[0];

        $externalId = $this->getFieldValue("External id", $crawler);
        $this->assertEquals($expectedTicket['externalId'], $externalId->html());

        $url = $this->getFieldValue("Url", $crawler);
        $this->assertEquals($expectedTicket['url'], $url->html());

        $problem = $this->getFieldValue("Problem", $crawler);
        $this->assertContains(static::$caseSubject, $problem->html());

        $recipient = $this->getFieldValue("Recipient email", $crawler);
        $this->assertContains($expectedTicket['recipient'], $recipient->html());

        $collaborators = $this->getFieldValue("Collaborators", $crawler);
        $this->assertContains('Fred Taylor', $collaborators->html());
        $this->assertContains('Alex Taylor', $collaborators->html());

        $submitter = $this->getFieldValue("Submitter", $crawler);
        $this->assertContains('Fred Taylor', $submitter->html());

        $assignee = $this->getFieldValue("Assignee", $crawler);
        $this->assertContains('Fred Taylor', $assignee->html());

        $requester = $this->getFieldValue("Requester", $crawler);
        $this->assertContains('Alex Taylor', $requester->html());

        $hasIncidents = $this->getFieldValue("Has incidents", $crawler);
        $this->assertContains($expectedTicket['hasIncidents'] ? 'Yes' : 'No', $hasIncidents->html());

        $status = $this->getFieldValue("Status", $crawler);
        $this->assertContains($expectedTicket['status_label'], $status->html());

        $priority = $this->getFieldValue("Priority", $crawler);
        $this->assertContains($expectedTicket['priority_label'], $priority->html());

        $type = $this->getFieldValue("Type", $crawler);
        $this->assertContains($expectedTicket['type_label'], $type->html());
    }

    protected function getFieldValue($label, Crawler $crawler)
    {
        $label = $crawler->filterXPath("//label[text()=\"{$label}\"]");
        $this->assertTrue($label->count() > 0, 'label not found');
        $value = $label->parents()->first()->filterXPath('//div[@class="control-label"]');
        $this->assertTrue($value->count() > 0, 'value not found');
        return $value;
    }
}
