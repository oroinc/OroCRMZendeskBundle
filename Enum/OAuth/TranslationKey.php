<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Enum\OAuth;

/**
 * Translation keys for OAuth flow messages and errors
 */
enum TranslationKey: string
{
    case USER_DENIED = 'oro.zendesk.oauth.error.user_denied';
    case INVALID_REQUEST = 'oro.zendesk.oauth.error.invalid_request';
    case TRANSPORT_NOT_FOUND = 'oro.zendesk.oauth.error.transport_not_found';
    case SUCCESS_CONNECTED = 'oro.zendesk.oauth.success.connected';
    case EXCHANGE_FAILED = 'oro.zendesk.oauth.error.exchange_failed';
    case GENERAL_ERROR = 'oro.zendesk.oauth.error.general';
    case AUTHORIZE_FAILED = 'oro.zendesk.oauth.error.authorize_failed';
}
