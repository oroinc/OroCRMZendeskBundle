<?php

namespace Oro\Bundle\ZendeskBundle\Form\Extension;

use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType as ChannelTypeProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Automatically sets and manages all connectors for Zendesk channel forms.
 */
class ChannelConnectorsExtension extends AbstractTypeExtension
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            array($this, 'onPostSubmit')
        );
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
    public function finishView(FormView $view, FormInterface $form, array $options): void
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
}
