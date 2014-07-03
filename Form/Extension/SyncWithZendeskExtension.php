<?php

namespace OroCRM\Bundle\ZendeskBundle\Form\Extension;

use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;

class SyncWithZendeskExtension extends AbstractTypeExtension
{
    const SYNC_WITH_ZENDESK_FIELD = 'syncWithZendesk';
    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskEntityProvider;
    /**
     * @var OroEntityProvider
     */
    private $oroEntityProvider;

    /**
     * @param ZendeskEntityProvider $zendeskEntityProvider
     * @param OroEntityProvider $oroEntityProvider
     */
    public function __construct(ZendeskEntityProvider $zendeskEntityProvider, OroEntityProvider $oroEntityProvider)
    {
        $this->zendeskEntityProvider = $zendeskEntityProvider;
        $this->oroEntityProvider = $oroEntityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $availableChannels = $this->oroEntityProvider->getAvailableChannels();

        $choicesList = array();

        /**
         * @var Channel $channel
         */
        foreach ($availableChannels as $channel) {
            $choicesList[$channel->getId()] = $channel->getName();
        }

        $builder->add(
            self::SYNC_WITH_ZENDESK_FIELD,
            'choice',
            array(
                'label'       => 'orocrm.zendesk.form.sync_to_zendesk.label',
                'mapped'      => false,
                'required'    => false,
                'empty_value' => 'Not sync',
                'choices'     => $choicesList
            )
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /**
         * @var CaseEntity $data
         */
        $data = $form->getData();
        if ($this->zendeskEntityProvider->getTicketByCase($data)) {
            $form->remove(self::SYNC_WITH_ZENDESK_FIELD);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'orocrm_case_entity';
    }
}
