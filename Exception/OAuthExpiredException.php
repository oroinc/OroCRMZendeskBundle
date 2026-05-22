<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Exception;

use Exception;

/**
 * Thrown when OAuth authorization has expired and requires user reconnection.
 */
class OAuthExpiredException extends Exception implements ZendeskException
{
}
