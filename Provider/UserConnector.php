<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

/**
 * Users connector
 */
class UserConnector extends AbstractZendeskConnector
{
    public const IMPORT_ENTITY = 'Oro\Bundle\ZendeskBundle\Entity\User';
    public const TYPE = 'user';
    public const IMPORT_JOB = 'zendesk_user_import';

    #[\Override]
    protected function getConnectorSource()
    {
        return $this->transport->getUsers($this->getLastSyncDate());
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.zendesk.connector.user.label';
    }

    #[\Override]
    public function getImportEntityFQCN()
    {
        return self::IMPORT_ENTITY;
    }

    #[\Override]
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    #[\Override]
    public function getType()
    {
        return self::TYPE;
    }
}
