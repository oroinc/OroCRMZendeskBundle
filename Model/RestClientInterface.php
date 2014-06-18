<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use OroCRM\Bundle\ZendeskBundle\Exception\BadResponseException;

interface RestClientInterface
{
    /**
     * @param string $action Example tickets.json | full url
     * @param array $params
     * @return array
     * @throws BadResponseException
     */
    public function get($action, $params = array());

    /**
     * @param string $action Example users/create_many.json
     * @param mixed $data
     * @return array
     * @throws BadResponseException
     */
    public function post($action, $data);

    /**
     * @param string $action Example users/12.json
     * @return array
     * @throws BadResponseException
     */
    public function delete($action);

    /**
     * @param string $action Example users/12.json
     * @param mixed $data
     * @return array
     * @throws BadResponseException
     */
    public function put($action, $data);
}
