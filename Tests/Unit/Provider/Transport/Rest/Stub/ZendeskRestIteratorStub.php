<?php
declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest\Stub;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestIterator;
use Symfony\Component\Serializer\SerializerInterface;

class ZendeskRestIteratorStub extends ZendeskRestIterator
{
    public function xgetClient(): RestClientInterface
    {
        return $this->client;
    }

    public function xgetResource(): string
    {
        return $this->resource;
    }

    public function xgetParams(): array
    {
        return $this->params;
    }

    public function xgetDataKeyName(): string
    {
        return $this->dataKeyName;
    }

    public function xgetSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function xgetItemType(): string
    {
        return $this->itemType;
    }

    public function xgetDeserializeContext(): array
    {
        return $this->deserializeContext;
    }
}
