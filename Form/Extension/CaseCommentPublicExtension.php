<?php

namespace Oro\Bundle\ZendeskBundle\Form\Extension;

use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\CaseBundle\Form\Type\CaseCommentType;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CaseCommentPublicExtension extends AbstractTypeExtension
{
    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskProvider;

    public function __construct(ZendeskEntityProvider $zendeskProvider)
    {
        $this->zendeskProvider = $zendeskProvider;
    }

    /**
     * Enable public field for case comment if it's connected to Zendesk
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
    public static function getExtendedTypes(): iterable
    {
        return [CaseCommentType::class];
    }
}
