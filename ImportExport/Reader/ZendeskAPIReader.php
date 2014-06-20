<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Reader;

use OroCRM\Bundle\ZendeskBundle\Model\RestClientFactoryInterface;
use OroCRM\Bundle\ZendeskBundle\Model\RestIterator;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;

class ZendeskAPIReader extends IteratorBasedReader
{
    /**
     * @var RestClientFactoryInterface
     */
    protected $clientFactory;

    /**
     * @param ContextRegistry $contextRegistry
     * @param RestClientFactoryInterface $clientFactory
     */
    public function __construct(ContextRegistry $contextRegistry, RestClientFactoryInterface $clientFactory)
    {
        parent::__construct($contextRegistry);
        $this->clientFactory = $clientFactory;
    }

    /**
     * @param ContextInterface $context
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->setSourceIterator($this->createSourceIterator($context));
    }

    /**
     * @param ContextInterface $context
     * @return RestIterator
     * @throws InvalidConfigurationException
     */
    protected function createSourceIterator(ContextInterface $context)
    {
        if (!$context->hasOption('resource')) {
            throw new InvalidConfigurationException(
                'Configuration must contain "resource" parameter.'
            );
        }

        $resource = $context->getOption('resource');
        $params = $context->getOption('params', array());

        $client = $this->clientFactory->getRestClient();

        $result = new RestIterator($client, $resource, $params);
        if ($context->hasOption('readerDataKeyName')) {
            $result->setDataKeyName($context->getOption('readerDataKeyName'));
        }
        return $result;
    }
}
