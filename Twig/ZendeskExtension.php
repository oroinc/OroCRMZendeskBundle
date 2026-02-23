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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_zendesk_enabled_two_way_sync_channels', [$this, 'getEnabledTwoWaySyncChannels']),
            new TwigFunction('oro_zendesk_ticket_by_related_case', [$this, 'getTicketByCase']),
            new TwigFunction('oro_zendesk_ticket_url', [$this, 'getTicketUrl'])
        ];
    }

    /**
     * @return Channel[]
     */
    public function getEnabledTwoWaySyncChannels(): array
    {
        return $this->getOroEntityProvider()->getEnabledTwoWaySyncChannels();
    }

    public function getTicketByCase(CaseEntity $caseEntity): ?Ticket
    {
        return $this->getZendeskEntityProvider()->getTicketByCase($caseEntity);
    }

    public function getTicketUrl(Ticket $ticket): ?string
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            OroEntityProvider::class,
            ZendeskEntityProvider::class
        ];
    }

    private function getOroEntityProvider(): OroEntityProvider
    {
        return $this->container->get(OroEntityProvider::class);
    }

    private function getZendeskEntityProvider(): ZendeskEntityProvider
    {
        return $this->container->get(ZendeskEntityProvider::class);
    }
}
