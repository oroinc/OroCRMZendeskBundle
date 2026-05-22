<?php

namespace Oro\Bundle\ZendeskBundle\Form\Type;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;
use Oro\Bundle\ZendeskBundle\Model\AuthorizationTypeCredentialsCleaner;
use Oro\Bundle\ZendeskBundle\Model\OAuth\OAuthCallbackUrlProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for Zendesk REST transport settings configuration with OAuth.
 */
class RestTransportSettingsFormType extends AbstractType
{
    public const NAME = 'oro_zendesk_rest_transport_setting_form_type';
    private const CSS_CLASS_AUTH_TYPE_EMAIL_TOKEN = 'auth email-token';
    private const CSS_CLASS_AUTH_TYPE_OAUTH = 'auth oauth';
    private const AUTHORIZATION_TYPE_COMPONENT = 'orozendesk/js/app/components/zendesk-authorization-type-component';

    public function __construct(
        private readonly ?OAuthCallbackUrlProviderInterface $callbackUrlProvider = null,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'url',
            UrlType::class,
            [
                'label' => 'oro.zendesk.zendeskresttransport.url.label',
                'required' => true,
                'tooltip' => 'oro.zendesk.form.zendesk_url.description',
            ]
        );

        $builder->add(
            'authorizationType',
            ChoiceType::class,
            [
                'label' => 'oro.zendesk.zendeskresttransport.authorization_type.label',
                'required' => true,
                'choices' => [
                    'oro.zendesk.zendeskresttransport.authorization_type.oauth' => AuthorizationType::OAUTH,
                    'oro.zendesk.zendeskresttransport.authorization_type.email_token' => AuthorizationType::EMAIL_TOKEN,
                ],
                'choice_value' => static fn (?AuthorizationType $authType): ?string => $authType?->value,
                'attr' => ['class' => 'authorization-type'],
            ]
        );

        $this->addOAuthFields($builder);

        $this->addEmailTokenFields($builder);

        $builder->add(
            'zendeskUserEmail',
            TextType::class,
            [
                'label' => 'oro.zendesk.zendeskresttransport.zendesk_user_email.label',
                'tooltip' => 'oro.zendesk.form.zendesk_user_email.description',
                'required' => true,
            ]
        );

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => ZendeskRestTransport::class]);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr']['data-page-component-module'] = self::AUTHORIZATION_TYPE_COMPONENT;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * @inheritdoc
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * Remove irrelevant credential fields before validation based on authorization type.
     */
    public function onSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        if (!$data instanceof ZendeskRestTransport) {
            return;
        }

        $form = $event->getForm();
        $authorizationType = $data->getAuthorizationType();
        if (null === $authorizationType) {
            return;
        }

        $fieldsToRemove = $authorizationType->isOAuth()
            ? ['email', 'token']
            : ['oauthClientId', 'oauthCallbackUrl'];

        foreach ($fieldsToRemove as $field) {
            $form->remove($field);
        }
    }

    /**
     * Clean credentials based on authorization type after submission.
     */
    public function onPostSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        if (!$data instanceof ZendeskRestTransport) {
            return;
        }

        AuthorizationTypeCredentialsCleaner::clearByAuthorizationType($data);
    }

    private function addOAuthFields(FormBuilderInterface $builder): void
    {
        $builder->add(
            'oauthClientId',
            TextType::class,
            [
                'label' => 'oro.zendesk.zendeskresttransport.oauth_client_id.label',
                'required' => true,
                'constraints' => [new NotBlank()],
                'attr' => ['class' => self::CSS_CLASS_AUTH_TYPE_OAUTH],
            ]
        );
        $builder->add(
            'oauthCallbackUrl',
            TextType::class,
            [
                'label' => 'oro.zendesk.oauth.oauth_callback_url.label',
                'tooltip' => 'oro.zendesk.oauth.oauth_callback_url.tooltip',
                'mapped' => false,
                'required' => false,
                'data' => $this->callbackUrlProvider?->getCallbackUrl(),
                'attr' => [
                    'class' => self::CSS_CLASS_AUTH_TYPE_OAUTH,
                    'readonly' => true,
                ],
            ]
        );
    }

    private function addEmailTokenFields(FormBuilderInterface $builder): void
    {
        $builder->add(
            'email',
            EmailType::class,
            [
                'label' => 'oro.zendesk.zendeskresttransport.email.label',
                'required' => true,
                'constraints' => [new NotBlank()],
                'attr' => ['class' => self::CSS_CLASS_AUTH_TYPE_EMAIL_TOKEN],
            ]
        );
        $builder->add(
            'token',
            TextType::class,
            [
                'label' => 'oro.zendesk.zendeskresttransport.token.label',
                'required' => true,
                'constraints' => [new NotBlank()],
                'attr' => ['class' => self::CSS_CLASS_AUTH_TYPE_EMAIL_TOKEN],
            ]
        );
    }
}
