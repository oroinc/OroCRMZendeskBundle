<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Entity\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ZendeskBundle\Entity\Repository\ZendeskRestTransportRepository;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ZendeskRestTransportRepositoryTest extends TestCase
{
    private const TEST_TRANSPORT_ID = 1;
    private const TEST_ENCRYPTED_ACCESS_TOKEN = 'encrypted-access-token';
    private const TEST_ENCRYPTED_REFRESH_TOKEN = 'encrypted-refresh-token';
    private const DEFAULT_THRESHOLD_DAYS = 7;

    private EntityManagerInterface&MockObject $em;
    private ZendeskRestTransportRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = ZendeskRestTransport::class;

        $this->em->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->repository = new ZendeskRestTransportRepository(
            $this->em,
            $classMetadata
        );
    }

    public function testUpdateOAuthTokensById(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects(self::once())
            ->method('update')
            ->willReturnSelf();

        $queryBuilder->expects(self::exactly(3))
            ->method('set')
            ->withConsecutive(
                ['t.accessToken', ':accessToken'],
                ['t.refreshToken', ':refreshToken'],
                ['t.oauthLastRefreshAt', ':refreshedAt']
            )
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('t.id = :id')
            ->willReturnSelf();

        $refreshedAt = new DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $queryBuilder->expects(self::exactly(4))
            ->method('setParameter')
            ->withConsecutive(
                ['accessToken', self::TEST_ENCRYPTED_ACCESS_TOKEN],
                ['refreshToken', self::TEST_ENCRYPTED_REFRESH_TOKEN],
                ['refreshedAt', $refreshedAt, Types::DATETIME_IMMUTABLE],
                ['id', self::TEST_TRANSPORT_ID]
            )
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('execute');

        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->repository->updateOAuthTokensById(
            self::TEST_TRANSPORT_ID,
            self::TEST_ENCRYPTED_ACCESS_TOKEN,
            self::TEST_ENCRYPTED_REFRESH_TOKEN,
            $refreshedAt
        );
    }

    public function testFindTransportsNeedingTokenRefresh(): void
    {
        $transport1 = $this->transport(1);
        $transport2 = $this->transport(2);

        $queryBuilder = $this->createMockQueryBuilder();
        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('t.refreshToken IS NOT NULL')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('t.oauthLastRefreshAt <= :threshold')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with(
                'threshold',
                self::callback(static function ($value): bool {
                    return $value instanceof \DateTime;
                })
            )
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([$transport1, $transport2]);

        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->repository->findTransportsNeedingTokenRefresh(self::DEFAULT_THRESHOLD_DAYS);

        self::assertCount(2, $result);
        self::assertSame($transport1, $result[0]);
        self::assertSame($transport2, $result[1]);
    }

    public function testFindTransportsNeedingTokenRefreshUsesCustomThreshold(): void
    {
        $customDays = 14;

        $queryBuilder = $this->createMockQueryBuilder();
        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with(
                'threshold',
                self::callback(static function ($value): bool {
                    return $value instanceof \DateTime;
                })
            )
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([]);

        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->repository->findTransportsNeedingTokenRefresh($customDays);
    }

    public function testFindTransportsNeedingTokenRefreshReturnsEmptyArray(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([]);

        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->repository->findTransportsNeedingTokenRefresh(self::DEFAULT_THRESHOLD_DAYS);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testClearOAuthTokensById(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects(self::once())
            ->method('update')
            ->willReturnSelf();

        $queryBuilder->expects(self::exactly(3))
            ->method('set')
            ->withConsecutive(
                ['t.accessToken', ':nullValue'],
                ['t.refreshToken', ':nullValue'],
                ['t.oauthLastRefreshAt', ':nullValue']
            )
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('t.id = :id')
            ->willReturnSelf();

        $queryBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['nullValue', null],
                ['id', self::TEST_TRANSPORT_ID]
            )
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('execute');

        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->repository->clearOAuthTokensById(self::TEST_TRANSPORT_ID);
    }

    public function testCountAuthorizedIntegrations(): void
    {
        $expectedCount = 3;

        $queryBuilder = $this->createMockQueryBuilder();
        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects(self::any())
            ->method('select')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('t.oauthLastRefreshAt IS NOT NULL')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn($expectedCount);

        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->repository->countAuthorizedIntegrations();

        self::assertSame($expectedCount, $result);
    }

    public function testCountAuthorizedIntegrationsReturnsZero(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects(self::any())
            ->method('select')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn(0);

        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->repository->countAuthorizedIntegrations();

        self::assertSame(0, $result);
    }

    private function createMockQueryBuilder(): QueryBuilder&MockObject
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects(self::any())
            ->method('from')
            ->willReturnSelf();

        $queryBuilder->expects(self::any())
            ->method('update')
            ->willReturnSelf();

        $queryBuilder->expects(self::any())
            ->method('select')
            ->willReturnSelf();

        return $queryBuilder;
    }

    private function transport(int $id): ZendeskRestTransport&MockObject
    {
        $transport = $this->createMock(ZendeskRestTransport::class);
        $transport->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $transport;
    }
}
