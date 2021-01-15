<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\EventListener\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ZendeskBundle\Provider\TicketConnector;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;

class SyncUpdateCaseListenerTest extends AbstractSyncSchedulerTest
{
    use MessageQueueExtension;

    /** @var ManagerRegistry */
    protected $registry;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->registry = $this->getContainer()->get('doctrine');

        $this->loadFixtures(['Oro\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadTicketData']);

        $this->setSecurityContextTokenByUser($this->getAdminUser());
    }

    public function testListenerCreatesSyncJobOnCaseUpdate()
    {
        $ticket = $this->getReference('oro_zendesk:ticket_43');

        $case = $this->getReference('oro_zendesk:case_2');
        $case->setSubject('Updated subject');

        $this->registry->getManager()->flush($case);

        self::assertMessageSent(
            Topics::REVERS_SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id' => $ticket->getChannel()->getId(),
                    'connector_parameters' => ['id' => $ticket->getId()],
                    'connector' => TicketConnector::TYPE,
                    'transport_batch_size' => 100,
                ],
                MessagePriority::VERY_LOW
            )
        );
    }

    public function testListenerSkipsCaseWithoutRelatedTicket()
    {
        $case = $this->getReference('oro_zendesk:case_3');

        $case->setSubject('Updated subject');
        $this->registry->getManager()->flush($case);

        self::assertMessagesEmpty(Topics::REVERS_SYNC_INTEGRATION);
    }
}
