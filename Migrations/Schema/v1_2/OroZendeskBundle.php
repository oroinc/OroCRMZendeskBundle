<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroZendeskBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_zd_user');
        $table->getColumn('name')->setLength(255);
        $table->getColumn('email')->setLength(255);
        $table->getColumn('phone')->setLength(50);
    }
}
