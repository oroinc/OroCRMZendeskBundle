<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\Model;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Model\SyncState;
use Oro\Bundle\ZendeskBundle\Provider\UserConnector;

class SyncStateTest extends WebTestCase
{
    /**
     * @var SyncState
     */
    protected $target;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(array(
                'Oro\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadSyncStatusData'
            ));
        $this->target = $this->getSyncStateService();
    }

    public function testServiceLoad()
    {
        $this->assertInstanceOf('Oro\Bundle\ZendeskBundle\Model\SyncState', $this->target);
    }

    public function testAddTicketId()
    {
        $expected = array(rand(), rand(), rand(), rand(), rand());
        foreach ($expected as $id) {
            $this->assertInstanceOf('Oro\Bundle\ZendeskBundle\Model\SyncState', $this->target->addTicketId($id));
        }
        $syncState = $this->getSyncStateService();
        $actual  = $syncState->getTicketIds();

        $this->assertEquals($expected, $actual);
    }

    public function testSetTicketIds()
    {
        $expected = array(rand(), rand(), rand(), rand(), rand());

        $this->assertInstanceOf('Oro\Bundle\ZendeskBundle\Model\SyncState', $this->target->setTicketIds($expected));
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

    /**
     * @return SyncState
     */
    protected function getSyncStateService()
    {
        return $this->getContainer()->get('oro_zendesk.sync_state');
    }
}
