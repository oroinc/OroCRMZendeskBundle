<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;

use OroCRM\Bundle\ZendeskBundle\Exception\ConfigurationException;

class ConfigurationProvider
{
    const EMAIL_FIELD_NAME = 'oro_crm_zendesk.zendesk_email';
    const API_TOKEN_FIELD_NAME = 'oro_crm_zendesk.zendesk_api_token';
    const SYNC_TIMEOUT_FIELD_NAME = 'oro_crm_zendesk.zendesk_sync_timeout';
    const ZENDESK_DEFAULT_USER_EMAIL_FIELD_NAME = 'oro_crm_zendesk.zendesk_default_user_email';
    const OROCRM_DEFAULT_USERNAME_FIELD_NAME = 'oro_crm_zendesk.orocrm_default_username';
    const USERNAME_FIELD_NAME = 'oro_crm_zendesk.zendesk_username';

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
        return $this->getConfigurationSetting(self::EMAIL_FIELD_NAME, true);
    }

    /**
     * @return string
     */
    public function getApiToken()
    {
        return $this->getConfigurationSetting(self::API_TOKEN_FIELD_NAME, true);
    }

    /**
     * @return int
     */
    public function getSyncTimeOut()
    {
        return $this->getConfigurationSetting(self::SYNC_TIMEOUT_FIELD_NAME);
    }

    /**
     * @return string
     */
    public function getZendeskDefaultUserEmail()
    {
        return $this->getConfigurationSetting(self::ZENDESK_DEFAULT_USER_EMAIL_FIELD_NAME);
    }

    /**
     * @return string
     */
    public function getSubDomain()
    {
        return $this->getConfigurationSetting(self::USERNAME_FIELD_NAME, true);
    }

    /**
     * @return null|User
     */
    public function getDefaultUser()
    {
        $username = $this->getConfigurationSetting(self::OROCRM_DEFAULT_USERNAME_FIELD_NAME);

        if (empty($username)) {
            return null;
        }

        $user = $this->entityManager->getRepository('OroUserBundle:User')
            ->findOneBy(array('username' => $username));

        return $user;
    }

    /**
     * Get configuration setting value
     *
     * @param string $name
     * @param bool $required
     * @return mixed
     * @throws ConfigurationException
     */
    protected function getConfigurationSetting($name, $required = false)
    {
        $value = $this->configManager->get($name);

        if ($value === null && $required) {
            throw ConfigurationException::settingValueRequired($name);
        }

        return $value;
    }
}
