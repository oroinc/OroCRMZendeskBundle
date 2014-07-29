<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Model\EntityProvider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;

/**
 * @dbIsolation
 */
class ZendeskEntityProviderTest extends WebTestCase
{
    /**
     * @var ZendeskEntityProvider
     */
    protected $target;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            array(
                'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData'
            )
        );
        $this->target = $this->getContainer()
            ->get('orocrm_zendesk.entity_provider.zendesk');
    }

    public function testGetNotSyncedTicketComments()
    {
        $channel = $this->getReference('zendesk_channel:second_test_channel');
        $iterator = $this->target->getNotSyncedTicketComments($channel);

        $commentThree = $this->getReference('zendesk_ticket_52_comment_3');
        $commentFour = $this->getReference('zendesk_ticket_52_comment_4');
        $expected = array(
            $commentThree->getId() => $commentThree,
            $commentFour->getId() => $commentFour
        );
        $this->assertCount(2, $iterator);
        foreach ($iterator as $ticketComment) {
            $this->assertInstanceOf('OroCRM\Bundle\ZendeskBundle\Entity\TicketComment', $ticketComment);
            $ticketId = $ticketComment->getId();
            $this->assertNotEmpty($ticketId);
            $this->assertArrayHasKey($ticketId, $expected);
            $this->assertEquals($expected[$ticketId], $ticketComment);
        }
    }
}
