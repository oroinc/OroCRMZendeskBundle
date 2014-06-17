<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;

use OroCRM\Bundle\ZendeskBundle\Exception\BadResponse;

class RestClient
{
    const BASE_URL = '.zendesk.com/api/v2/';

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client, array $zendeskSettings)
    {
        $this->client = $client;
        $this->settings = $zendeskSettings;
    }

    /**
     * @param string $action Example tickets.json | full url
     * @param array $params
     * @return array
     */
    public function get($action, $params = array())
    {
        $url = filter_var($action, FILTER_VALIDATE_URL) ? $action : $this->getUrl($action, $params);

        return $this->performRequest($url, 'get');
    }

    /**
     * @param string $action Example users/create_many.json
     * @param mixed $data
     * @return array
     */
    public function post($action, $data)
    {
        $url = $this->getUrl($action, array());
        return $this->performRequest($url, 'post', $data);
    }

    /**
     * @param string $action Example users/12.json
     * @return array
     */
    public function delete($action)
    {
        $url = $this->getUrl($action, array());
        return $this->performRequest($url, 'delete');
    }

    /**
     * @param string $action Example users/12.json
     * @param mixed $data
     * @return array
     */
    public function put($action, $data)
    {
        $url = $this->getUrl($action, array());
        return $this->performRequest($url, 'put', $data);
    }

    /**
     * @param $action
     * @param $params
     * @return string
     */
    protected function getUrl($action, $params)
    {
        $query = new QueryString();
        foreach ($params as $name => $value) {
            $query->set($name, $value);
        }

        $path = $this->settings['sub_domain'] . static::BASE_URL . $action;
        $url = Url::buildUrl(array('scheme' => 'https', 'host' => $path, 'query' => $query));

        return $url;
    }

    /**
     * @param string     $url
     * @param string     $method
     * @param null|mixed $data
     * @throws \OroCRM\Bundle\ZendeskBundle\Exception\BadResponse
     * @return string
     */
    protected function performRequest($url, $method, $data = null)
    {
        $email = $this->settings['email'];
        $token = $this->settings['api_token'];

        try {
            $request = $this->client->createRequest(
                $method,
                $url,
                null,
                $data,
                array('auth' => array("{$email}/token", $token))
            );

            $response = $request->send();
        } catch (GuzzleException $e) {
            throw new BadResponse();
        }


        if ($response->getStatusCode() != 200) {
            throw new BadResponse();
        }

        $body = $response->getBody(true);

        return json_encode($body);
    }
}
