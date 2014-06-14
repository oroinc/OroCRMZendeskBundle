<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCRMZendeskBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createRoleTable($schema);
        $this->createUserTable($schema);
        $this->createCommentTable($schema);
        $this->createPriorityTable($schema);
        $this->createStatusTable($schema);
        $this->createTypeTable($schema);
        $this->createTicketTable($schema);

        $this->addForeignKeys($schema);
    }

    protected function createTicketTable(Schema $schema)
    {
        /** Generate table orocrm_zendesk_ticket **/
        $table = $schema->createTable('orocrm_zendesk_ticket');
        $table->addColumn('id', 'integer', array());
        $table->addColumn('owner_id', 'integer', array('notnull' => false));
        $table->addColumn('status_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('type_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('submitter_id', 'integer', array('notnull' => false));
        $table->addColumn('priority_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('case_id', 'integer', array('notnull' => false));
        $table->addColumn('requester_id', 'integer', array('notnull' => false));
        $table->addColumn('assigned_to_id', 'integer', array('notnull' => false));
        $table->addColumn('url', 'string', array('length' => 255));
        $table->addColumn('subject', 'string', array('length' => 255));
        $table->addColumn('description', 'text', array('notnull' => false));
        $table->addColumn('recipient_email', 'string', array('length' => 100));
        $table->addColumn('public', 'boolean', array('default' => '0'));
        $table->addColumn('dueAt', 'datetime', array());
        $table->addColumn('createdAt', 'datetime', array());
        $table->addColumn('updatedAt', 'datetime', array());
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('case_id'), 'UNIQ_9E8CE395CF10D4F5');
        $table->addIndex(array('type_name'), 'IDX_9E8CE395892CBB0E', array());
        $table->addIndex(array('status_name'), 'IDX_9E8CE3956625D392', array());
        $table->addIndex(array('priority_name'), 'IDX_9E8CE395965BD3DF', array());
        $table->addIndex(array('requester_id'), 'IDX_9E8CE395ED442CF4', array());
        $table->addIndex(array('submitter_id'), 'IDX_9E8CE395919E5513', array());
        $table->addIndex(array('assigned_to_id'), 'IDX_9E8CE395F4BD7827', array());
        $table->addIndex(array('owner_id'), 'IDX_9E8CE3957E3C61F9', array());
        /** End of generate table orocrm_zendesk_ticket **/
    }

    protected function createUserTable(Schema $schema)
    {
        /** Generate table orocrm_zendesk_user **/
        $table = $schema->createTable('orocrm_zendesk_user');
        $table->addColumn('id', 'integer', array());
        $table->addColumn('role_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('owner_id', 'integer', array('notnull' => false));
        $table->addColumn('user_id', 'integer', array('notnull' => false));
        $table->addColumn('createdAt', 'datetime', array());
        $table->addColumn('updatedAt', 'datetime', array());
        $table->addColumn('email', 'string', array('length' => 100));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(array('role_name'), 'IDX_303D6CC8E09C0C92', array());
        $table->addIndex(array('user_id'), 'IDX_303D6CC8A76ED395', array());
        $table->addIndex(array('owner_id'), 'IDX_303D6CC87E3C61F9', array());
        /** End of generate table orocrm_zendesk_user **/
    }

    protected function createRoleTable(Schema $schema)
    {
        /** Generate table orocrm_zendesk_user_role **/
        $table = $schema->createTable('orocrm_zendesk_user_role');
        $table->addColumn('name', 'string', array('length' => 16));
        $table->addColumn('label', 'string', array('length' => 255));
        $table->setPrimaryKey(array('name'));
        /** End of generate table orocrm_zendesk_user_role **/

        /** Generate table orocrm_zendesk_user_role_trans **/
        $table = $schema->createTable('orocrm_zendesk_user_role_trans');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('foreign_key', 'string', array('length' => 16));
        $table->addColumn('content', 'string', array('length' => 255));
        $table->addColumn('locale', 'string', array('length' => 8));
        $table->addColumn('object_class', 'string', array('length' => 255));
        $table->addColumn('field', 'string', array('length' => 32));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(
            array('locale', 'object_class', 'field', 'foreign_key'),
            'orocrm_zendesk_user_role_trans_idx',
            array()
        );
        /** End of generate table orocrm_zendesk_user_role_trans **/
    }

    protected function createPriorityTable(Schema $schema)
    {
        /** Generate table orocrm_ticket_priority **/
        $table = $schema->createTable('orocrm_ticket_priority');
        $table->addColumn('name', 'string', array('length' => 16));
        $table->addColumn('label', 'string', array('length' => 255));
        $table->setPrimaryKey(array('name'));
        /** End of generate table orocrm_ticket_priority **/

        /** Generate table orocrm_ticket_priority_trans **/
        $table = $schema->createTable('orocrm_ticket_priority_trans');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('foreign_key', 'string', array('length' => 16));
        $table->addColumn('content', 'string', array('length' => 255));
        $table->addColumn('locale', 'string', array('length' => 8));
        $table->addColumn('object_class', 'string', array('length' => 255));
        $table->addColumn('field', 'string', array('length' => 32));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(
            array('locale', 'object_class', 'field', 'foreign_key'),
            'orocrm_ticket_priority_trans_idx',
            array()
        );
        /** End of generate table orocrm_ticket_priority_trans **/
    }

    protected function createStatusTable(Schema $schema)
    {
        /** Generate table orocrm_ticket_status **/
        $table = $schema->createTable('orocrm_ticket_status');
        $table->addColumn('name', 'string', array('length' => 16));
        $table->addColumn('label', 'string', array('length' => 255));
        $table->setPrimaryKey(array('name'));
        /** End of generate table orocrm_ticket_status **/

        /** Generate table orocrm_ticket_status_trans **/
        $table = $schema->createTable('orocrm_ticket_status_trans');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('foreign_key', 'string', array('length' => 16));
        $table->addColumn('content', 'string', array('length' => 255));
        $table->addColumn('locale', 'string', array('length' => 8));
        $table->addColumn('object_class', 'string', array('length' => 255));
        $table->addColumn('field', 'string', array('length' => 32));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(
            array('locale', 'object_class', 'field', 'foreign_key'),
            'orocrm_ticket_status_trans_idx',
            array()
        );
        /** End of generate table orocrm_ticket_status_trans **/
    }

    protected function createCommentTable(Schema $schema)
    {
        /** Generate table orocrm_zendesk_comment **/
        $table = $schema->createTable('orocrm_zendesk_comment');
        $table->addColumn('id', 'integer', array());
        $table->addColumn('owner_id', 'integer', array('notnull' => false));
        $table->addColumn('ticket_id', 'integer', array('notnull' => false));
        $table->addColumn('author_id', 'integer', array('notnull' => false));
        $table->addColumn('body', 'text', array());
        $table->addColumn('html_body', 'text', array());
        $table->addColumn('public', 'boolean', array('default' => '0'));
        $table->addColumn('createdAt', 'datetime', array());
        $table->addColumn('updatedAt', 'datetime', array());
        $table->setPrimaryKey(array('id'));
        $table->addIndex(array('author_id'), 'IDX_5BC7EBBBF675F31B', array());
        $table->addIndex(array('ticket_id'), 'IDX_5BC7EBBB700047D2', array());
        $table->addIndex(array('owner_id'), 'IDX_5BC7EBBB7E3C61F9', array());
        /** End of generate table orocrm_zendesk_comment **/
    }

    protected function createTypeTable(Schema $schema)
    {
        /** Generate table orocrm_ticket_type **/
        $table = $schema->createTable('orocrm_ticket_type');
        $table->addColumn('name', 'string', array('length' => 16));
        $table->addColumn('label', 'string', array('length' => 255));
        $table->setPrimaryKey(array('name'));
        /** End of generate table orocrm_ticket_type **/

        /** Generate table orocrm_ticket_type_trans **/
        $table = $schema->createTable('orocrm_ticket_type_trans');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('foreign_key', 'string', array('length' => 16));
        $table->addColumn('content', 'string', array('length' => 255));
        $table->addColumn('locale', 'string', array('length' => 8));
        $table->addColumn('object_class', 'string', array('length' => 255));
        $table->addColumn('field', 'string', array('length' => 32));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(
            array('locale', 'object_class', 'field', 'foreign_key'),
            'orocrm_ticket_type_trans_idx',
            array()
        );
        /** End of generate table orocrm_ticket_type_trans **/
    }

    protected function addForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table orocrm_zendesk_comment **/
        $table = $schema->getTable('orocrm_zendesk_comment');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            array('owner_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zendesk_ticket'),
            array('ticket_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zendesk_user'),
            array('author_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zendesk_comment **/

        /** Generate foreign keys for table orocrm_zendesk_ticket **/
        $table = $schema->getTable('orocrm_zendesk_ticket');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            array('owner_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_ticket_status'),
            array('status_name'),
            array('name'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_ticket_type'),
            array('type_name'),
            array('name'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zendesk_user'),
            array('submitter_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_ticket_priority'),
            array('priority_name'),
            array('name'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_case'),
            array('case_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zendesk_user'),
            array('requester_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zendesk_user'),
            array('assigned_to_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zendesk_ticket **/

        /** Generate foreign keys for table orocrm_zendesk_user **/
        $table = $schema->getTable('orocrm_zendesk_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zendesk_user_role'),
            array('role_name'),
            array('name'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            array('owner_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            array('user_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zendesk_user **/
    }
}
