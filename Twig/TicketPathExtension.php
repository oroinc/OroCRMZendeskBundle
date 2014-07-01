<?php

namespace OroCRM\Bundle\ZendeskBundle\Twig;

use Guzzle\Http\Url;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use OroCRM\Bundle\ZendeskBundle\Exception\ConfigurationException;

class TicketPathExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('orocrm_zendesk_ticket_path', array($this, 'getTicketViewPath')),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'orocrm_zendesk_ticket_path';
    }

    /**
     * @param Ticket $ticket
     * @return null|string
     */
    public function getTicketViewPath(Ticket $ticket)
    {
        try {
            /**
             * @var ZendeskRestTransport $transport
             */
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
