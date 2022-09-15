<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\EventListener\Doctrine;

use Oro\Bundle\IntegrationBundle\Async\Topic\ReverseSyncIntegrationTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\ZendeskBundle\Provider\TicketConnector;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketData;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SyncUpdateCaseListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadTicketData::class]);

        $user = self::getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);

        self::assertNotNull($user, 'Cannot get admin user');
        $token = new UsernamePasswordToken($user, $user->getUsername(), 'main');
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    public function testListenerCreatesSyncJobOnCaseUpdate(): void
    {
        $ticket = $this->getReference('oro_zendesk:ticket_43');

        $case = $this->getReference('oro_zendesk:case_2');
        $case->setSubject('Updated subject');

        self::getContainer()->get('doctrine')->getManager()->flush($case);

        self::assertMessageSent(
            ReverseSyncIntegrationTopic::getName(),
            [
                'integration_id' => $ticket->getChannel()->getId(),
                'connector_parameters' => ['id' => $ticket->getId()],
                'connector' => TicketConnector::TYPE,

            ]
        );
        self::assertMessageSentWithPriority(ReverseSyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }

    public function testListenerSkipsCaseWithoutRelatedTicket(): void
    {
        $case = $this->getReference('oro_zendesk:case_3');

        $case->setSubject('Updated subject');
        self::getContainer()->get('doctrine')->getManager()->flush($case);

        self::assertMessagesEmpty(ReverseSyncIntegrationTopic::getName());
    }
}
