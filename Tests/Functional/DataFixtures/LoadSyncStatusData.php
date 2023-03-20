<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\ZendeskBundle\Provider\TicketConnector;
use Oro\Bundle\ZendeskBundle\Provider\UserConnector;

class LoadSyncStatusData extends AbstractZendeskFixture implements DependentFixtureInterface
{
    private array $statusData = [
        [
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_COMPLETED,
            'connector' => UserConnector::TYPE,
            'message' => '',
            'date' => '2014-06-05T10:24:23Z'
        ],
        [
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_FAILED,
            'connector' => UserConnector::TYPE,
            'message' => '',
            'date' => '2014-06-05T11:24:23Z'
        ],
        [
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_COMPLETED,
            'connector' => UserConnector::TYPE,
            'message' => '',
            'date' => '2014-06-05T12:24:23Z',
            'reference' => 'zendesk_sync_state:last_user_complete_state'
        ],
        [
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_FAILED,
            'connector' => UserConnector::TYPE,
            'message' => '',
            'date' => '2014-06-06T11:24:23Z'
        ],
        [
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_COMPLETED,
            'connector' => TicketConnector::TYPE,
            'message' => '',
            'date' => '2014-06-06T11:24:23Z'
        ],
    ];
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->statusData as $data) {
            $channel = $this->getReference($data['channel']);
            unset($data['channel']);
            $entity       = new Status();
            $data['date'] = new \DateTime($data['date']);

            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $channel->addStatus($entity);

            if (isset($data['reference'])) {
                $this->setReference($data['reference'], $entity);
            }

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadChannelData'
        ];
    }
}
