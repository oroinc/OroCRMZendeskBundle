<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Schema\v7_1_0_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Changes orocrm_zd_access_token and orocrm_zd_refresh_token columns from VARCHAR(255) to TEXT
 */
class ChangeOAuthTokenColumnsToText implements Migration
{
    private const TABLE_NAME = 'oro_integration_transport';

    private const COLUMNS = [
        'orocrm_zd_access_token',
        'orocrm_zd_refresh_token',
    ];

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        foreach (self::COLUMNS as $columnName) {
            if (false === $table->hasColumn($columnName)) {
                continue;
            }

            $column = $table->getColumn($columnName);
            if (Types::TEXT === $column->getType()->getName()) {
                continue;
            }

            $table->modifyColumn($columnName, ['type' => Type::getType(Types::TEXT), 'length' => null]);
        }
    }
}
