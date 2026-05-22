<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds OAuth fields to the integration transport table.
 */
class AddOAuthFieldsIntegration implements Migration, OrderedMigrationInterface
{
    private const TABLE_NAME = 'oro_integration_transport';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        if (false === $table->hasColumn('orocrm_zd_authorization_type')) {
            $table->addColumn('orocrm_zd_authorization_type', 'string', ['notnull' => false, 'length' => 32]);
        }

        if (false === $table->hasColumn('orocrm_zd_oauth_client_id')) {
            $table->addColumn('orocrm_zd_oauth_client_id', 'string', ['notnull' => false, 'length' => 255]);
        }

        if (false === $table->hasColumn('orocrm_zd_access_token')) {
            $table->addColumn('orocrm_zd_access_token', 'string', ['notnull' => false, 'length' => 255]);
        }

        if (false === $table->hasColumn('orocrm_zd_refresh_token')) {
            $table->addColumn('orocrm_zd_refresh_token', 'string', ['notnull' => false, 'length' => 255]);
        }

        if (false === $table->hasColumn('orocrm_zd_oauth_last_refresh_at')) {
            $table->addColumn('orocrm_zd_oauth_last_refresh_at', 'datetime', ['notnull' => false]);
        }
    }

    #[\Override]
    public function getOrder(): int
    {
        return 100;
    }
}
