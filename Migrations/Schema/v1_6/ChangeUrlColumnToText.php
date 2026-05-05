<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Changes orocrm_zd_url column type from VARCHAR(255) to TEXT.
 */
class ChangeUrlColumnToText implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $column = $table->getColumn('orocrm_zd_url');

        if ($column->getType()->getName() === Types::TEXT) {
            return;
        }

        $table->modifyColumn('orocrm_zd_url', ['type' => Type::getType(Types::TEXT), 'length' => null]);
    }
}
