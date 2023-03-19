<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\Model;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Model\SyncState;
use Oro\Bundle\ZendeskBundle\Provider\UserConnector;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadSyncStatusData;

class SyncStateTest extends WebTestCase
{
    private SyncState $target;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadSyncStatusData::class]);

        $this->target = $this->getSyncStateService();
    }

    public function testServiceLoad()
    {
        $this->assertInstanceOf(SyncState::class, $this->target);
    }

    public function testAddTicketId()
    {
        $expected = [rand(), rand(), rand(), rand(), rand()];
        foreach ($expected as $id) {
            $this->assertInstanceOf(SyncState::class, $this->target->addTicketId($id));
        }
        $syncState = $this->getSyncStateService();
        $actual  = $syncState->getTicketIds();

        $this->assertEquals($expected, $actual);
    }

    public function testSetTicketIds()
    {
        $expected = [rand(), rand(), rand(), rand(), rand()];

        $this->assertInstanceOf(SyncState::class, $this->target->setTicketIds($expected));
        $syncState = $this->getSyncStateService();
        $actual  = $syncState->getTicketIds();

        $this->assertEquals($expected, $actual);
    }

    public function getLastSyncDate()
    {
        $channel = $this->getReference('zendesk_channel:first_test_channel');
        $expected = $this->getReference('zendesk_sync_state:last_user_complete_state')->getDate();
        $actual = $this->target->getLastSyncDate($channel, UserConnector::TYPE);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    private function getSyncStateService(): SyncState
    {
        return $this->getContainer()->get('oro_zendesk.sync_state');
    }
}
