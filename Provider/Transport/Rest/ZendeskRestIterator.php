<?php

namespace Oro\Bundle\ZendeskBundle\Provider\Transport\Rest;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\AbstractRestIterator;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Data iterator for the zendesk integration.
 */
class ZendeskRestIterator extends AbstractRestIterator
{
    protected string $resource;
    protected array $params;
    protected string $dataKeyName;
    protected ?string $nextPageUrl = null;
    protected ?DenormalizerInterface $denormalizer = null;
    protected string $itemType = '';
    protected array $denormalizeContext = [];

    public function __construct(RestClientInterface $client, string $resource, string $dataKeyName, array $params = [])
    {
        parent::__construct($client);

        $this->resource = $resource;
        $this->dataKeyName = $dataKeyName;
        $this->params = $params;
    }

    #[\Override]
    protected function loadPage(RestClientInterface $client)
    {
        $data = null;

        if (!$this->firstLoaded) {
            $data = $client->getJSON($this->resource, $this->params);
        } elseif ($this->nextPageUrl) {
            $data = $this->client->getJSON($this->nextPageUrl);
        }

        if (isset($data['next_page'])) {
            $this->nextPageUrl = (string)$data['next_page'];
        } else {
            $this->nextPageUrl = null;
        }

        return $data;
    }

    #[\Override]
    protected function getRowsFromPageData(array $data)
    {
        if (isset($data[$this->dataKeyName]) && is_array($data[$this->dataKeyName])) {
            return $data[$this->dataKeyName];
        }

        return null;
    }

    #[\Override]
    protected function getTotalCountFromPageData(array $data, $previousValue)
    {
        if (isset($data['count'])) {
            return (int)$data['count'];
        }

        return $previousValue;
    }

    #[\Override]
    public function current(): mixed
    {
        $result = parent::current();

        if ($result !== null && $this->denormalizer) {
            $result = $this->denormalizer->denormalize($result, $this->itemType, '', $this->denormalizeContext);
        }

        return $result;
    }

    public function setupDenormalization(DenormalizerInterface $denormalizer, string $type, array $context = []): void
    {
        $this->denormalizer = $denormalizer;
        $this->itemType = $type;
        $this->denormalizeContext = $context;
    }
}
