<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Model;

use DateTimeImmutable;
use DateTimeZone;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;
use Oro\Bundle\ZendeskBundle\Model\AuthorizationTypeCredentialsCleaner;
use PHPUnit\Framework\TestCase;

final class AuthorizationTypeCredentialsCleanerTest extends TestCase
{
    public function testClearByAuthorizationTypeClearsEmailTokenCredentialsForOAuth(): void
    {
        $transport = (new ZendeskRestTransport())
            ->setAuthorizationType(AuthorizationType::OAUTH)
            ->setEmail('agent@example.com')
            ->setToken('api-token')
            ->setOauthClientId('oauth-client-id')
            ->setAccessToken('access-token')
            ->setRefreshToken('refresh-token')
            ->setOauthLastRefreshAt(new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC')));

        $transport->getSettingsBag();

        AuthorizationTypeCredentialsCleaner::clearByAuthorizationType($transport);

        self::assertNull($transport->getEmail());
        self::assertNull($transport->getToken());
        self::assertSame('oauth-client-id', $transport->getOauthClientId());
        self::assertSame('access-token', $transport->getAccessToken());
        self::assertSame('refresh-token', $transport->getRefreshToken());
        self::assertInstanceOf(DateTimeImmutable::class, $transport->getOauthLastRefreshAt());
        self::assertNull($transport->getSettingsBag()->get('email'));
        self::assertNull($transport->getSettingsBag()->get('token'));
    }

    public function testClearByAuthorizationTypeClearsOAuthCredentialsForEmailToken(): void
    {
        $transport = (new ZendeskRestTransport())
            ->setAuthorizationType(AuthorizationType::EMAIL_TOKEN)
            ->setEmail('agent@example.com')
            ->setToken('api-token')
            ->setOauthClientId('oauth-client-id')
            ->setAccessToken('access-token')
            ->setRefreshToken('refresh-token')
            ->setOauthLastRefreshAt(new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC')));

        $transport->getSettingsBag();

        AuthorizationTypeCredentialsCleaner::clearByAuthorizationType($transport);

        self::assertSame('agent@example.com', $transport->getEmail());
        self::assertSame('api-token', $transport->getToken());
        self::assertNull($transport->getOauthClientId());
        self::assertNull($transport->getAccessToken());
        self::assertNull($transport->getRefreshToken());
        self::assertNull($transport->getOauthLastRefreshAt());
        self::assertNull($transport->getSettingsBag()->get('oauthClientId'));
        self::assertNull($transport->getSettingsBag()->get('accessToken'));
        self::assertNull($transport->getSettingsBag()->get('refreshToken'));
        self::assertNull($transport->getSettingsBag()->get('oauthLastRefreshAt'));
    }

    public function testClearByAuthorizationTypeTreatsDefaultAuthorizationAsOAuth(): void
    {
        $transport = (new ZendeskRestTransport())
            ->setEmail('agent@example.com')
            ->setToken('api-token');

        AuthorizationTypeCredentialsCleaner::clearByAuthorizationType($transport);

        self::assertSame(AuthorizationType::DEFAULT, $transport->getAuthorizationType());
        self::assertNull($transport->getEmail());
        self::assertNull($transport->getToken());
    }
}
