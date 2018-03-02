<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class UpdateIntegrationChannel implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // change associations with the integration channel from "onDelete=SET NULL" to "onDelete=CASCADE"
        $this->updateChannelForeignKey($schema, 'orocrm_zd_comment', 'FK_20AD0BDA72F5A1AA');
        $this->updateChannelForeignKey($schema, 'orocrm_zd_ticket', 'fk_orocrm_zd_ticket_channel_id');
        $this->updateChannelForeignKey($schema, 'orocrm_zd_user', 'fk_orocrm_zd_user_channel_id');

        // remove entities without the integration channel
        $queries->addQuery(
            new SqlMigrationQuery([
                'DELETE FROM orocrm_zd_comment WHERE channel_id IS NULL',
                'DELETE FROM orocrm_zd_ticket WHERE channel_id IS NULL',
                'DELETE FROM orocrm_zd_user WHERE channel_id IS NULL',
            ])
        );
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     * @param string $foreignKeyName
     */
    private function updateChannelForeignKey(Schema $schema, $tableName, $foreignKeyName)
    {
        $table = $schema->getTable($tableName);
        if ($table->hasForeignKey($foreignKeyName)) {
            $fk = $table->getForeignKey($foreignKeyName);
            if ('CASCADE' !== $fk->onDelete()) {
                $table->removeForeignKey($foreignKeyName);
                $table->addForeignKeyConstraint(
                    $schema->getTable('oro_integration_channel'),
                    ['channel_id'],
                    ['id'],
                    ['onDelete' => 'CASCADE']
                );
            }
        }
    }
}
