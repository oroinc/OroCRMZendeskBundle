<?php

namespace Oro\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Zendesk integration change set
 */
class ChangeSet implements \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * @var PropertyAccessor
     */
    private static $propertyAccessor;

    /**
     * @var ChangeValue[]
     */
    protected $values = [];

    /**
     * @var mixed
     */
    protected $target;

    /**
     * @var mixed
     */
    protected $source;

    /**
     * @param mixed $target
     * @param mixed $source
     */
    public function __construct($target, $source)
    {
        $this->target = $target;
        $this->source = $source;
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Adds value to change set if it has changes.
     *
     * @param string|array $targetProperty This value is used to set value to $target, pass string or array:
     *          'targetProperty' or ['property' => 'targetProperty', 'path' => 'property.path', 'value' => optional]
     * @param string|array|null $sourceProperty This value is used to get value from $source, pass string or array:
     *          'sourceProperty' or ['property' => 'sourceProperty', 'path' => 'property.path', 'value' => optional]
     * @param string|null $compareProperty Used to compare target and source values
     * @param bool $force Force changes
     * @return ChangeSet
     */
    public function add($targetProperty, $sourceProperty = null, $compareProperty = null, $force = false)
    {
        $sourceProperty = ($sourceProperty == null) ? $targetProperty : $sourceProperty;

        $changeValue = new ChangeValue(
            $this->target,
            $targetProperty,
            $this->source,
            $sourceProperty,
            $compareProperty
        );

        if ($force || $changeValue->hasChange()) {
            $this->values[$changeValue->getTargetProperty()] = $changeValue;
        }

        return $this;
    }

    /**
     * Apply change set
     *
     * @return bool
     */
    public function apply()
    {
        foreach ($this->values as $changeValue) {
            $changeValue->apply();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasChanges()
    {
        $result = false;
        foreach ($this->values as $changeValue) {
            if ($changeValue->hasChange()) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->values[$offset]) || array_key_exists($offset, $this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): ?ChangeValue
    {
        if (isset($this->values[$offset])) {
            return $this->values[$offset];
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof ChangeValue) {
            throw new \InvalidArgumentException(
                sprintf(
                    '$value must be an instance of %s, but %s given.',
                    'Oro\\Bundle\\ZendeskBundle\\Model\\SyncHelper\\ChangeSet\\ChangeValue',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }
        $this->values[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->values[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * @param mixed $target
     */
    protected function applyValue($target)
    {
        self::getPropertyAccessor()->setValue(
            $target,
            $this->getTargetProperty(),
            $this->getSourceValue()
        );
    }

    /**
     * @return PropertyAccessor
     */
    protected static function getPropertyAccessor()
    {
        if (!self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return self::$propertyAccessor;
    }
}
