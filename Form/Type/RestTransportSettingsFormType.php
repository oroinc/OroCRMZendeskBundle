<?php

namespace OroCRM\Bundle\ZendeskBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RestTransportSettingsFormType extends AbstractType
{
    const NAME = 'orocrm_zendesk_rest_transport_setting_form_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'url',
            'text',
            [
                'label' => 'orocrm.zendesk.zendeskresttransport.url.label',
                'required' => true,
                'tooltip' => 'orocrm.zendesk.form.zendesk_url.tooltip',
            ]
        );
        $builder->add(
            'email',
            'email',
            [
                'label' => 'orocrm.zendesk.zendeskresttransport.email.label',
                'tooltip' => 'orocrm.zendesk.form.email.tooltip',
                'required' => true,
            ]
        );
        $builder->add(
            'token',
            'text',
            [
                'label' => 'orocrm.zendesk.zendeskresttransport.token.label',
                'tooltip' => 'orocrm.zendesk.form.token.tooltip',
                'required' => true
            ]
        );
        $builder->add(
            'zendeskUserEmail',
            'text',
            [
                'label' => 'orocrm.zendesk.zendeskresttransport.zendesk_user_email.label',
                'tooltip' => 'orocrm.zendesk.form.zendesk_user_email.tooltip',
                'required' => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => 'OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
