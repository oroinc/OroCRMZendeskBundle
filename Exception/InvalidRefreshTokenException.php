<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Exception;

use Exception;

/**
 * Thrown when a Zendesk OAuth refresh token is invalid or expired.
 */
class InvalidRefreshTokenException extends Exception implements ZendeskException
{
}
