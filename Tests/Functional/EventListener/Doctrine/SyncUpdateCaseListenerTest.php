<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\EventListener\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketConnector;

/**
 * @dbIsolation
 */
class SyncUpdateCaseListenerTest extends AbstractSyncSchedulerTest
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

        $traces = $this->getMessageProducer()->getTopicSentMessages(Topics::REVERS_SYNC_INTEGRATION);

        self::assertCount(1, $traces);
        self::assertEquals([
            'integration_id' => $ticket->getChannel()->getId(),
            'connector_parameters' => ['id' => $ticket->getId()],
            'connector' => TicketConnector::TYPE,
            'transport_batch_size' => 100,
        ], $traces[0]['message']->getBody());
        self::assertEquals(MessagePriority::VERY_LOW, $traces[0]['message']->getPriority());
    }

    public function testListenerSkipsCaseWithoutRelatedTicket()
    {
        $this->getMessageProducer()->clear();

        $case = $this->getReference('orocrm_zendesk:case_3');

        $case->setSubject('Updated subject');
        $this->registry->getManager()->flush($case);

        $traces = $this->getMessageProducer()->getTopicSentMessages(Topics::REVERS_SYNC_INTEGRATION);

        self::assertCount(0, $traces);
    }

    /**
     * @return TraceableMessageProducer
     */
    private function getMessageProducer()
    {
        return self::getContainer()->get('oro_message_queue.message_producer');
    }
}
