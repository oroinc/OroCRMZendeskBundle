<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\Stub;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Model\OAuth\OAuthCallbackUrlProviderInterface;
use Oro\Bundle\ZendeskBundle\Model\OAuthManager;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Test stub for OAuthManager that bypasses session code_verifier requirement
 */
class OAuthManagerStub extends OAuthManager
{
    private const TEST_CODE_VERIFIER = 'test_code_verifier_value';

    public function __construct(
        RequestStack $requestStack,
        private readonly AccessTokenManagerInterface $testTokenManager,
        OAuthCallbackUrlProviderInterface $callbackUrlProvider,
    ) {
        parent::__construct($requestStack, $testTokenManager, $callbackUrlProvider);
    }

    /**
     * Override to use a fixed test code_verifier instead of session
     */
    #[\Override]
    public function exchangeAuthorizationCode(ZendeskRestTransport $transport, string $code): void
    {
        // Use fixed test code_verifier instead of retrieving from session
        $this->testTokenManager->exchangeAuthorizationCode(
            $transport,
            $code,
            self::TEST_CODE_VERIFIER
        );
    }
}
