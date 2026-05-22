<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Enum\OAuth;

use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

/**
 * OAuth scopes for Zendesk API access
 */
enum Scope: string
{
    case READ_WRITE = 'read write';
    case READ = 'read';

    /**
     * Determines the appropriate scope based on two-way sync configuration
     */
    public static function fromTwoWaySync(bool $isTwoWaySyncEnabled): self
    {
        return $isTwoWaySyncEnabled ? self::READ_WRITE : self::READ;
    }

    /**
     * Determines the appropriate scope based on transport synchronization settings
     */
    public static function fromTransport(ZendeskRestTransport $transport): self
    {
        $isTwoWaySyncEnabled = (bool) $transport->getChannel()
            ?->getSynchronizationSettings()
            ?->offsetGetOr('isTwoWaySyncEnabled', false);

        return self::fromTwoWaySync($isTwoWaySyncEnabled);
    }
}
