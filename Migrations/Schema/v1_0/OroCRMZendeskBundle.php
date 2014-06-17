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
        $this->createPriorityTable($schema);
        $this->createStatusTable($schema);
        $this->createTypeTable($schema);
        $this->createTicketTable($schema);
        $this->createSyncStateTable($schema);
        $this->createCommentTable($schema);
        $this->createTicketCollaboratorTable($schema);
    }

    /**
     * @param Schema $schema
     */
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

    /**
     * @param Schema $schema
     */
    protected function createSyncStateTable(Schema $schema)
    {
        /** Generate table orocrm_zd_sync_state **/
        $table = $schema->createTable('orocrm_zd_sync_state');
        $table->addColumn('id', 'integer', array());
        $table->addColumn('userSync', 'datetime', array());
        $table->addColumn('ticketSync', 'datetime', array());
        $table->setPrimaryKey(array('id'));
        /** End of generate table orocrm_zd_sync_state **/
    }

    /**
     * @param Schema $schema
     */
    protected function createTicketTable(Schema $schema)
    {
        /** Generate table orocrm_zd_ticket **/
        $table = $schema->createTable('orocrm_zd_ticket');
        $table->addColumn('id', 'integer', array());
        $table->addColumn('case_id', 'integer', array('notnull' => false));
        $table->addColumn('assignee_id', 'integer', array('notnull' => false));
        $table->addColumn('status_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('type_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('submitter_id', 'integer', array('notnull' => false));
        $table->addColumn('priority_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('problem_id', 'integer', array('notnull' => false));
        $table->addColumn('requester_id', 'integer', array('notnull' => false));
        $table->addColumn('url', 'string', array('length' => 255));
        $table->addColumn('external_id', 'string', array('length' => 50));
        $table->addColumn('subject', 'string', array('length' => 255));
        $table->addColumn('description', 'text', array('notnull' => false));
        $table->addColumn('recipient_email', 'string', array('length' => 100));
        $table->addColumn('public', 'boolean', array('default' => '0'));
        $table->addColumn('dueAt', 'datetime', array());
        $table->addColumn('createdAt', 'datetime', array());
        $table->addColumn('updatedAt', 'datetime', array());
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('problem_id'), 'UNIQ_45472C5FA0DCED86');
        $table->addUniqueIndex(array('case_id'), 'UNIQ_45472C5FCF10D4F5');
        $table->addIndex(array('type_name'), 'IDX_45472C5F892CBB0E', array());
        $table->addIndex(array('status_name'), 'IDX_45472C5F6625D392', array());
        $table->addIndex(array('priority_name'), 'IDX_45472C5F965BD3DF', array());
        $table->addIndex(array('requester_id'), 'IDX_45472C5FED442CF4', array());
        $table->addIndex(array('submitter_id'), 'IDX_45472C5F919E5513', array());
        $table->addIndex(array('assignee_id'), 'IDX_45472C5F59EC7D60', array());
        /** End of generate table orocrm_zd_ticket **/

        /** Generate foreign keys for table orocrm_zd_ticket **/
        $table = $schema->getTable('orocrm_zd_ticket');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_case'),
            array('case_id'),
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
            $schema->getTable('orocrm_zd_ticket_type'),
            array('type_name'),
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
            $schema->getTable('orocrm_zd_ticket'),
            array('problem_id'),
            array('id'),
            array('onDelete' => null, 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            array('requester_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zd_ticket **/
    }

    /**
     * @param Schema $schema
     */
    protected function createUserTable(Schema $schema)
    {
        /** Generate table orocrm_zd_user **/
        $table = $schema->createTable('orocrm_zd_user');
        $table->addColumn('id', 'integer', array());
        $table->addColumn('user_id', 'integer', array('notnull' => false));
        $table->addColumn('role_name', 'string', array('notnull' => false, 'length' => 16));
        $table->addColumn('contact_id', 'integer', array('notnull' => false));
        $table->addColumn('url', 'string', array('length' => 255));
        $table->addColumn('external_id', 'string', array('length' => 50));
        $table->addColumn('name', 'string', array('length' => 100));
        $table->addColumn('details', 'text', array());
        $table->addColumn('ticket_restrictions', 'string', array('length' => 30));
        $table->addColumn('only_private_comments', 'boolean', array('default' => '0'));
        $table->addColumn('notes', 'text', array());
        $table->addColumn('createdAt', 'datetime', array());
        $table->addColumn('updatedAt', 'datetime', array());
        $table->addColumn('lastLoginAt', 'datetime', array());
        $table->addColumn('verified', 'boolean', array('default' => '0'));
        $table->addColumn('active', 'boolean', array('default' => '0'));
        $table->addColumn('alias', 'string', array('length' => 100));
        $table->addColumn('email', 'string', array('length' => 100));
        $table->addColumn('phone', 'string', array('length' => 30));
        $table->addColumn('time_zone', 'string', array('length' => 30));
        $table->addColumn('locale', 'string', array('length' => 30));
        $table->setPrimaryKey(array('id'));
        $table->addIndex(array('role_name'), 'IDX_5CD5C9CDE09C0C92', array());
        $table->addIndex(array('contact_id'), 'IDX_5CD5C9CDE7A1254A', array());
        $table->addIndex(array('user_id'), 'IDX_5CD5C9CDA76ED395', array());
        /** End of generate table orocrm_zd_user **/

        /** Generate foreign keys for table orocrm_zd_user **/
        $table = $schema->getTable('orocrm_zd_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            array('user_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user_role'),
            array('role_name'),
            array('name'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            array('contact_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zd_user **/
    }

    /**
     * @param Schema $schema
     */
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

    /**
     * @param Schema $schema
     */
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

    /**
     * @param Schema $schema
     */
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

    /**
     * @param Schema $schema
     */
    protected function createCommentTable(Schema $schema)
    {
        /** Generate table orocrm_zd_comment **/
        $table = $schema->createTable('orocrm_zd_comment');
        $table->addColumn('id', 'integer', array());
        $table->addColumn('case_comment_id', 'integer', array('notnull' => false));
        $table->addColumn('ticket_id', 'integer', array('notnull' => false));
        $table->addColumn('author_id', 'integer', array('notnull' => false));
        $table->addColumn('body', 'text', array());
        $table->addColumn('html_body', 'text', array());
        $table->addColumn('public', 'boolean', array('default' => '0'));
        $table->addColumn('createdAt', 'datetime', array());
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('case_comment_id'), 'UNIQ_20AD0BDAD22875F7');
        $table->addIndex(array('author_id'), 'IDX_20AD0BDAF675F31B', array());
        $table->addIndex(array('ticket_id'), 'IDX_20AD0BDA700047D2', array());
        /** End of generate table orocrm_zd_comment **/

        /** Generate foreign keys for table orocrm_zd_comment **/
        $table = $schema->getTable('orocrm_zd_comment');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_case_comment'),
            array('case_comment_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_ticket'),
            array('ticket_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_zd_user'),
            array('author_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null)
        );
        /** End of generate foreign keys for table orocrm_zd_comment **/
    }

    /**
     * @param Schema $schema
     */
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