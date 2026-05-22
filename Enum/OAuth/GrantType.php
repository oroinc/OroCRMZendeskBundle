<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Enum\OAuth;

/**
 * OAuth grant types supported by Zendesk OAuth flow
 */
enum GrantType: string
{
    case AUTHORIZATION_CODE = 'authorization_code';
    case REFRESH_TOKEN = 'refresh_token';
}
