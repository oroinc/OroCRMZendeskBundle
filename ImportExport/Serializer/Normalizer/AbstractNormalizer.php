<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

abstract class AbstractNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    const SHORT_MODE = 'short';

    /**
     * @var PropertyAccessor
     */
    static private $propertyAccessor;

    /**
     * @var SerializerInterface|NormalizerInterface|DenormalizerInterface
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
    public function normalize($object, $format = null, array $context = array())
    {
        $targetClass = $this->getTargetClassName();
        if (!$object instanceof $targetClass) {
            return null;
        }

        $fieldRules = $this->getProcessedFieldRules();

        if (isset($context['mode']) && $context['mode'] == self::SHORT_MODE && $this->primaryField) {
            return $this->getPropertyAccessor()->getValue($object, $this->primaryField['denormalizeName']);
        }

        $result = array();
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
     * @param mixed $data
     * @param string $class
     * @param mixed $format
     * @param array $context
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $fieldRules = $this->getProcessedFieldRules();

        if (!is_array($data)) {
            if ($this->primaryField) {
                $data = array($this->primaryField['normalizeName'] => $data);
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
        if (null == $this->fieldRules) {
            $this->fieldRules = array();
            foreach ($this->getFieldRules() as $key => $field) {
                if (is_string($field)) {
                    $fieldName = $field;
                    $field = array();
                } elseif (isset($field['name'])) {
                    $fieldName = $field['name'];
                } else {
                    $fieldName = $key;
                }

                $defaultValues = array(
                    'name' => $fieldName,
                    'normalize' => true,
                    'denormalize' => true,
                    'context' => array(),
                    'primary' => false,
                    'normalizeName' => $fieldName,
                    'denormalizeName' => $fieldName,
                );

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
    protected function getPropertyAccessor()
    {
        if (!self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return self::$propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        $className = $this->getTargetClassName();
        return $data instanceof $className;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return $type == $this->getTargetClassName();
    }

    /**
     * @param SerializerInterface $serializer
     * @throws InvalidArgumentException
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!$serializer instanceof NormalizerInterface || !$serializer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Serializer must implement "%s" and "%s"',
                    'Symfony\\Component\\Serializer\\Normalizer\\NormalizerInterface',
                    'Symfony\\Component\\Serializer\\Normalizer\\DenormalizerInterface'
                )
            );
        }
        $this->serializer = $serializer;
    }
}
