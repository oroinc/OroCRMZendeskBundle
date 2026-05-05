<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Form\Type\RestTransportSettingsFormType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RestTransportSettingsFormTypeTest extends FormIntegrationTestCase
{
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
                [],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)],
                ]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function testSubmitValid(): void
    {
        $submitData = [
            'url' => 'https://test.zendesk.com',
            'email' => 'test@example.com',
            'token' => 'token123',
            'zendeskUserEmail' => 'user@example.com',
        ];

        $form = $this->factory->create(RestTransportSettingsFormType::class, new ZendeskRestTransport());
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    /**
     * @dataProvider submitWithLongValuesProvider
     */
    public function testSubmitWithTooLongValues(array $override): void
    {
        $submitData = array_replace_recursive([
            'url' => 'https://test.zendesk.com',
            'email' => 'test@example.com',
            'token' => 'token123',
            'zendeskUserEmail' => 'user@example.com',
        ], $override);

        $form = $this->factory->create(RestTransportSettingsFormType::class, new ZendeskRestTransport());
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function submitWithLongValuesProvider(): array
    {
        return [
            'url too long' => [['url' => 'https://' . str_repeat('a', 250) . '.com']],
            'email too long' => [['email' => str_repeat('a', 90) . '@example.com']],
            'token too long' => [['token' => str_repeat('a', 256)]],
            'zendeskUserEmail too long' => [['zendeskUserEmail' => str_repeat('a', 90) . '@example.com']],
        ];
    }

    public function testGetBlockPrefix(): void
    {
        $formType = new RestTransportSettingsFormType();
        self::assertSame(RestTransportSettingsFormType::NAME, $formType->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => 'Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport']);

        $formType = new RestTransportSettingsFormType();
        $formType->configureOptions($resolver);
    }
}
