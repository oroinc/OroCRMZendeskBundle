<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\IntegrationBundle\Entity\Status;
use OroCRM\Bundle\ZendeskBundle\Provider\TicketConnector;
use OroCRM\Bundle\ZendeskBundle\Provider\UserConnector;

class LoadSyncStatusData extends AbstractZendeskFixture implements DependentFixtureInterface
{
    protected $statusData = array(
        array(
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_COMPLETED,
            'connector' => UserConnector::TYPE,
            'message' => '',
            'date' => '2014-06-05T10:24:23Z'
        ),
        array(
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_FAILED,
            'connector' => UserConnector::TYPE,
            'message' => '',
            'date' => '2014-06-05T11:24:23Z'
        ),
        array(
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_COMPLETED,
            'connector' => UserConnector::TYPE,
            'message' => '',
            'date' => '2014-06-05T12:24:23Z',
            'reference' => 'zendesk_sync_state:last_user_complete_state'
        ),
        array(
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_FAILED,
            'connector' => UserConnector::TYPE,
            'message' => '',
            'date' => '2014-06-06T11:24:23Z'
        ),
        array(
            'channel' => 'zendesk_channel:first_test_channel',
            'code' => Status::STATUS_COMPLETED,
            'connector' => TicketConnector::TYPE,
            'message' => '',
            'date' => '2014-06-06T11:24:23Z'
        ),
    );
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

            $this->setEntityPropertyValues($entity, $data, array('reference'));
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
        return array(
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadChannelData'
        );
    }
}
