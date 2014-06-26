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
            ['label' => 'orocrm.zendesk.zendeskresttransport.url.label', 'required' => true]
        );
        $builder->add(
            'email',
            'email',
            ['label' => 'orocrm.zendesk.zendeskresttransport.email.label', 'required' => true]
        );
        $builder->add(
            'token',
            'text',
            ['label' => 'orocrm.zendesk.zendeskresttransport.token.label', 'required' => true]
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
