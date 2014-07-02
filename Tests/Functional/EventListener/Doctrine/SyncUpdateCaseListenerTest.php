<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\EventListener\Doctrine;

use Doctrine\ORM\EntityManager;

use OroCRM\Bundle\ZendeskBundle\Provider\TicketConnector;

/**
 * @dbIsolation
 */
class SyncNewCommentsListenerTest extends AbstractSyncSchedulerTest
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);

        $this->setSecurityContextTokenByUser($this->getAdminUser());
    }

    public function testListenerCreatesSyncJobOnCaseUpdate()
    {
        $ticket = $this->getReference('orocrm_zendesk:ticket_43');

        $case = $this->getReference('orocrm_zendesk:case_2');
        $case->setSubject('Updated subject');

        $this->entityManager->flush($case);

        $jobs = $this->entityManager->getRepository('JMS\\JobQueueBundle\\Entity\\Job')
            ->findBy(
                [
                    'command' => 'oro:integration:reverse:sync',
                ]
            );

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
        $this->entityManager->flush($case);

        $jobs = $this->entityManager->getRepository('JMS\\JobQueueBundle\\Entity\\Job')
            ->findBy(['command' => 'oro:integration:reverse:sync']);

        $this->assertCount(
            $jobsCount,
            $jobs,
            'JMS job queue should not be affected by listener for case wihtout ticket.'
        );
    }
}
