<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use Guzzle\Http\StaticClient;

use OroCRM\Bundle\ZendeskBundle\Provider\ConfigurationProvider;

class Transport
{
    /**
     * @var ConfigurationProvider
     */
    private $provider;

    public function __construct(ConfigurationProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param string $action Example tickets.json
     * @param array $params
     * @return array
     */
    public function call($action, $params = array())
    {
        //$client = StaticClient::get()
    }
}
