<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Model\OAuth;

/**
 * Provides OAuth callback URL for Zendesk integration
 */
interface OAuthCallbackUrlProviderInterface
{
    /**
     * Get OAuth callback URL
     */
    public function getCallbackUrl(): string;
}
