<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Enum\OAuth\TranslationKey;
use Oro\Bundle\ZendeskBundle\Model\OAuthManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles Zendesk OAuth authorization flow in a modal.
 *
 * @Route("/zendesk/oauth")
 */
class OAuthController extends AbstractController
{
    private const TEMPLATE_CALLBACK = '@OroZendesk/OAuth/callback_popup.html.twig';
    private const MESSAGE_TYPE_SUCCESS = 'success';
    private const MESSAGE_TYPE_ERROR = 'error';

    /**
     * OAuth callback endpoint - receives authorization code from Zendesk and exchanges it for access token.
     * This endpoint handles the OAuth 2.0 callback after user grants/denies permission.
     *
     * @Route("/callback", name="oro_zendesk_oauth_callback", methods={"GET"})
     */
    public function callbackAction(Request $request): Response
    {
        $translator = $this->container->get(TranslatorInterface::class);
        $doctrine = $this->container->get(ManagerRegistry::class);
        $logger = $this->container->get(LoggerInterface::class);

        $authorizationCode = $request->query->get('code');
        $transportId = $this->extractTransportId($request);
        $oauthError = $request->query->get('error');
        $errorDescription = $request->query->get('error_description');

        if ($oauthError) {
            return $this->renderErrorCallbackResponse(
                $translator->trans(TranslationKey::USER_DENIED->value, [
                    '%error%' => $errorDescription ?: $oauthError
                ])
            );
        }

        if (!$authorizationCode || !$transportId) {
            $logger->warning('Invalid OAuth callback request', [
                'has_code' => null !== $authorizationCode,
                'has_state' => null !== $transportId,
            ]);

            return $this->renderErrorCallbackResponse(
                $translator->trans(TranslationKey::INVALID_REQUEST->value)
            );
        }

        $transport = $doctrine->getRepository(ZendeskRestTransport::class)->find($transportId);
        if (!$transport) {
            $logger->error('OAuth callback: transport not found', ['transport_id' => $transportId]);

            return $this->renderErrorCallbackResponse(
                $translator->trans(TranslationKey::TRANSPORT_NOT_FOUND->value)
            );
        }

        return $this->processTokenExchange($transport, $authorizationCode);
    }

    /**
     * Initiates OAuth authorization flow by redirecting user to Zendesk consent page.
     *
     * @Route(
     *     "/authorize/{id}",
     *     name="oro_zendesk_oauth_authorize",
     *     requirements={"id"="\d+"},
     *     methods={"GET"}
     * )
     */
    public function authorizeAction(int $id): Response|RedirectResponse
    {
        $translator = $this->container->get(TranslatorInterface::class);
        $doctrine = $this->container->get(ManagerRegistry::class);
        $logger = $this->container->get(LoggerInterface::class);
        $oauthManager = $this->container->get(OAuthManagerInterface::class);

        $transport = $doctrine->getRepository(ZendeskRestTransport::class)->find($id);

        if (!$transport) {
            $logger->error('OAuth authorize: transport not found', ['transport_id' => $id]);

            return $this->renderErrorCallbackResponse(
                $translator->trans(TranslationKey::TRANSPORT_NOT_FOUND->value)
            );
        }

        try {
            $state = (string) $transport->getId();
            $authorizeUrl = $oauthManager->generateAuthorizeUrl($transport, $state);

            $logger->info('Redirecting to Zendesk OAuth authorization', [
                'transport_id' => $id,
                'url' => $authorizeUrl,
            ]);

            return new RedirectResponse($authorizeUrl);
        } catch (\Exception $e) {
            $logger->error('Failed to generate OAuth authorization URL', [
                'transport_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return $this->renderErrorCallbackResponse(
                $translator->trans(
                    TranslationKey::AUTHORIZE_FAILED->value,
                    ['%error%' => $e->getMessage()]
                )
            );
        }
    }

    /**
     * Extracts and validates transport ID from request state parameter.
     */
    private function extractTransportId(Request $request): ?int
    {
        $state = $request->query->get('state');
        if (null === $state || '' === $state) {
            return null;
        }

        $transportId = filter_var($state, FILTER_VALIDATE_INT);

        return false !== $transportId
            ? $transportId
            : null;
    }

    /**
     * Processes OAuth token exchange with Zendesk.
     */
    private function processTokenExchange(
        ZendeskRestTransport $transport,
        string $authorizationCode
    ): Response {
        $oauthManager = $this->container->get(OAuthManagerInterface::class);
        $logger = $this->container->get(LoggerInterface::class);
        $translator = $this->container->get(TranslatorInterface::class);
        $transportId = $transport->getId();

        try {
            $oauthManager->exchangeAuthorizationCode($transport, $authorizationCode);
            $logger->info('OAuth token exchange successful', ['transport_id' => $transportId]);

            return $this->render(self::TEMPLATE_CALLBACK, [
                'success' => true,
                'message' => $translator->trans(TranslationKey::SUCCESS_CONNECTED->value),
                'messageType' => self::MESSAGE_TYPE_SUCCESS,
            ]);
        } catch (\RuntimeException $e) {
            $logger->error('OAuth token exchange failed', [
                'transport_id' => $transportId,
                'exception' => $e->getMessage(),
            ]);

            return $this->renderErrorCallbackResponse(
                $translator->trans(
                    TranslationKey::EXCHANGE_FAILED->value,
                    ['%error%' => $e->getMessage()]
                )
            );
        } catch (\Exception $e) {
            $logger->critical('Unexpected error during OAuth token exchange', [
                'transport_id' => $transportId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->renderErrorCallbackResponse(
                $translator->trans(TranslationKey::GENERAL_ERROR->value)
            );
        }
    }

    /**
     * Renders error callback response with translated message.
     */
    private function renderErrorCallbackResponse(string $message): Response
    {
        return $this->render(self::TEMPLATE_CALLBACK, [
            'success' => false,
            'message' => $message,
            'messageType' => self::MESSAGE_TYPE_ERROR,
        ]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                OAuthManagerInterface::class,
                LoggerInterface::class,
                ManagerRegistry::class,
            ]
        );
    }
}
