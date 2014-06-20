<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Twig;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ZendeskBundle\Twig\CaseTicketExtension;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class CaseTicketExtensionTest extends WebTestCase
{
    /**
     * @var CaseTicketExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());

        $this->loadFixtures(array('OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData'));

        $this->extension = $this->client->getContainer()
            ->get('orocrm_zendesk.twig.extension.case_ticket');
    }

    public function testGetCaseTicket()
    {
        $repository = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OroCRMCaseBundle:CaseEntity');

        $caseWithTicket = $repository->findOneBySubject('Case #1');
        $this->assertNotNull($caseWithTicket);

        $caseWithoutTicket = $repository->findOneBySubject('Case #3');
        $this->assertNotNull($caseWithoutTicket);

        $this->assertNull($this->extension->getCaseTicket($caseWithoutTicket));

        $ticket = $this->extension->getCaseTicket($caseWithTicket);
        $this->assertNotNull($ticket);

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket', $ticket);
        $this->assertEquals(42, $ticket->getOriginId());
    }
}
