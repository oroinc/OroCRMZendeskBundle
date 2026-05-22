<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Model;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\RestException;

/**
 * Interface for managing OAuth 2.0 Authorization Code flow with PKCE for Zendesk
 */
interface OAuthManagerInterface
{
    /**
     * Generate authorization URL with PKCE challenge for user to authorize the app
     *
     * @throws \RuntimeException When channel not found for transport
     */
    public function generateAuthorizeUrl(ZendeskRestTransport $transport, string $state): string;

    /**
     * Exchange authorization code for access and refresh tokens using PKCE
     *
     * @throws \RuntimeException When code verifier not found in session or channel not found
     * @throws RestException When token exchange fails
     */
    public function exchangeAuthorizationCode(ZendeskRestTransport $transport, string $code): void;
}
