<?php

namespace Oro\Bundle\ZendeskBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;

class SyncWithZendeskExtension extends AbstractTypeExtension
{
    const ZENDESK_CHANNEL_FIELD = 'syncWithZendesk';

    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskProvider;

    /**
     * @var OroEntityProvider
     */
    private $oroProvider;

    /**
     * @param ZendeskEntityProvider $zendeskProvider
     * @param OroEntityProvider $oroProvider
     */
    public function __construct(ZendeskEntityProvider $zendeskProvider, OroEntityProvider $oroProvider)
    {
        $this->zendeskProvider = $zendeskProvider;
        $this->oroProvider = $oroProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $channels = $this->oroProvider->getEnabledTwoWaySyncChannels();

        if (!$channels) {
            return;
        }

        $choices = array();

        foreach ($channels as $channel) {
            $choices[$channel->getId()] = $channel->getName();
        }

        $builder->add(
            self::ZENDESK_CHANNEL_FIELD,
            'choice',
            array(
                'label'       => 'oro.zendesk.form.sync_to_zendesk.label',
                'mapped'      => false,
                'required'    => false,
                'empty_value' => 'oro.zendesk.form.sync_to_zendesk.empty',
                'choices'     => $choices
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var CaseEntity $data */
        $data = $form->getData();
        if ($data->getId() && $this->zendeskProvider->getTicketByCase($data)) {
            $form->remove(self::ZENDESK_CHANNEL_FIELD);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_case_entity';
    }
}
