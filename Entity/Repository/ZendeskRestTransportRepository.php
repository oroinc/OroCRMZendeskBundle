<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Entity\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;

/**
 * Repository for ZendeskRestTransport entity
 */
class ZendeskRestTransportRepository extends EntityRepository
{
    public function updateOAuthTokensById(
        int $id,
        string $encryptedAccessToken,
        string $encryptedRefreshToken,
        DateTimeImmutable $refreshedAt
    ): void {
        $qb = $this->createQueryBuilder('t');
        $qb->update()
            ->set('t.accessToken', ':accessToken')
            ->set('t.refreshToken', ':refreshToken')
            ->set('t.oauthLastRefreshAt', ':refreshedAt')
            ->where('t.id = :id')
            ->setParameter('accessToken', $encryptedAccessToken)
            ->setParameter('refreshToken', $encryptedRefreshToken)
            ->setParameter('refreshedAt', $refreshedAt, Types::DATETIME_IMMUTABLE)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Find transports that need token refresh (last refresh > N days ago)
     */
    public function findTransportsNeedingTokenRefresh(int $daysThreshold = 7): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.refreshToken IS NOT NULL')
            ->andWhere('t.oauthLastRefreshAt <= :threshold')
            ->setParameter('threshold', new \DateTime(sprintf('-%d days', $daysThreshold)));

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Clears OAuth tokens for a specific transport.
     */
    public function clearOAuthTokensById(int $id): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.accessToken', ':nullValue')
            ->set('t.refreshToken', ':nullValue')
            ->set('t.oauthLastRefreshAt', ':nullValue')
            ->where('t.id = :id')
            ->setParameter('nullValue', null)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Count how many transports have valid OAuth tokens (oauthLastRefreshAt is not null)
     */
    public function countAuthorizedIntegrations(): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.oauthLastRefreshAt IS NOT NULL');

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function clearLegacyAuthenticationFields(int $id): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.email', ':nullValue')
            ->set('t.token', ':nullValue')
            ->where('t.id = :id')
            ->setParameter('nullValue', null)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}
