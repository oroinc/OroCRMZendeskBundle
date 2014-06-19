<?php

namespace OroCRM\Bundle\ZendeskBundle\Twig;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Exception\ConfigurationException;
use OroCRM\Bundle\ZendeskBundle\Provider\ConfigurationProvider;

class TicketPathExtension extends \Twig_Extension
{
    /**
     * @var ConfigurationProvider
     */
    protected $configurationProvider;

    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

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
            $url = $this->configurationProvider->getZendeskUrl();

            if (empty($url)) {
                return null;
            }

            return $url . '/tickets/' . $ticket->getOriginId();
        } catch (ConfigurationException $exception) {
            return null;
        }
    }
}
