<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Provider\Rest\Client\Guzzle;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\ConfigurationException;
use Oro\Bundle\ZendeskBundle\Exception\OAuthExpiredException;
use Oro\Bundle\ZendeskBundle\Exception\RestException;
use Oro\Bundle\ZendeskBundle\Exception\TokenRefreshException;

/**
 * Interface for handling token refresh when an unauthorized error occurs during API requests.
 * Implementations should refresh the access token and update client authentication headers.
 */
interface TokenRefreshHandlerInterface
{
    public function setTransportContext(ZendeskRestTransport $transportEntity): void;

    /**
     * @throws TokenRefreshException
     * @throws ConfigurationException
     * @throws OAuthExpiredException
     * @throws RestException
     */
    public function refreshToken(): string;
}
