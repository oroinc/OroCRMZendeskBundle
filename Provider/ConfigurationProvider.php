<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

use Guzzle\Http\Url;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;

use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use OroCRM\Bundle\ZendeskBundle\Exception\ConfigurationException;

class ConfigurationProvider
{
    const API_EMAIL_FIELD_NAME = 'oro_crm_zendesk.api_email';
    const API_TOKEN_FIELD_NAME = 'oro_crm_zendesk.api_token';
    const CRON_SCHEDULE_FIELD_NAME = 'oro_crm_zendesk.zendesk_cron_schedule';
    const ZENDESK_DEFAULT_USER_EMAIL_FIELD_NAME = 'oro_crm_zendesk.zendesk_default_user_email';
    const ORO_DEFAULT_USERNAME_FIELD_NAME = 'oro_crm_zendesk.oro_default_username';
    const ZENDESK_URL_FIELD_NAME = 'oro_crm_zendesk.zendesk_url';

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
        return $this->getConfigurationSetting(self::API_EMAIL_FIELD_NAME, true);
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
    public function getCronSchedule()
    {
        return $this->getConfigurationSetting(self::CRON_SCHEDULE_FIELD_NAME);
    }

    /**
     * @return string
     */
    public function getZendeskDefaultUserEmail()
    {
        return $this->getConfigurationSetting(self::ZENDESK_DEFAULT_USER_EMAIL_FIELD_NAME);
    }

    /**
     * @return ZendeskUser|null
     */
    public function getZendeskDefaultUser()
    {
        $email = $this->getZendeskDefaultUserEmail();

        if (!$email) {
            return null;
        }

        $user = $this->entityManager->getRepository('OroCRMZendeskBundle:User')
            ->findOneBy(array('email' => $email));

        return $user;
    }

    /**
     * @return string
     */
    public function getZendeskUrl()
    {
        $url = $this->getConfigurationSetting(self::ZENDESK_URL_FIELD_NAME, true);

        $url = Url::factory($url);
        $scheme = $url->getScheme();

        if (empty($scheme)) {
            $url->setHost($url->getPath())
                ->setPath('')
                ->setScheme('https');
        }

        return (string)$url;
    }

    /**
     * @return string
     */
    public function getOroDefaultUsername()
    {
        return $this->getConfigurationSetting(self::ORO_DEFAULT_USERNAME_FIELD_NAME);
    }

    /**
     * @return null|User
     */
    public function getOroDefaultUser()
    {
        $username = $this->getOroDefaultUsername();

        if (!$username) {
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

        if ($value === '' && $required) {
            throw ConfigurationException::settingValueRequired($name);
        }

        return $value;
    }
}
