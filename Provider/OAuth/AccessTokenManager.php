<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\OAuth;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;
use Oro\Bundle\ZendeskBundle\Model\OAuth\TokenResponse;
use Psr\Log\LoggerInterface;

/**
 * Manages OAuth access token lifecycle: fetches tokens via provider and persists to transport.
 */
class AccessTokenManager implements AccessTokenManagerInterface
{
    private const RECENT_REFRESH_THRESHOLD_SECONDS = 60;

    public function __construct(
        private readonly AccessTokenProviderInterface $tokenProvider,
        private readonly SymmetricCrypterInterface $crypter,
        private readonly ManagerRegistry $doctrine,
        private readonly TokenRefreshLockManager $lockManager,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    #[\Override]
    public function exchangeAuthorizationCode(
        ZendeskRestTransport $transport,
        string $code,
        string $codeVerifier
    ): void {
        $tokenData = $this->tokenProvider->getAccessTokenByAuthorizationCode($transport, $code, $codeVerifier);

        $this->persistTokens($transport, $tokenData);

        $repository = $this->doctrine->getRepository($transport::class);
        $repository->clearLegacyAuthenticationFields($transport->getId());
    }

    #[\Override]
    public function refreshAccessToken(ZendeskRestTransport $transport): void
    {
        $errorMessage = match (null) {
            $transport->getId() => 'Transport must be persisted before token refresh',
            $transport->getRefreshToken() => 'Transport does not have a refresh token',
            default => null,
        };

        if (null !== $errorMessage) {
            throw new TokenRefreshException($errorMessage);
        }

        $this->lockManager->executeWithLock(
            (int)$transport->getId(),
            fn () => $this->performTokenRefreshCallback($transport)
        );
    }

    /**
     * Performs the actual token refresh within the distributed lock.
     */
    private function performTokenRefreshCallback(ZendeskRestTransport $transport): void
    {
        $em = $this->doctrine->getManagerForClass($transport::class);
        if ($em->contains($transport)) {
            $em->refresh($transport);
        }

        if ($this->wasRecentlyRefreshed($transport)) {
            $this->logger?->debug(
                'Token was recently refreshed, skipping API call',
                ['transport_id' => $transport->getId()]
            );

            return;
        }

        $tokenData = $this->tokenProvider->refreshAccessToken($transport);
        $this->persistTokens($transport, $tokenData);
    }

    /**
     * Check if token was refreshed within the threshold to avoid redundant API calls
     */
    private function wasRecentlyRefreshed(ZendeskRestTransport $transport): bool
    {
        $lastRefreshAt = $transport->getOauthLastRefreshAt();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $secondsAgo = $now->getTimestamp() - $lastRefreshAt->getTimestamp();

        return $secondsAgo < self::RECENT_REFRESH_THRESHOLD_SECONDS;
    }

    private function persistTokens(
        ZendeskRestTransport $transport,
        TokenResponse $tokenResponse
    ): void {
        $encryptedAccessToken = $this->crypter->encryptData($tokenResponse->getAccessToken());
        $encryptedRefreshToken = $this->crypter->encryptData($tokenResponse->getRefreshToken());
        $refreshedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $repository = $this->doctrine->getRepository($transport::class);
        $repository->updateOAuthTokensById(
            id: $transport->getId(),
            encryptedAccessToken: $encryptedAccessToken,
            encryptedRefreshToken: $encryptedRefreshToken,
            refreshedAt: $refreshedAt
        );

        $em = $this->doctrine->getManagerForClass($transport::class);
        if ($em->contains($transport)) {
            $em->refresh($transport);
        } else {
            // fallback keeps same instance usable
            $transport->setAccessToken($encryptedAccessToken);
            $transport->setRefreshToken($encryptedRefreshToken);
            $transport->setOauthLastRefreshAt($refreshedAt);
        }

        $transport->clearSettings();

        $this->logger?->info('Zendesk OAuth tokens persisted successfully', [
            'transport_id' => $transport->getId(),
        ]);
    }
}
