<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractZendeskFixture extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function setEntityPropertyValues(object $entity, array $data, array $excludeProperties = []): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $property => $value) {
            if (\in_array($property, $excludeProperties, true)) {
                continue;
            }
            $propertyAccessor->setValue($entity, $property, $value);
        }
    }
}
