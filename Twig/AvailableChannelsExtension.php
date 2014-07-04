<?php

namespace OroCRM\Bundle\ZendeskBundle\Twig;

use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;

class AvailableChannelsExtension extends \Twig_Extension
{

    /**
     * @var OroEntityProvider
     */
    protected $provider;

    public function __construct(OroEntityProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('orocrm_zendesk_get_available_channels', array($this, 'getChannels')),
        );
    }

    public function getChannels()
    {
        return $this->provider->getAvailableChannels();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'orocrm_zendesk_available_channels';
    }
}
