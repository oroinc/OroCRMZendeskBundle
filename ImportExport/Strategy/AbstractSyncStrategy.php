<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use OroCRM\Bundle\ZendeskBundle\ImportExport\ImportExportLogger;
use OroCRM\Bundle\ZendeskBundle\ImportExport\SyncPropertiesHelper;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

abstract class AbstractSyncStrategy implements StrategyInterface, ContextAwareInterface
{
    /**
     * @var ConnectorContextMediator
     */
    private $connectorContextMediator;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var ImportExportLogger
     */
    private $logger;

    /**
     * @var Channel
     */
    private $channel = null;

    /**
     * Validates availability of origin id field
     *
     * @param mixed $entity
     * @return bool
     */
    public function validateOriginId($entity)
    {
        if (!$entity->getOriginId()) {
            $readPosition = $this->getContext()->getReadCount();
            $message = "Can't process record with empty id at read position $readPosition.";

            $this->getContext()->addError($message);
            $this->getLogger()->error($message);

            $this->getContext()->incrementErrorEntriesCount();

            return false;
        }

        return true;
    }

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        if ($this->channel === null) {
            $this->channel = $this->connectorContextMediator->getChannel($this->context);
        }

        return $this->channel;
    }

    /**
     * @param ConnectorContextMediator $connectorContextMediator
     */
    public function setConnectorContextMediator(ConnectorContextMediator $connectorContextMediator)
    {
        $this->connectorContextMediator = $connectorContextMediator;
    }

    /**
     * @param mixed $entity
     * @param string $fieldName
     * @param string $dictionaryEntityAlias
     * @param boolean $required
     */
    protected function refreshDictionaryField($entity, $fieldName, $dictionaryEntityAlias = null, $required = false)
    {
        $dictionaryEntityAlias = $dictionaryEntityAlias ? : $fieldName;
        $value = null;
        $entityGetter = 'get' . ucfirst($fieldName);
        $entitySetter = 'set' . ucfirst($fieldName);
        $providerGetter = 'get' . ucfirst($dictionaryEntityAlias);
        if ($entity->$entityGetter()) {
            $value = $this->zendeskProvider->$providerGetter($entity->$entityGetter());
            if (!$value) {
                $valueName = $entity->$entityGetter()->getName();
                $this->getLogger()->warning("Can't find Zendesk $fieldName [name=$valueName].");
            }
        } elseif ($required) {
            $this->getLogger()->warning("Zendesk $fieldName is empty.");
        }
        $entity->$entitySetter($value);
    }

    /**
     * @param ContextInterface $context
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @return ContextInterface
     */
    protected function getContext()
    {
        return $this->context;
    }

    /**
     * @return ImportExportLogger
     */
    protected function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new ImportExportLogger(new NullLogger());
        }
        $this->logger->setImportExportContext($this->getContext());
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = new ImportExportLogger($logger);
    }
}
