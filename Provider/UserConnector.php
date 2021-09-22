<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

/**
 * Users connector
 */
class UserConnector extends AbstractZendeskConnector
{
    const IMPORT_ENTITY = 'Oro\Bundle\ZendeskBundle\Entity\User';
    const TYPE = 'user';
    const IMPORT_JOB = 'zendesk_user_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return $this->transport->getUsers($this->getLastSyncDate());
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.zendesk.connector.user.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return self::IMPORT_ENTITY;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
