<?php

namespace Oro\Bundle\ZendeskBundle\Exception;

class ConfigurationException extends \Exception implements ZendeskException
{
    /**
     * @param string $name
     * @return ConfigurationException
     */
    public static function settingValueRequired($name)
    {
        $message = sprintf('Configuration setting "%s" is required.', $name);
        return new static($message);
    }
}
