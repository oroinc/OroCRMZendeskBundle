<?php

namespace Oro\Bundle\ZendeskBundle\Twig;

use GuzzleHttp\Psr7\Uri;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\ConfigurationException;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to display Zendesk-related information:
 *   - oro_zendesk_enabled_two_way_sync_channels
 *   - oro_zendesk_ticket_by_related_case
 */
class ZendeskExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return OroEntityProvider
     */
    protected function getOroEntityProvider()
    {
        return $this->container->get('oro_zendesk.entity_provider.oro');
    }

    /**
     * @return ZendeskEntityProvider
     */
    protected function getZendeskEntityProvider()
    {
        return $this->container->get('oro_zendesk.entity_provider.zendesk');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_zendesk_enabled_two_way_sync_channels',
                [$this, 'getEnabledTwoWaySyncChannels']
            ),
            new TwigFunction(
                'oro_zendesk_ticket_by_related_case',
                [$this, 'getTicketByCase']
            ),
            new TwigFunction('oro_zendesk_ticket_url', [$this, 'getTicketUrl']),
        ];
    }

    /**
     * @return Channel[]
     */
    public function getEnabledTwoWaySyncChannels()
    {
        return $this->getOroEntityProvider()->getEnabledTwoWaySyncChannels();
    }

    /**
     * @param CaseEntity $caseEntity
     *
     * @return Ticket|null
     */
    public function getTicketByCase(CaseEntity $caseEntity)
    {
        return $this->getZendeskEntityProvider()->getTicketByCase($caseEntity);
    }

    /**
     * @param Ticket $ticket
     *
     * @return string|null
     */
    public function getTicketUrl(Ticket $ticket)
    {
        try {
            if (!$ticket->getChannel() || !$ticket->getOriginId()) {
                return null;
            }
            /** @var ZendeskRestTransport $transport */
            $transport = $ticket->getChannel()->getTransport();

            $url = $transport->getUrl();
            $uri = new Uri($url);
            $scheme = $uri->getScheme();

            if (empty($scheme)) {
                $uri = Uri::fromParts(['scheme' => 'https', 'host' => $uri->getPath()]);
            }

            $url = (string)$uri;

            if (empty($url)) {
                return null;
            }

            return $url . '/tickets/' . $ticket->getOriginId();
        } catch (ConfigurationException $exception) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_zendesk.entity_provider.oro' => OroEntityProvider::class,
            'oro_zendesk.entity_provider.zendesk' => ZendeskEntityProvider::class,
        ];
    }
}
