<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updated 'locale' column length to be consistent with the data from 'oro_language' table
 */
class UpdateLocaleFieldLength implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_zd_user_role_trans');
        $table->changeColumn('locale', ['length' => 16]);

        $table = $schema->getTable('orocrm_zd_ticket_priority_tran');
        $table->changeColumn('locale', ['length' => 16]);

        $table = $schema->getTable('orocrm_zd_ticket_status_trans');
        $table->changeColumn('locale', ['length' => 16]);

        $table = $schema->getTable('orocrm_zd_ticket_type_trans');
        $table->changeColumn('locale', ['length' => 16]);
    }
}
