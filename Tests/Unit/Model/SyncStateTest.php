<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\ZendeskBundle\Model\SyncState;
use Psr\Log\LoggerInterface;

class SyncStateTest extends \PHPUnit\Framework\TestCase
{
    const STATUS_ID = 1;

    /**
     * @var SyncState
     */
    protected $syncState;

    /**
     * @var string
     */
    protected $connector = "CONNECTOR";

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $channel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $channelRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $status;

    protected function setUp(): void
    {
        $this->channel = $this->createMock(Channel::class);
        $this->channelRepository = $this->createMock(ChannelRepository::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($this->channelRepository);

        $this->syncState = new SyncState($managerRegistry);
        $this->syncState->setLogger($this->logger);
    }

    public function testLastStatusNotFound()
    {
        $this->channelRepository
            ->expects($this->once())
            ->method('getLastStatusForConnector')
            ->with($this->channel, $this->connector, Status::STATUS_COMPLETED)
            ->willReturn(null);

        $this->assertSame(
            null,
            $this->syncState->getLastSyncDate($this->channel, $this->connector)
        );
    }

    /**
     * @dataProvider getLastSyncDateProvider
     */
    public function testGetLastSyncDate(array $data, $error, $result)
    {
        $this->getMockForStatusEntity($data);

        if (false !== $error) {
            $this->logger
                ->expects($this->once())
                ->method('error')
                ->with($error);
        }

        $this->assertEquals(
            $result,
            $this->syncState->getLastSyncDate($this->channel, $this->connector)
        );
    }

    /**
     * @return array
     */
    public function getLastSyncDateProvider()
    {
        return [
            "Key 'lastSyncDate' isn't set" => [
                'data' => [],
                'error' => false,
                'result' => null
            ],
            "Data by key 'lastSyncDate' has incorrect format" => [
                'data' => [
                    SyncState::LAST_SYNC_DATE_KEY => 'incorrect_format'
                ],
                'error' => 'Status with [id=1] contains incorrect date format in data by key "lastSyncDate".',
                'result' => null
            ],
            "Data by key 'lastSyncDate' present and has correct format" => [
                'data' => [
                    SyncState::LAST_SYNC_DATE_KEY => '2004-02-12T15:19:21+00:00'
                ],
                'error' => false,
                'result' => new \DateTime('2004-02-12T15:19:21+00:00')
            ]
        ];
    }

    protected function getMockForStatusEntity(array $data)
    {
        $this->status = $this->createMock(Status::class);

        $this->status
            ->expects($this->any())
            ->method('getId')
            ->willReturn(self::STATUS_ID);

        $this->status
            ->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn($data);

        $this->channelRepository
            ->expects($this->once())
            ->method('getLastStatusForConnector')
            ->with($this->channel, $this->connector, Status::STATUS_COMPLETED)
            ->willReturn($this->status);
    }

    protected function tearDown(): void
    {
        unset(
            $this->syncState,
            $this->channel,
            $this->logger,
            $this->status,
            $this->channelRepository
        );
    }
}
