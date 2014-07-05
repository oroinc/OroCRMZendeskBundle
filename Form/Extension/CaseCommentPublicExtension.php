<?php

namespace OroCRM\Bundle\ZendeskBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use OroCRM\Bundle\CaseBundle\Entity\CaseComment;

class CaseCommentPublicExtension extends AbstractTypeExtension
{
    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskProvider;

    /**
     * @param ZendeskEntityProvider $zendeskProvider
     */
    public function __construct(ZendeskEntityProvider $zendeskProvider)
    {
        $this->zendeskProvider = $zendeskProvider;
    }

    /**
     * Enable public field for case comment if it's connected to Zendesk
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $caseComment = $form->getData();

        if ($caseComment && $this->isLinkedToZendesk($caseComment)) {
            $view->vars['public_field_hidden'] = false;
        }
    }

    /**
     * Checks if case comment linked to Zendesk.
     *
     * @param CaseComment $caseComment
     * @return bool
     */
    protected function isLinkedToZendesk(CaseComment $caseComment)
    {
        if (!$caseComment->getCase()) {
            return false;
        }

        return null !== $this->zendeskProvider->getTicketByCase($caseComment->getCase());
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'orocrm_case_comment';
    }
}
