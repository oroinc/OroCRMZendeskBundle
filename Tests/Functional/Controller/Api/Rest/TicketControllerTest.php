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
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine');

        $caseId = $this->getReference('orocrm_zendesk:case_3')->getId();
        $channelId = $this->getReference('zendesk_channel:first_test_channel')->getId();
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_ticket_sync_case', ['id' => $caseId, 'channelId' => $channelId - 1])
        );
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($response, 404);

        $maxId = $em->getRepository('OroCRMCaseBundle:CaseEntity')->createQueryBuilder('c')
            ->select('MAX(c.id)')->getQuery()->getSingleScalarResult();

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_ticket_sync_case', ['id' => $maxId + 1, 'channelId' => $channelId])
        );
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($response, 404);
    }
}
