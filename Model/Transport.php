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
    public function get($action, $params = array())
    {
        //$client = StaticClient::get()
    }

    /**
     * @param string $action Example users/create_many.json
     * @param array $params
     * @return array
     */
    public function post($action, $params = array())
    {

    }

    /**
     * @param string $action Example users/12.json
     * @param array $params
     * @return array
     */
    public function delete($action, $params = array())
    {

    }

    /**
     * @param string $action Example users/12.json
     * @param array $params
     * @return array
     */
    public function put($action, $params = array())
    {

    }
}
