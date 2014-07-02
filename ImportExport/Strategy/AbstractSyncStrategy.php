<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Util\ClassUtils;

use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\OroEntityProvider;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\ZendeskEntityProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

abstract class AbstractSyncStrategy implements StrategyInterface, ContextAwareInterface
{
    /**
     * @var PropertyAccessor
     */
    static private $propertyAccessor;

    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskProvider;

    /**
     * @var OroEntityProvider
     */
    protected $oroEntityProvider;

    /**
     * @var ConnectorContextMediator
     */
    protected $connectorContextMediator;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var SyncLogger
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
     * @param ZendeskEntityProvider $zendeskProvider
     */
    public function setZendeskProvider(ZendeskEntityProvider $zendeskProvider)
    {
        $this->zendeskProvider = $zendeskProvider;
    }

    /**
     * @return OroEntityProvider
     */
    public function getOroEntityProvider()
    {
        return $this->oroEntityProvider;
    }

    /**
     * @param OroEntityProvider $oroEntityProvider
     */
    public function setOroEntityProvider(OroEntityProvider $oroEntityProvider)
    {
        $this->oroEntityProvider = $oroEntityProvider;
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
     * Sync properties of $target object with $source object
     *
     * @param mixed $target
     * @param mixed $source
     * @param array $excludeProperties
     * @throws InvalidArgumentException
     */
    protected function syncProperties($target, $source, array $excludeProperties = array())
    {
        if (!is_object($target)) {
            throw new InvalidArgumentException(
                'Expect argument $target has object type object but %s given.',
                gettype($target)
            );
        }

        if (!is_object($source)) {
            throw new InvalidArgumentException(
                'Expect argument $target has object type object but %s given.',
                gettype($source)
            );
        }

        $targetClass = ClassUtils::getRealClass($target);
        $sourceClass = ClassUtils::getRealClass($source);
        if ($targetClass !== $sourceClass) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expect argument $sourceClass is instance of %s but %s given.',
                    $targetClass,
                    $sourceClass
                )
            );
        }

        $reflectionClass = new \ReflectionClass($targetClass);

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();
            if (in_array($propertyName, $excludeProperties)) {
                continue;
            }
            $this->getPropertyAccessor()->setValue(
                $target,
                $propertyName,
                $this->getPropertyAccessor()->getValue($source, $propertyName)
            );
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return self::$propertyAccessor;
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
     * @return SyncLogger
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = new SyncLogger($logger);
    }
}
