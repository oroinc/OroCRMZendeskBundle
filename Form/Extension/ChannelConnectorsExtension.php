<?php

namespace Oro\Bundle\ZendeskBundle\Form\Extension;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType as ChannelTypeProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * The form extension for Zendesk channel.
 */
class ChannelConnectorsExtension extends AbstractTypeExtension
{
    private const SYNCHRONIZATION_SETTINGS_FIELD = 'synchronizationSettings';
    private const TWO_WAY_SYNC_FIELD = 'isTwoWaySyncEnabled';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            array($this, 'onPostSubmit')
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            [$this, 'onPostSetData']
        );
    }

    /**
     * Adjust existing two-way sync field options only for Zendesk channels.
     */
    public function onPostSetData(FormEvent $event): void
    {
        if (ChannelTypeProvider::TYPE !== $event->getData()?->getType()) {
            return;
        }

        $form = $event->getForm();
        $this->modifyTwoWaySyncField($form);
    }

    /**
     * Set all connectors to Zendesk channel
     */
    public function onPostSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!$data || $data->getType() !== ChannelTypeProvider::TYPE) {
            return;
        }
        $options = $event->getForm()['connectors']->getConfig()->getOptions();
        $connectors = array_values($options['choices']);
        $data->setConnectors($connectors);
    }

    /**
     * Set all connectors disabled and checked on view
     */
    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $data = $form->getData();
        if (!$data || $data->getType() !== ChannelTypeProvider::TYPE) {
            return;
        }

        foreach ($view['connectors']->children as $checkbox) {
            $checkbox->vars['checked'] = true;
            $checkbox->vars['disabled'] = true;
        }
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ChannelType::class];
    }

    public function modifyTwoWaySyncField(FormInterface $form): void
    {
        if (!$form->has(self::SYNCHRONIZATION_SETTINGS_FIELD)) {
            return;
        }

        $syncSettings = $form->get(self::SYNCHRONIZATION_SETTINGS_FIELD);
        if (!$syncSettings->has(self::TWO_WAY_SYNC_FIELD)) {
            return;
        }

        FormUtils::replaceField(
            $syncSettings,
            self::TWO_WAY_SYNC_FIELD,
            [
                'label' => 'oro.zendesk.synchronization_settings.two_way_sync.label',
                'tooltip' => 'oro.zendesk.synchronization_settings.two_way_sync.tooltip',
            ]
        );
    }
}
