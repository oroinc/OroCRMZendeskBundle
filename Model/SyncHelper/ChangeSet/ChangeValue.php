<?php

namespace Oro\Bundle\ZendeskBundle\Model\SyncHelper\ChangeSet;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Represents target property to be changed with source value.
 */
class ChangeValue
{
    /**
     * @var PropertyAccessor
     */
    private static $propertyAccessor;

    /**
     * @var mixed
     */
    protected $target;

    /**
     * @var string
     */
    protected $targetProperty;

    /**
     * @var string
     */
    protected $targetPropertyPath;

    /**
     * @var mixed
     */
    protected $targetValue;

    /**
     * @var mixed
     */
    protected $source;

    /**
     * @var string
     */
    protected $sourceProperty;

    /**
     * @var mixed
     */
    protected $sourceValue;

    /**
     * @var string
     */
    protected $compareProperty;

    /**
     * @param mixed $target
     * @param string|array $targetProperty This value is used to set value to $target, pass string or array:
     *          'targetProperty' or ['property' => 'targetProperty', 'path' => 'property.path', 'value' => optional]
     * @param mixed $source
     * @param string|array $sourceProperty This value is used to get value from $source, pass string or array:
     *          'sourceProperty' or ['property' => 'sourceProperty', 'path' => 'property.path', 'value' => optional]
     * @param string|null $compareProperty Used to compare target and source values
     */
    public function __construct($target, $targetProperty, $source, $sourceProperty, $compareProperty = null)
    {
        $this->target = $target;
        $this->source = $source;

        $this->parseTargetProperty($targetProperty);
        $this->parseSourceProperty($sourceProperty);

        $this->compareProperty = $compareProperty;
    }

    /**
     * @param mixed $targetProperty
     * @throws \InvalidArgumentException
     */
    protected function parseTargetProperty($targetProperty)
    {
        if (!is_array($targetProperty)) {
            $targetProperty = ['property' => $targetProperty];
        }
        $targetProperty = array_merge(['property' => null, 'path' => null], $targetProperty);
        if (!$targetProperty['property']) {
            throw new \InvalidArgumentException('Can\t parse $targetProperty.');
        }
        $this->targetProperty = $targetProperty['property'];
        $this->targetPropertyPath = $targetProperty['path'] ? $targetProperty['path'] : $this->targetProperty;
        $this->targetValue = array_key_exists('value', $targetProperty) ?
            $targetProperty['value'] : self::getPropertyAccessor()->getValue($this->target, $this->targetPropertyPath);
    }

    /**
     * @param mixed $sourceProperty
     * @throws \InvalidArgumentException
     */
    protected function parseSourceProperty($sourceProperty)
    {
        if (!is_array($sourceProperty)) {
            $sourceProperty = ['property' => $sourceProperty];
        }
        $sourceProperty = array_merge(['property' => null, 'path' => null], $sourceProperty);
        $this->sourceProperty = $sourceProperty['property'];

        if (array_key_exists('value', $sourceProperty)) {
            $this->sourceValue = $sourceProperty['value'];
        } elseif ($sourceProperty['path']) {
            $this->sourceValue = self::getPropertyAccessor()->getValue($this->source, $sourceProperty['path']);
        } elseif ($sourceProperty['property']) {
            $this->sourceValue = self::getPropertyAccessor()->getValue($this->source, $sourceProperty['property']);
        } else {
            throw new \InvalidArgumentException('Can\t parse $sourceValue.');
        }
    }

    /**
     * @return string
     */
    public function getTargetProperty()
    {
        return $this->targetProperty;
    }

    /**
     * @return mixed
     */
    public function getTargetValue()
    {
        return $this->targetValue;
    }

    /**
     * @return string
     */
    public function geSourceProperty()
    {
        return $this->sourceProperty;
    }

    /**
     * @return mixed
     */
    public function getSourceValue()
    {
        return $this->sourceValue;
    }

    /**
     * Apply changes to $target from source value
     */
    public function apply()
    {
        self::getPropertyAccessor()->setValue($this->target, $this->targetProperty, $this->sourceValue);
    }

    /**
     * Returns TRUE if has changes.
     *
     * @return bool
     */
    public function hasChange()
    {
        $target = $this->targetValue;
        $source = $this->sourceValue;

        if ($this->compareProperty && is_object($target) && is_object($source)) {
            $target = self::getPropertyAccessor()->getValue($target, $this->compareProperty);
            $source = self::getPropertyAccessor()->getValue($source, $this->compareProperty);
            if ($target === null && $source === null) {
                $target = $this->targetValue;
                $source = $this->sourceValue;
            }
        }

        return $target != $source;
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
