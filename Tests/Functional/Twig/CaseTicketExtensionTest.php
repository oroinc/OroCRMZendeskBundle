<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Twig;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadCaseEntityData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class CaseTicketExtensionTest extends WebTestCase
{
    /**
     * @var CaseEntity
     */
    protected static $case;

    /**
     * @var CaseEntity
     */
    protected static $secondCase;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());

        $this->loadFixtures(array('OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketEntityData'));
    }

    protected function postFixtureLoad()
    {
        $caseData = LoadCaseEntityData::getCaseData();

        $repository = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OroCRMCaseBundle:CaseEntity');

        $case = $repository->findOneBySubject($caseData[0]['subject']);

        $this->assertNotNull($case);

        static::$case = $case;

        $case = $repository->findOneBySubject($caseData[1]['subject']);

        $this->assertNotNull($case);

        static::$secondCase = $case;
    }

    public function testGetCaseTicket()
    {
        $extension = $this->client->getContainer()
            ->get('orocrm_zendesk.twig.extension.case_ticket');
        $ticket = $extension->getCaseTicket(static::$secondCase);
        $this->assertNull($ticket);
        $ticket = $extension->getCaseTicket(static::$case);
        $this->assertNotNull($ticket);
    }
}
