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

    public function testGetTicketCommentsByChannel()
    {
        $channel = $this->getReference('zendesk_channel:second_test_channel');
        $iterator = $this->target->getTicketCommentsByChannel($channel);
        $expected = array(
            $this->getReference('zendesk_ticket_52_comment_3'),
            $this->getReference('zendesk_ticket_52_comment_4')
        );
        $this->assertCount(2, $iterator);
        foreach ($iterator as $ticketComment) {
            $this->assertInstanceOf('OroCRM\Bundle\ZendeskBundle\Entity\TicketComment', $ticketComment);
            $this->assertEquals(current($expected), $ticketComment);
            next($expected);
        }
    }
}
