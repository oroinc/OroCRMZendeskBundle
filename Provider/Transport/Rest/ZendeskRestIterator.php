<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider\Transport\Rest;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\AbstractRestIterator;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;

class ZendeskRestIterator extends AbstractRestIterator
{
    /**
     * @var string
     */
    protected $resource;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var string
     */
    protected $dataKeyName;

    /**
     * @var string|null
     */
    protected $nextPageUrl;

    /**
     * @param RestClientInterface $client
     * @param string $resource
     * @param string $dataKeyName
     * @param array $params
     */
    public function __construct(RestClientInterface $client, $resource, $dataKeyName, array $params = [])
    {
        parent::__construct($client);
        $this->resource = $resource;
        $this->dataKeyName = $dataKeyName;
        $this->params = $params;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadPage(RestClientInterface $client)
    {
        $result = null;

        if (!$this->firstLoaded) {
            $result = $client->get($this->resource, $this->params);
        } elseif ($this->nextPageUrl) {
            $result = $this->client->get($this->nextPageUrl);
        }

        if (isset($result['next_page'])) {
            $this->nextPageUrl = (string)$result['next_page'];
        } else {
            $this->nextPageUrl = null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRowsFromPageData(array $data)
    {
        if (isset($data[$this->dataKeyName]) && is_array($data[$this->dataKeyName])) {
            return $data[$this->dataKeyName];
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getTotalCountFromPageData(array $data, $previousValue)
    {
        if (isset($data['count'])) {
            return (int)$data['count'];
        } else {
            return $previousValue;
        }
    }
}
