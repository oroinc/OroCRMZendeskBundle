<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbReindex
 * @dbIsolation
 */
class TicketControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(['debug' => false], $this->generateWsseAuthHeader());
        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);
    }

    public function testPostSyncCaseActionSuccess()
    {
        $caseId = $this->getReference('orocrm_zendesk:case_3')->getId();
        $channelId = $this->getReference('zendesk_channel:first_test_channel')->getId();
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_ticket_sync_case', ['id' => $caseId, 'channelId' => $channelId])
        );
        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($response, 200);
    }

    public function testPostSyncCaseActionFail()
    {
        $caseId = $this->getReference('orocrm_zendesk:case_3')->getId();
        $channelId = $this->getReference('zendesk_channel:first_test_channel')->getId();
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_ticket_sync_case', ['id' => $caseId, 'channelId' => 0])
        );
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($response, 404);

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_ticket_sync_case', ['id' => 0, 'channelId' => $channelId])
        );
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($response, 404);
    }
}
