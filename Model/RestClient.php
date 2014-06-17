<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;

use OroCRM\Bundle\ZendeskBundle\Exception\BadRequestException;
use OroCRM\Bundle\ZendeskBundle\Exception\BadResponseException;

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
     * @throws BadRequestException
     * @throws BadResponseException
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
     * @throws BadRequestException
     * @throws BadResponseException
     * @return array
     */
    public function post($action, $data)
    {
        $url = $this->getUrl($action, array());
        return $this->performRequest($url, 'post', $data);
    }

    /**
     * @param string $action Example users/12.json
     * @throws BadRequestException
     * @throws BadResponseException
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
     * @throws BadRequestException
     * @throws BadResponseException
     * @return array
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
     * @throws BadRequestException
     * @throws BadResponseException
     * @return array
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
        } catch (RequestException $exception) {
            throw new BadRequestException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            throw new BadResponseException('Incorrect status code', $statusCode);
        }

        $body = $response->getBody(true);

        return json_decode($body, true);
    }
}
