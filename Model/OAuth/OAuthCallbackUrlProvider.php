<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Model\OAuth;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides OAuth callback URL
 */
class OAuthCallbackUrlProvider implements OAuthCallbackUrlProviderInterface
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    #[\Override]
    public function getCallbackUrl(): string
    {
        return $this->router->generate(
            'oro_zendesk_oauth_callback',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
