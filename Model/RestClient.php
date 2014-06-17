<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;
use OroCRM\Bundle\ZendeskBundle\Exception\RestException;

class RestClient implements RestClientInterface
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
     * @throws RestException
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
     * @throws RestException
     */
    public function post($action, $data)
    {
        $url = $this->getUrl($action, array());
        return $this->performRequest($url, 'post', $data);
    }

    /**
     * @param string $action Example users/12.json
     * @return array
     * @throws RestException
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
     * @throws RestException
     */
    public function put($action, $data)
    {
        $url = $this->getUrl($action, array());
        return $this->performRequest($url, 'put', $data);
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
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
     * @return string
     * @throws RestException
     */
    protected function performRequest($url, $method, $data = null)
    {
        $email = $this->settings['email'];
        $token = $this->settings['api_token'];

        try {
            $headers = null;
            if ($data) {
                $headers = array('Content-Type' => 'application/json');
                $data = json_encode($data);
            }

            $request = $this->client->createRequest(
                $method,
                $url,
                $headers,
                $data,
                array('auth' => array("{$email}/token", $token))
            );

            $response = $request->send();
        } catch (GuzzleException $exception) {
            throw RestException::create(
                null,
                isset($request) ? $request : null,
                null,
                $exception
            );
        }

        if ($response->getStatusCode() >= 400 || $response->getStatusCode() < 200) {
            throw RestException::create(
                'Unexpected response status',
                $request,
                $response
            );
        }

        $body = $response->getBody(true);

        return json_decode($body, true);
    }
}
