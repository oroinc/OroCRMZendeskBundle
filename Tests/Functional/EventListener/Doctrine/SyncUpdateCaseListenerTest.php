<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\EventListener\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroCRM\Bundle\ZendeskBundle\Provider\TicketConnector;

/**
 * @dbIsolation
 */
class SyncNewCommentsListenerTest extends AbstractSyncSchedulerTest
{
    /** @var ManagerRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->registry = $this->getContainer()->get('doctrine');

        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);

        $this->setSecurityContextTokenByUser($this->getAdminUser());
    }

    public function testListenerCreatesSyncJobOnCaseUpdate()
    {
        $ticket = $this->getReference('orocrm_zendesk:ticket_43');

        $case = $this->getReference('orocrm_zendesk:case_2');
        $case->setSubject('Updated subject');

        $this->registry->getManager()->flush($case);

        $jobs = $this->registry->getRepository('JMS\\JobQueueBundle\\Entity\\Job')
            ->findBy(['command' => 'oro:integration:reverse:sync']);

        $expectedJobArgs = [
            '--integration=' . $ticket->getChannel()->getId(),
            '--connector=' . TicketConnector::TYPE,
            '--params=' . serialize(['id' => $ticket->getId()]),
        ];

        $found = false;
        foreach ($jobs as $job) {
            if ($job->getArgs() == $expectedJobArgs) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Can\'t find JMS job for ticket sync.');

        return count($jobs);
    }

    /**
     * @depends testListenerCreatesSyncJobOnCaseUpdate
     */
    public function testListenerSkipsCaseWithoutRelatedTicket($jobsCount)
    {
        $case = $this->getReference('orocrm_zendesk:case_3');

        $case->setSubject('Updated subject');
        $this->registry->getManager()->flush($case);

        $jobs = $this->registry->getRepository('JMS\\JobQueueBundle\\Entity\\Job')
            ->findBy(['command' => 'oro:integration:reverse:sync']);

        $this->assertCount(
            $jobsCount,
            $jobs,
            'JMS job queue should not be affected by listener for case wihtout ticket.'
        );
    }
}
