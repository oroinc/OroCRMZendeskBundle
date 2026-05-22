<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;
use Oro\Bundle\ZendeskBundle\Form\Type\RestTransportSettingsFormType;
use Oro\Bundle\ZendeskBundle\Model\OAuth\OAuthCallbackUrlProviderInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RestTransportSettingsFormTypeTest extends FormIntegrationTestCase
{
    private const ZENDESK_URL = 'https://test.zendesk.com';
    private const ZENDESK_USER_EMAIL = 'user@example.com';

    private RestTransportSettingsFormType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $callbackUrlProvider = $this->createMock(OAuthCallbackUrlProviderInterface::class);
        $this->formType = new RestTransportSettingsFormType($callbackUrlProvider);

        parent::setUp();
    }

    /**
     * The parent implementation resolves the validation.yml path by searching for "Bundle" in the entity file path.
     * Since ZendeskBundle classes reside in "package/zendesk/" (without "Bundle" in the directory structure),
     * the automatic resolution fails. This override provides the correct path explicitly.
     */
    #[\Override]
    protected function getConfigFile(string $class): ?string
    {
        if ($class === ZendeskRestTransport::class) {
            return dirname(__DIR__, 4) . '/Resources/config/validation.yml';
        }

        return parent::getConfigFile($class);
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)],
                ]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @dataProvider submitValidProvider
     */
    public function testSubmitValid(array $submitData): void
    {
        $form = $this->factory->create(RestTransportSettingsFormType::class, new ZendeskRestTransport());
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    public function submitValidProvider(): array
    {
        return [
            'email_token authorization' => [
                $this->getValidEmailTokenData(),
            ],
            'oauth authorization' => [
                $this->getValidOAuthTokenData(),
            ],
        ];
    }

    /**
     * @dataProvider submitWithLongValuesProvider
     */
    public function testSubmitWithTooLongValues(array $override): void
    {
        $submitData = array_replace_recursive($this->getValidEmailTokenData(), $override);

        $form = $this->factory->create(RestTransportSettingsFormType::class, new ZendeskRestTransport());
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function submitWithLongValuesProvider(): array
    {
        return [
            'url too long' => [['url' => 'https://' . str_repeat('a', 250) . '.com']],
            'zendeskUserEmail too long' => [['zendeskUserEmail' => str_repeat('a', 90) . '@example.com']],
        ];
    }

    /**
     * @dataProvider submitInvalidMissingCredentialsProvider
     */
    public function testSubmitInvalidMissingCredentials(array $submitData): void
    {
        $form = $this->factory->create(RestTransportSettingsFormType::class, new ZendeskRestTransport());
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function submitInvalidMissingCredentialsProvider(): array
    {
        return [
            'email_token: missing email' => [
                array_replace($this->getValidEmailTokenData(), ['email' => '']),
            ],
            'email_token: missing token' => [
                array_replace($this->getValidEmailTokenData(), ['token' => '']),
            ],
            'oauth: missing oauthClientId' => [
                array_replace($this->getValidOAuthTokenData(), ['oauthClientId' => '']),
            ],
        ];
    }

    public function testGetBlockPrefix(): void
    {
        self::assertSame(RestTransportSettingsFormType::NAME, $this->formType->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => 'Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport']);

        $this->formType->configureOptions($resolver);
    }

    private function getValidEmailTokenData(): array
    {
        return [
            'url' => self::ZENDESK_URL,
            'authorizationType' => AuthorizationType::EMAIL_TOKEN->value,
            'email' => self::ZENDESK_USER_EMAIL,
            'token' => 'token123',
            'zendeskUserEmail' => self::ZENDESK_USER_EMAIL,
        ];
    }

    private function getValidOAuthTokenData(): array
    {
        return [
            'url' => self::ZENDESK_URL,
            'authorizationType' => AuthorizationType::OAUTH->value,
            'oauthClientId' => 'clintId123',
            'zendeskUserEmail' => self::ZENDESK_USER_EMAIL,
        ];
    }
}
