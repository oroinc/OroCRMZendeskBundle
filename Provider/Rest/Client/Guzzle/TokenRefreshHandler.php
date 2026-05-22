<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\Rest\Client\Guzzle;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\InvalidRefreshTokenException;
use Oro\Bundle\ZendeskBundle\Exception\OAuthExpiredException;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Token refresh handler for Zendesk OAuth authentication.
 * Handles automatic token renewal when 401 Unauthorized responses are received.
 */
class TokenRefreshHandler implements TokenRefreshHandlerInterface
{
    private ?ZendeskRestTransport $transportEntity = null;

    public function __construct(
        private readonly AccessTokenManagerInterface $tokenManager,
        private readonly SymmetricCrypterInterface $crypter,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    #[\Override]
    public function setTransportContext(ZendeskRestTransport $transportEntity): void
    {
        $this->transportEntity = $transportEntity;
    }

    #[\Override]
    public function refreshToken(): string
    {
        if (null === $this->transportEntity) {
            throw TokenRefreshException::missingTransportContext();
        }

        try {
            $this->tokenManager->refreshAccessToken($this->transportEntity);
        } catch (InvalidRefreshTokenException $e) {
            $this->logger?->error('Zendesk OAuth refresh token is invalid or expired', [
                'transport_id' => $this->transportEntity->getId(),
                'url' => $this->transportEntity->getUrl(),
                'error' => $e->getMessage(),
            ]);

            throw new OAuthExpiredException(
                'Zendesk OAuth authorization has expired. Please reconnect your Zendesk account.',
                0,
                $e
            );
        }

        return $this->crypter->decryptData($this->transportEntity->getAccessToken() ?? '');
    }
}
