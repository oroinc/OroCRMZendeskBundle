<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;

class ConfigurationProvider
{
    const EMAIL_FIELD_NAME = 'oro_crm_zendesk.zendesk_email';
    const API_TOKEN_FIELD_NAME = 'oro_crm_zendesk.zendesk_api_token';
    const SYNC_TIMEOUT_FIELD_NAME = 'oro_crm_zendesk.zendesk_sync_timeout';
    const ZENDESK_DEFAULT_USER_FIELD_NAME = 'oro_crm_zendesk.zendesk_default_user';
    const OROCRM_DEFAULT_USER_FIELD_NAME = 'oro_crm_zendesk.orocrm_default_user';
    const USERNAME_FIELD_NAME = 'oro_crm_zendesk.zendesk_usermane';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(EntityManager $entityManager, ConfigManager $configManager)
    {
        $this->entityManager = $entityManager;
        $this->configManager = $configManager;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->configManager->get(self::EMAIL_FIELD_NAME);
    }

    /**
     * @return string
     */
    public function getApiToken()
    {
        return $this->configManager->get(self::API_TOKEN_FIELD_NAME);
    }

    /**
     * @return int
     */
    public function getSyncTimeOut()
    {
        return $this->configManager->get(self::SYNC_TIMEOUT_FIELD_NAME);
    }

    /**
     * @return string
     */
    public function getZendeskDefaultUser()
    {
        return $this->configManager->get(self::ZENDESK_DEFAULT_USER_FIELD_NAME);
    }

    public function getSubDomain()
    {
        return $this->configManager->get(self::USERNAME_FIELD_NAME);
    }

    /**
     * @return null|User
     */
    public function getDefaultUser()
    {
        $username = $this->configManager->get(self::OROCRM_DEFAULT_USER_FIELD_NAME);

        if (empty($username)) {
            return null;
        }

        $user = $this->entityManager->getRepository('OroUserBundle:User')
            ->findOneBy(array('username' => $username));

        return $user;
    }
}
