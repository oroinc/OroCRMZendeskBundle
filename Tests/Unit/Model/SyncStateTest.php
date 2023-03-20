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
    private const STATUS_ID = 1;

    /** @var SyncState */
    private $syncState;

    /** @var string */
    private $connector = 'CONNECTOR';

    /** @var Channel|\PHPUnit\Framework\MockObject\MockObject */
    private $channel;

    /** @var ChannelRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $channelRepository;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->channel = $this->createMock(Channel::class);
        $this->channelRepository = $this->createMock(ChannelRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($this->channelRepository);

        $this->syncState = new SyncState($doctrine);
        $this->syncState->setLogger($this->logger);
    }

    public function testLastStatusNotFound()
    {
        $this->channelRepository
            ->expects($this->once())
            ->method('getLastStatusForConnector')
            ->with($this->channel, $this->connector, Status::STATUS_COMPLETED)
            ->willReturn(null);

        $this->assertNull(
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
            $this->logger->expects($this->once())
                ->method('error')
                ->with($error);
        }

        $this->assertEquals(
            $result,
            $this->syncState->getLastSyncDate($this->channel, $this->connector)
        );
    }

    public function getLastSyncDateProvider(): array
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

    private function getMockForStatusEntity(array $data)
    {
        $status = $this->createMock(Status::class);
        $status->expects($this->any())
            ->method('getId')
            ->willReturn(self::STATUS_ID);
        $status->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn($data);

        $this->channelRepository->expects($this->once())
            ->method('getLastStatusForConnector')
            ->with($this->channel, $this->connector, Status::STATUS_COMPLETED)
            ->willReturn($status);
    }
}
