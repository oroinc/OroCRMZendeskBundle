<?php

namespace Oro\Bundle\ZendeskBundle\Twig;

use Guzzle\Http\Url;

use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Exception\ConfigurationException;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;

class ZendeskExtension extends \Twig_Extension
{
    /**
     * @var OroEntityProvider
     */
    protected $oroProvider;

    /**
     * @param OroEntityProvider $oroProvider
     * @param ZendeskEntityProvider $zendeskProvider
     */
    public function __construct(OroEntityProvider $oroProvider, ZendeskEntityProvider $zendeskProvider)
    {
        $this->oroProvider = $oroProvider;
        $this->zendeskProvider = $zendeskProvider;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'oro_zendesk_enabled_two_way_sync_channels',
                [$this->oroProvider, 'getEnabledTwoWaySyncChannels']
            ),
            new \Twig_SimpleFunction(
                'oro_zendesk_ticket_by_related_case',
                [$this->zendeskProvider, 'getTicketByCase']
            ),
            new \Twig_SimpleFunction('oro_zendesk_ticket_url', [$this, 'getTicketUrl']),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'oro_zendesk';
    }

    /**
     * @param Ticket $ticket
     * @return null|string
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
            $url = Url::factory($url);
            $scheme = $url->getScheme();

            if (empty($scheme)) {
                $url->setHost($url->getPath())
                    ->setPath('')
                    ->setScheme('https');
            }

            $url = (string)$url;

            if (empty($url)) {
                return null;
            }

            return $url . '/tickets/' . $ticket->getOriginId();
        } catch (ConfigurationException $exception) {
            return null;
        }
    }
}
