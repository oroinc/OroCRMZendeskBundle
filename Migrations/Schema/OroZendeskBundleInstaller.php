<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroZendeskBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

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
        $table->addColumn('orocrm_zd_email', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('orocrm_zd_url', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_zd_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_zd_default_user_email', 'string', ['notnull' => false, 'length' => 100]);
    }

    protected function createTicketCollaboratorTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket_collaborators **/
        $table = $schema->createTable('orocrm_zd_ticket_collaborators');
        $table->addColumn('ticket_id', 'integer', []);
        $table->addColumn('user_id', 'integer', []);
        $table->setPrimaryKey(['ticket_id', 'user_id']);
        $table->addIndex(['ticket_id'], 'IDX_5632B9CD700047D2', []);
        $table->addIndex(['user_id'], 'IDX_5632B9CDA76ED395', []);
        /** End of generate table orocrm_zd_ticket_collaborators **/

        /** Generate foreign keys for table orocrm_zd_ticket_collaborators **/
        $table = $schema->getTable('orocrm_zd_ticket_collaborators');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket'),
            ['ticket_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table orocrm_zd_ticket_collaborators **/
    }

    protected function createTicketTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket **/
        $table = $schema->createTable('orocrm_zd_ticket');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('problem_id', 'integer', ['notnull' => false]);
        $table->addColumn('assignee_id', 'integer', ['notnull' => false]);
        $table->addColumn('status_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('submitter_id', 'integer', ['notnull' => false]);
        $table->addColumn('priority_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('requester_id', 'integer', ['notnull' => false]);
        $table->addColumn('case_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('type_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('url', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('external_id', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('subject', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('recipient_email', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('has_incidents', 'boolean', ['default' => '0']);
        $table->addColumn('due_at', 'datetime', ['notnull' => false]);
        $table->addColumn('origin_created_at', 'datetime', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['notnull' => false]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->addColumn('origin_updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['case_id'], 'UNIQ_45472C5FCF10D4F5');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'zd_ticket_oid_cid_unq');
        $table->addIndex(['type_name'], 'IDX_45472C5F892CBB0E', []);
        $table->addIndex(['status_name'], 'IDX_45472C5F6625D392', []);
        $table->addIndex(['priority_name'], 'IDX_45472C5F965BD3DF', []);
        $table->addIndex(['requester_id'], 'IDX_45472C5FED442CF4', []);
        $table->addIndex(['submitter_id'], 'IDX_45472C5F919E5513', []);
        $table->addIndex(['assignee_id'], 'IDX_45472C5F59EC7D60', []);
        $table->addIndex(['channel_id'], 'IDX_45472C5F72F5A1AA', []);
        $table->addIndex(['problem_id'], 'IDX_45472C5FA0DCED86', []);
        /** End of generate table orocrm_zd_ticket **/

        /** Generate foreign keys for table orocrm_zd_ticket **/
        $table = $schema->getTable('orocrm_zd_ticket');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket'),
            ['problem_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            ['assignee_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket_status'),
            ['status_name'],
            ['name'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            ['submitter_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket_priority'),
            ['priority_name'],
            ['name'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            ['requester_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_case'),
            ['case_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket_type'),
            ['type_name'],
            ['name'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table orocrm_zd_ticket **/
    }

    protected function createUserTable(Schema $schema)
    {
        /** Generate table orocrm_zd_user **/
        $table = $schema->createTable('orocrm_zd_user');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('related_contact_id', 'integer', ['notnull' => false]);
        $table->addColumn('related_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('role_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('url', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('external_id', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('details', 'text', ['notnull' => false]);
        $table->addColumn('ticket_restrictions', 'string', ['notnull' => false, 'length' => 30]);
        $table->addColumn('only_private_comments', 'boolean', ['default' => '0']);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['notnull' => false]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->addColumn('origin_created_at', 'datetime', ['notnull' => false]);
        $table->addColumn('origin_updated_at', 'datetime', ['notnull' => false]);
        $table->addColumn('last_login_at', 'datetime', ['notnull' => false]);
        $table->addColumn('verified', 'boolean', ['default' => '0']);
        $table->addColumn('active', 'boolean', ['default' => '0']);
        $table->addColumn('alias', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('time_zone', 'string', ['notnull' => false, 'length' => 30]);
        $table->addColumn('locale', 'string', ['notnull' => false, 'length' => 30]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'zd_user_oid_cid_unq');
        $table->addIndex(['role_name'], 'IDX_5CD5C9CDE09C0C92', []);
        $table->addIndex(['related_contact_id'], 'IDX_5CD5C9CD6D6C2DFA', []);
        $table->addIndex(['related_user_id'], 'IDX_5CD5C9CD98771930', []);
        $table->addIndex(['channel_id'], 'IDX_5CD5C9CD72F5A1AA', []);
        /** End of generate table orocrm_zd_user **/

        /** Generate foreign keys for table orocrm_zd_user **/
        $table = $schema->getTable('orocrm_zd_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            ['related_contact_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['related_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user_role'),
            ['role_name'],
            ['name'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table orocrm_zd_user **/
    }

    protected function createRoleTable(Schema $schema)
    {
        /** Generate table orocrm_zd_user_role **/
        $table = $schema->createTable('orocrm_zd_user_role');
        $table->addColumn('name', 'string', ['length' => 16]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        /** End of generate table orocrm_zd_user_role **/

        /** Generate table orocrm_zd_user_role_trans **/
        $table = $schema->createTable('orocrm_zd_user_role_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 16]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 16]);
        $table->addColumn('object_class', 'string', ['length' => 191]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(
            ['locale', 'object_class', 'field', 'foreign_key'],
            'orocrm_zd_user_role_trans_idx',
            []
        );
        /** End of generate table orocrm_zd_user_role_trans **/
    }

    protected function createPriorityTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket_priority **/
        $table = $schema->createTable('orocrm_zd_ticket_priority');
        $table->addColumn('name', 'string', ['length' => 16]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        /** End of generate table orocrm_zd_ticket_priority **/

        /** Generate table orocrm_zd_ticket_priority_tran **/
        $table = $schema->createTable('orocrm_zd_ticket_priority_tran');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 16]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 16]);
        $table->addColumn('object_class', 'string', ['length' => 191]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(
            ['locale', 'object_class', 'field', 'foreign_key'],
            'orocrm_zd_ticket_priority_tran_idx',
            []
        );
        /** End of generate table orocrm_zd_ticket_priority_tran **/
    }

    protected function createStatusTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket_status **/
        $table = $schema->createTable('orocrm_zd_ticket_status');
        $table->addColumn('name', 'string', ['length' => 16]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        /** End of generate table orocrm_zd_ticket_status **/

        /** Generate table orocrm_zd_ticket_status_trans **/
        $table = $schema->createTable('orocrm_zd_ticket_status_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 16]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 16]);
        $table->addColumn('object_class', 'string', ['length' => 191]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(
            ['locale', 'object_class', 'field', 'foreign_key'],
            'orocrm_zd_ticket_status_trans_idx',
            []
        );
        /** End of generate table orocrm_zd_ticket_status_trans **/
    }

    protected function createCommentTable(Schema $schema)
    {
        /** Generate table orocrm_zd_comment **/
        $table = $schema->createTable('orocrm_zd_comment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('related_comment_id', 'integer', ['notnull' => false]);
        $table->addColumn('author_id', 'integer', ['notnull' => false]);
        $table->addColumn('ticket_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('body', 'text', ['notnull' => false]);
        $table->addColumn('html_body', 'text', ['notnull' => false]);
        $table->addColumn('public', 'boolean', ['default' => '0']);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('origin_created_at', 'datetime', ['notnull' => false]);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['related_comment_id'], 'UNIQ_20AD0BDA72A475A3');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'zd_comment_oid_cid_unq');
        $table->addIndex(['author_id'], 'IDX_20AD0BDAF675F31B', []);
        $table->addIndex(['ticket_id'], 'IDX_20AD0BDA700047D2', []);
        $table->addIndex(['channel_id'], 'IDX_20AD0BDA72F5A1AA', []);
        /** End of generate table orocrm_zd_comment **/

        /** Generate foreign keys for table orocrm_zd_comment **/
        $table = $schema->getTable('orocrm_zd_comment');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_case_comment'),
            ['related_comment_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            ['author_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket'),
            ['ticket_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table orocrm_zd_comment **/
    }

    protected function createTypeTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket_type **/
        $table = $schema->createTable('orocrm_zd_ticket_type');
        $table->addColumn('name', 'string', ['length' => 16]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        /** End of generate table orocrm_zd_ticket_type **/

        /** Generate table orocrm_zd_ticket_type_trans **/
        $table = $schema->createTable('orocrm_zd_ticket_type_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 16]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 16]);
        $table->addColumn('object_class', 'string', ['length' => 191]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(
            ['locale', 'object_class', 'field', 'foreign_key'],
            'orocrm_zd_ticket_type_trans_idx',
            []
        );
        /** End of generate table orocrm_zd_ticket_type_trans **/
    }
}
