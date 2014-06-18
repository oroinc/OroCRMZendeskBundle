<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

interface RestClientFactoryInterface
{
    /**
     * @return RestClient
     */
    public function getRestClient();
}
