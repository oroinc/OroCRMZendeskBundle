<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\Model\EntityProvider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketData;

class ZendeskEntityProviderTest extends WebTestCase
{
    private ZendeskEntityProvider $target;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTicketData::class]);

        $this->target = $this->getContainer()
            ->get('oro_zendesk.entity_provider.zendesk');
    }

    public function testGetNotSyncedTicketComments()
    {
        $channel = $this->getReference('zendesk_channel:second_test_channel');
        $iterator = $this->target->getNotSyncedTicketComments($channel);

        $commentThree = $this->getReference('zendesk_ticket_52_comment_3');
        $commentFour = $this->getReference('zendesk_ticket_52_comment_4');
        $expected = [
            $commentThree->getId() => $commentThree,
            $commentFour->getId() => $commentFour
        ];
        $this->assertCount(2, $iterator);
        foreach ($iterator as $ticketComment) {
            $this->assertInstanceOf(TicketComment::class, $ticketComment);
            $ticketId = $ticketComment->getId();
            $this->assertNotEmpty($ticketId);
            $this->assertArrayHasKey($ticketId, $expected);
            $this->assertEquals($expected[$ticketId], $ticketComment);
        }
    }
}
