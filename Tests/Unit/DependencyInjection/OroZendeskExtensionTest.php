<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ZendeskBundle\DependencyInjection\OroZendeskExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroZendeskExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroZendeskExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
