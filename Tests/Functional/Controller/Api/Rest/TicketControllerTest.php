<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Controller\Api\Rest;

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
        $this->initClient(array('debug' => false), $this->generateWsseAuthHeader());
        $this->loadFixtures(array('OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData'));
    }

    public function testPostSyncCaseActionSuccess()
    {
        $caseId = $this->getReference('orocrm_zendesk:case_3')->getId();
        $channelId = $this->getReference('zendesk_channel:first_test_channel')->getId();
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_ticket_sync_case'),
            array('id' => $caseId, 'channelId' => $channelId)
        );
        $result = $this->client->getResponse();
        $this->assertEquals($result->getStatusCode(), 200);
    }

    public function testPostSyncCaseActionFail()
    {
        $caseId = $this->getReference('orocrm_zendesk:case_3')->getId();
        $channelId = $this->getReference('zendesk_channel:first_test_channel')->getId();
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_ticket_sync_case'),
            array('id' => $caseId, 'channelId' => 127)
        );
        $result = $this->client->getResponse();
        $this->assertTrue($result->isNotFound());

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_ticket_sync_case'),
            array('id' => 127, 'channelId' => $channelId)
        );
        $result = $this->client->getResponse();
        $this->assertTrue($result->isNotFound());
    }
}
