<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Model\OAuth;

use Oro\Bundle\ZendeskBundle\Model\OAuth\OAuthCallbackUrlProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class OAuthCallbackUrlProviderTest extends TestCase
{
    private const CALLBACK_URL = 'https://app.example.com/zendesk/oauth/callback';

    private RouterInterface&MockObject $router;
    private OAuthCallbackUrlProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->provider = new OAuthCallbackUrlProvider($this->router);
    }

    public function testGetCallbackUrlGeneratesAbsoluteUrl(): void
    {
        $this->router->expects(self::once())
            ->method('generate')
            ->with('oro_zendesk_oauth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn(self::CALLBACK_URL);

        self::assertSame(self::CALLBACK_URL, $this->provider->getCallbackUrl());
    }
}
