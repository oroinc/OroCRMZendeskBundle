<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Schema\v1_0;

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
        $this->createRoleTable($schema);
        $this->createUserTable($schema);
        $this->createPriorityTable($schema);
        $this->createStatusTable($schema);
        $this->createTypeTable($schema);
        $this->createTicketTable($schema);
        $this->createCommentTable($schema);
        $this->createTicketCollaboratorTable($schema);
        $this->updateOroIntegrationTransportTable($schema);
    }

    public function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('orocrm_zd_email', 'string', array('notnull' => false, 'length' => 100));
        $table->addColumn('orocrm_zd_url', 'string', array('notnull' => false, 'length' => 255));
        $table->addColumn('orocrm_zd_token', 'string', array('notnull' => false, 'length' => 255));
        $table->addColumn('orocrm_zd_default_user_email', 'string', array('notnull' => false, 'length' => 100));
    }

    protected function createTicketCollaboratorTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket_collaborators **/
        $table = $schema->createTable('orocrm_zd_ticket_collaborators');
        $table->addColumn('ticket_id', 'integer', array());
        $table->addColumn('user_id', 'integer', array());
        $table->setPrimaryKey(array('ticket_id', 'user_id'));
        $table->addIndex(array('ticket_id'), 'IDX_5632B9CD700047D2', array());
        $table->addIndex(array('user_id'), 'IDX_5632B9CDA76ED395', array());
        /** End of generate table orocrm_zd_ticket_collaborators **/

        /** Generate foreign keys for table orocrm_zd_ticket_collaborators **/
        $table = $schema->getTable('orocrm_zd_ticket_collaborators');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            array('user_id'),
            array('id'),
            array('onDelete' => 'CASCADE', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket'),
            array('ticket_id'),
            array('id'),
            array('onDelete' => 'CASCADE', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zd_ticket_collaborators **/
    }

    protected function createTicketTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket **/
        $table = $schema->createTable('orocrm_zd_ticket');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('problem_id', 'integer', array('notnull' => false));
        $table->addColumn('assignee_id', 'integer', array('notnull' => false));
        $table->addColumn('status_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('submitter_id', 'integer', array('notnull' => false));
        $table->addColumn('priority_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('requester_id', 'integer', array('notnull' => false));
        $table->addColumn('case_id', 'integer', array('notnull' => false));
        $table->addColumn('channel_id', 'smallint', array('notnull' => false));
        $table->addColumn('type_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('origin_id', 'bigint', array('notnull' => false));
        $table->addColumn('url', 'string', array('notnull' => false, 'length' => 255));
        $table->addColumn('external_id', 'string', array('notnull' => false, 'length' => 50));
        $table->addColumn('subject', 'string', array('notnull' => false, 'length' => 255));
        $table->addColumn('description', 'text', array('notnull' => false));
        $table->addColumn('recipient_email', 'string', array('notnull' => false, 'length' => 100));
        $table->addColumn('has_incidents', 'boolean', array('default' => '0'));
        $table->addColumn('due_at', 'datetime', array('notnull' => false));
        $table->addColumn('origin_created_at', 'datetime', array('notnull' => false));
        $table->addColumn('created_at', 'datetime', array('notnull' => false));
        $table->addColumn('updated_at', 'datetime', array('notnull' => false));
        $table->addColumn('origin_updated_at', 'datetime', array('notnull' => false));
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('case_id'), 'UNIQ_45472C5FCF10D4F5');
        $table->addUniqueIndex(array('origin_id', 'channel_id'), 'unq_origin_id_channel_id');
        $table->addIndex(array('type_name'), 'IDX_45472C5F892CBB0E', array());
        $table->addIndex(array('status_name'), 'IDX_45472C5F6625D392', array());
        $table->addIndex(array('priority_name'), 'IDX_45472C5F965BD3DF', array());
        $table->addIndex(array('requester_id'), 'IDX_45472C5FED442CF4', array());
        $table->addIndex(array('submitter_id'), 'IDX_45472C5F919E5513', array());
        $table->addIndex(array('assignee_id'), 'IDX_45472C5F59EC7D60', array());
        $table->addIndex(array('channel_id'), 'IDX_45472C5F72F5A1AA', array());
        $table->addIndex(array('problem_id'), 'IDX_45472C5FA0DCED86', array());
        /** End of generate table orocrm_zd_ticket **/

        /** Generate foreign keys for table orocrm_zd_ticket **/
        $table = $schema->getTable('orocrm_zd_ticket');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket'),
            array('problem_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            array('assignee_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket_status'),
            array('status_name'),
            array('name'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            array('submitter_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket_priority'),
            array('priority_name'),
            array('name'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            array('requester_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_case'),
            array('case_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            array('channel_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket_type'),
            array('type_name'),
            array('name'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zd_ticket **/
    }

    protected function createUserTable(Schema $schema)
    {
        /** Generate table orocrm_zd_user **/
        $table = $schema->createTable('orocrm_zd_user');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('channel_id', 'smallint', array('notnull' => false));
        $table->addColumn('related_contact_id', 'integer', array('notnull' => false));
        $table->addColumn('related_user_id', 'integer', array('notnull' => false));
        $table->addColumn('role_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('origin_id', 'bigint', array('notnull' => false));
        $table->addColumn('url', 'string', array('notnull' => false, 'length' => 255));
        $table->addColumn('external_id', 'string', array('notnull' => false, 'length' => 50));
        $table->addColumn('name', 'string', array('notnull' => false, 'length' => 100));
        $table->addColumn('details', 'text', array('notnull' => false));
        $table->addColumn('ticket_restrictions', 'string', array('notnull' => false, 'length' => 30));
        $table->addColumn('only_private_comments', 'boolean', array('default' => '0'));
        $table->addColumn('notes', 'text', array('notnull' => false));
        $table->addColumn('created_at', 'datetime', array('notnull' => false));
        $table->addColumn('updated_at', 'datetime', array('notnull' => false));
        $table->addColumn('origin_created_at', 'datetime', array('notnull' => false));
        $table->addColumn('origin_updated_at', 'datetime', array('notnull' => false));
        $table->addColumn('last_login_at', 'datetime', array('notnull' => false));
        $table->addColumn('verified', 'boolean', array('default' => '0'));
        $table->addColumn('active', 'boolean', array('default' => '0'));
        $table->addColumn('alias', 'string', array('notnull' => false, 'length' => 100));
        $table->addColumn('email', 'string', array('notnull' => false, 'length' => 100));
        $table->addColumn('phone', 'string', array('notnull' => false, 'length' => 30));
        $table->addColumn('time_zone', 'string', array('notnull' => false, 'length' => 30));
        $table->addColumn('locale', 'string', array('notnull' => false, 'length' => 30));
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('origin_id', 'channel_id'), 'unq_origin_id_channel_id');
        $table->addIndex(array('role_name'), 'IDX_5CD5C9CDE09C0C92', array());
        $table->addIndex(array('related_contact_id'), 'IDX_5CD5C9CD6D6C2DFA', array());
        $table->addIndex(array('related_user_id'), 'IDX_5CD5C9CD98771930', array());
        $table->addIndex(array('channel_id'), 'IDX_5CD5C9CD72F5A1AA', array());
        /** End of generate table orocrm_zd_user **/

        /** Generate foreign keys for table orocrm_zd_user **/
        $table = $schema->getTable('orocrm_zd_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            array('channel_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            array('related_contact_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            array('related_user_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user_role'),
            array('role_name'),
            array('name'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zd_user **/
    }

    protected function createRoleTable(Schema $schema)
    {
        /** Generate table orocrm_zd_user_role **/
        $table = $schema->createTable('orocrm_zd_user_role');
        $table->addColumn('name', 'string', array('length' => 16));
        $table->addColumn('label', 'string', array('length' => 255));
        $table->setPrimaryKey(array('name'));
        /** End of generate table orocrm_zd_user_role **/

        /** Generate table orocrm_zd_user_role_trans **/
        $table = $schema->createTable('orocrm_zd_user_role_trans');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('foreign_key', 'string', array('length' => 16));
        $table->addColumn('content', 'string', array('length' => 255));
        $table->addColumn('locale', 'string', array('length' => 8));
        $table->addColumn('object_class', 'string', array('length' => 255));
        $table->addColumn('field', 'string', array('length' => 32));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(
            array('locale', 'object_class', 'field', 'foreign_key'),
            'orocrm_zd_user_role_trans_idx',
            array()
        );
        /** End of generate table orocrm_zd_user_role_trans **/
    }

    protected function createPriorityTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket_priority **/
        $table = $schema->createTable('orocrm_zd_ticket_priority');
        $table->addColumn('name', 'string', array('length' => 16));
        $table->addColumn('label', 'string', array('length' => 255));
        $table->setPrimaryKey(array('name'));
        /** End of generate table orocrm_zd_ticket_priority **/

        /** Generate table orocrm_zd_ticket_priority_tran **/
        $table = $schema->createTable('orocrm_zd_ticket_priority_tran');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('foreign_key', 'string', array('length' => 16));
        $table->addColumn('content', 'string', array('length' => 255));
        $table->addColumn('locale', 'string', array('length' => 8));
        $table->addColumn('object_class', 'string', array('length' => 255));
        $table->addColumn('field', 'string', array('length' => 32));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(
            array('locale', 'object_class', 'field', 'foreign_key'),
            'orocrm_zd_ticket_priority_tran_idx',
            array()
        );
        /** End of generate table orocrm_zd_ticket_priority_tran **/
    }

    protected function createStatusTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket_status **/
        $table = $schema->createTable('orocrm_zd_ticket_status');
        $table->addColumn('name', 'string', array('length' => 16));
        $table->addColumn('label', 'string', array('length' => 255));
        $table->setPrimaryKey(array('name'));
        /** End of generate table orocrm_zd_ticket_status **/

        /** Generate table orocrm_zd_ticket_status_trans **/
        $table = $schema->createTable('orocrm_zd_ticket_status_trans');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('foreign_key', 'string', array('length' => 16));
        $table->addColumn('content', 'string', array('length' => 255));
        $table->addColumn('locale', 'string', array('length' => 8));
        $table->addColumn('object_class', 'string', array('length' => 255));
        $table->addColumn('field', 'string', array('length' => 32));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(
            array('locale', 'object_class', 'field', 'foreign_key'),
            'orocrm_zd_ticket_status_trans_idx',
            array()
        );
        /** End of generate table orocrm_zd_ticket_status_trans **/
    }

    protected function createCommentTable(Schema $schema)
    {
        /** Generate table orocrm_zd_comment **/
        $table = $schema->createTable('orocrm_zd_comment');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('channel_id', 'smallint', array('notnull' => false));
        $table->addColumn('related_comment_id', 'integer', array('notnull' => false));
        $table->addColumn('author_id', 'integer', array('notnull' => false));
        $table->addColumn('ticket_id', 'integer', array('notnull' => false));
        $table->addColumn('origin_id', 'bigint', array('notnull' => false));
        $table->addColumn('body', 'text', array('notnull' => false));
        $table->addColumn('html_body', 'text', array('notnull' => false));
        $table->addColumn('public', 'boolean', array('default' => '0'));
        $table->addColumn('created_at', 'datetime', array());
        $table->addColumn('origin_created_at', 'datetime', array('notnull' => false));
        $table->addColumn('updated_at', 'datetime', array());
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('related_comment_id'), 'UNIQ_20AD0BDA72A475A3');
        $table->addUniqueIndex(array('origin_id', 'channel_id'), 'unq_origin_id_channel_id');
        $table->addIndex(array('author_id'), 'IDX_20AD0BDAF675F31B', array());
        $table->addIndex(array('ticket_id'), 'IDX_20AD0BDA700047D2', array());
        $table->addIndex(array('channel_id'), 'IDX_20AD0BDA72F5A1AA', array());
        /** End of generate table orocrm_zd_comment **/

        /** Generate foreign keys for table orocrm_zd_comment **/
        $table = $schema->getTable('orocrm_zd_comment');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            array('channel_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_case_comment'),
            array('related_comment_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            array('author_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket'),
            array('ticket_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zd_comment **/
    }

    protected function createTypeTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket_type **/
        $table = $schema->createTable('orocrm_zd_ticket_type');
        $table->addColumn('name', 'string', array('length' => 16));
        $table->addColumn('label', 'string', array('length' => 255));
        $table->setPrimaryKey(array('name'));
        /** End of generate table orocrm_zd_ticket_type **/

        /** Generate table orocrm_zd_ticket_type_trans **/
        $table = $schema->createTable('orocrm_zd_ticket_type_trans');
        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('foreign_key', 'string', array('length' => 16));
        $table->addColumn('content', 'string', array('length' => 255));
        $table->addColumn('locale', 'string', array('length' => 8));
        $table->addColumn('object_class', 'string', array('length' => 255));
        $table->addColumn('field', 'string', array('length' => 32));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(
            array('locale', 'object_class', 'field', 'foreign_key'),
            'orocrm_zd_ticket_type_trans_idx',
            array()
        );
        /** End of generate table orocrm_zd_ticket_type_trans **/
    }
}
