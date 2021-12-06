<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\EventListener\Doctrine;

use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\ZendeskBundle\Provider\TicketConnector;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketData;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SyncUpdateCaseListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadTicketData::class]);

        $user = $this->getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);
        $this->assertNotNull($user, 'Cannot get admin user');
        $token = new UsernamePasswordToken($user, $user->getUsername(), 'main');
        $this->getContainer()->get('security.token_storage')->setToken($token);
    }

    public function testListenerCreatesSyncJobOnCaseUpdate()
    {
        $ticket = $this->getReference('oro_zendesk:ticket_43');

        $case = $this->getReference('oro_zendesk:case_2');
        $case->setSubject('Updated subject');

        self::getContainer()->get('doctrine')->getManager()->flush($case);

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
        self::getContainer()->get('doctrine')->getManager()->flush($case);

        self::assertMessagesEmpty(Topics::REVERS_SYNC_INTEGRATION);
    }
}
