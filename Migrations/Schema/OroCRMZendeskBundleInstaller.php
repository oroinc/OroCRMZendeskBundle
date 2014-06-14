<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use OroCRM\Bundle\ZendeskBundle\Migrations\Schema\v1_0\OroCRMZendeskBundle;

class OroCRMZendeskBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $migration = new OroCRMZendeskBundle();
        $migration->up($schema, $queries);
    }
}
