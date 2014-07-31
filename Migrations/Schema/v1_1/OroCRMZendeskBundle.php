<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCRMZendeskBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery('ALTER TABLE orocrm_zd_comment DROP FOREIGN KEY FK_20AD0BDA72F5A1AA;');
        $queries->addPreQuery('ALTER TABLE orocrm_zd_ticket DROP FOREIGN KEY fk_orocrm_zd_ticket_channel_id;');
        $queries->addPreQuery('ALTER TABLE orocrm_zd_user DROP FOREIGN KEY fk_orocrm_zd_user_channel_id;');

        $table = $schema->getTable('orocrm_zd_ticket');
        $table->getColumn('channel_id')->setType(Type::getType('integer'));
        $table->dropIndex('unq_origin_id_channel_id');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'zd_ticket_oid_cid_unq');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $table = $schema->getTable('orocrm_zd_user');
        $table->getColumn('channel_id')->setType(Type::getType('integer'));
        $table->dropIndex('unq_origin_id_channel_id');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'zd_user_oid_cid_unq');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $table = $schema->getTable('orocrm_zd_comment');
        $table->getColumn('channel_id')->setType(Type::getType('integer'));
        $table->dropIndex('unq_origin_id_channel_id');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'zd_comment_oid_cid_unq');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
