<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Schema\v1_1;

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
        $table = $schema->getTable('orocrm_zd_ticket');
        $table->dropIndex('unq_origin_id_channel_id');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'zd_ticket_oid_cid_unq');

        $table = $schema->getTable('orocrm_zd_user');
        $table->dropIndex('unq_origin_id_channel_id');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'zd_user_oid_cid_unq');

        $table = $schema->getTable('orocrm_zd_comment');
        $table->dropIndex('unq_origin_id_channel_id');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'zd_comment_oid_cid_unq');
    }
}
