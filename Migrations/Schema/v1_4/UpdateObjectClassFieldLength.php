<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updated object_class column length according to
 * {@see \Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation::$objectClass} field metadata change
 */
class UpdateObjectClassFieldLength implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_zd_user_role_trans');
        $table->changeColumn('object_class', ['length' => 191]);

        $table = $schema->getTable('orocrm_zd_ticket_priority_tran');
        $table->changeColumn('object_class', ['length' => 191]);

        $table = $schema->getTable('orocrm_zd_ticket_status_trans');
        $table->changeColumn('object_class', ['length' => 191]);

        $table = $schema->getTable('orocrm_zd_ticket_type_trans');
        $table->changeColumn('object_class', ['length' => 191]);
    }
}
