<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Model;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\ZendeskBundle\Model\SyncState;
use OroCRM\Bundle\ZendeskBundle\Provider\UserConnector;

/**
 * @dbIsolation
 */
class SyncStateTest extends WebTestCase
{
    /**
     * @var SyncState
     */
    protected $target;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(array(
                'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadSyncStatusData'
            ));
        $this->target = $this->getSyncStateService();
    }

    public function testServiceLoad()
    {
        $this->assertInstanceOf('OroCRM\Bundle\ZendeskBundle\Model\SyncState', $this->target);
    }

    public function testAddTicketId()
    {
        $expected = array(rand(), rand(), rand(), rand(), rand());
        foreach ($expected as $id) {
            $this->assertInstanceOf('OroCRM\Bundle\ZendeskBundle\Model\SyncState', $this->target->addTicketId($id));
        }
        $syncState = $this->getSyncStateService();
        $actual  = $syncState->getTicketIds();

        $this->assertEquals($expected, $actual);
    }

    public function testSetTicketIds()
    {
        $expected = array(rand(), rand(), rand(), rand(), rand());

        $this->assertInstanceOf('OroCRM\Bundle\ZendeskBundle\Model\SyncState', $this->target->setTicketIds($expected));
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
        return $this->getContainer()->get('orocrm_zendesk.sync_state');
    }
}
