<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Base normalizer for zendesk entities.
 */
abstract class AbstractNormalizer implements
    SerializerAwareInterface,
    ContextAwareNormalizerInterface,
    ContextAwareDenormalizerInterface
{
    const SHORT_MODE = 'short';

    /**
     * @var PropertyAccessor
     */
    private static $propertyAccessor;

    /**
     * @var SerializerInterface|ContextAwareNormalizerInterface|ContextAwareDenormalizerInterface
     */
    protected $serializer;

    /**
     * @var array
     */
    private $fieldRules;

    /**
     * @var string
     */
    private $primaryField;

    /**
     * List of rules that declare (de)normalization, for example
     *
     * array(
     *  array(
     *      'name' => 'id',
     *      'primary' => true,
     *  ),
     *  'name',
     *  'created_at' => array(
     *      'type' => 'DateTime',
     *      'context' => array('type' => 'datetime'),
     *  ),
     * );
     *
     * @return array
     */
    abstract protected function getFieldRules();

    /**
     * Class name of object of this normalizer
     *
     * @return string
     */
    abstract protected function getTargetClassName();

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $targetClass = $this->getTargetClassName();
        if (!$object instanceof $targetClass) {
            return null;
        }

        $fieldRules = $this->getProcessedFieldRules();

        if (isset($context['mode']) && $context['mode'] == self::SHORT_MODE && $this->primaryField) {
            return $this->getPropertyAccessor()->getValue($object, $this->primaryField['denormalizeName']);
        }

        $result = [];
        foreach ($fieldRules as $field) {
            if (!$field['normalize']) {
                continue;
            }

            $value = $this->getPropertyAccessor()->getValue($object, $field['denormalizeName']);
            if (isset($field['type']) && $value !== null) {
                $value = $this->serializer->normalize(
                    $value,
                    $format,
                    array_merge($context, $field['context'])
                );
            }

            $result[$field['normalizeName']] = $value;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $fieldRules = $this->getProcessedFieldRules();

        if (!is_array($data)) {
            if ($this->primaryField) {
                $data = [$this->primaryField['normalizeName'] => $data];
            } else {
                return $this->createNewObject();
            }
        }

        $object = $this->createNewObject();

        foreach ($fieldRules as $field) {
            if (!$field['denormalize'] || !array_key_exists($field['normalizeName'], $data)) {
                continue;
            }

            $value = $data[$field['normalizeName']];
            if (isset($field['type']) && $value !== null) {
                $value = $this->serializer->denormalize(
                    $data[$field['normalizeName']],
                    $field['type'],
                    $format,
                    array_merge($context, $field['context'])
                );
            }

            $this->getPropertyAccessor()->setValue($object, $field['denormalizeName'], $value);
        }

        return $object;
    }

    /**
     * Creates new object of target class
     *
     * @return mixed
     */
    protected function createNewObject()
    {
        $className = $this->getTargetClassName();

        return new $className;
    }

    /**
     * List of rules that declare (de)normalization
     *
     * @return array
     */
    protected function getProcessedFieldRules()
    {
        if (null === $this->fieldRules) {
            $this->fieldRules = [];
            foreach ($this->getFieldRules() as $key => $field) {
                if (is_string($field)) {
                    $fieldName = $field;
                    $field = [];
                } elseif (isset($field['name'])) {
                    $fieldName = $field['name'];
                } else {
                    $fieldName = $key;
                }

                $defaultValues = [
                    'name' => $fieldName,
                    'normalize' => true,
                    'denormalize' => true,
                    'context' => [],
                    'primary' => false,
                    'normalizeName' => $fieldName,
                    'denormalizeName' => $fieldName,
                ];

                $field = array_merge($defaultValues, (array)$field);

                if ($field['primary']) {
                    $this->primaryField = $field;
                }

                $this->fieldRules[] = $field;
            }
        }

        return $this->fieldRules;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor(): PropertyAccessor
    {
        if (!self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        $className = $this->getTargetClassName();

        return $data instanceof $className;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return $type === $this->getTargetClassName();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        if (!$serializer instanceof ContextAwareNormalizerInterface
            || !$serializer instanceof ContextAwareDenormalizerInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Serializer must implement "%s" and "%s"',
                    ContextAwareNormalizerInterface::class,
                    ContextAwareDenormalizerInterface::class
                )
            );
        }
        $this->serializer = $serializer;
    }
}
