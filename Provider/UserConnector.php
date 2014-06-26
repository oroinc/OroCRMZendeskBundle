<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

class UserConnector extends AbstractZendeskConnector
{
    const IMPORT_ENTITY = 'OroCRM\Bundle\ZendeskBundle\Entity\User';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return $this->transport->getUsers($this->syncState->getLastSyncDate());
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.zendesk.connector.user.label';
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
        return 'zendesk_user_import';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'user';
    }
}
