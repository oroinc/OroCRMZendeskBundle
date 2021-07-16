<?php

namespace Oro\Bundle\ZendeskBundle\Form\Extension;

use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\CaseBundle\Form\Type\CaseEntityType;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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
            $choices[$channel->getName()] = $channel->getId();
        }

        $builder->add(
            self::ZENDESK_CHANNEL_FIELD,
            ChoiceType::class,
            array(
                'label'       => 'oro.zendesk.form.sync_to_zendesk.label',
                'mapped'      => false,
                'required'    => false,
                'placeholder' => 'oro.zendesk.form.sync_to_zendesk.empty',
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
    public static function getExtendedTypes(): iterable
    {
        return [CaseEntityType::class];
    }
}
