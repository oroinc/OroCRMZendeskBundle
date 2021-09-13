<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\ZendeskBundle\Controller\Api\Rest\TicketController;
use Oro\Bundle\ZendeskBundle\DependencyInjection\OroZendeskExtension;

class OroZendeskExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroZendeskExtension());

        $expectedDefinitions = [
            TicketController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
