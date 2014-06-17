<?php

namespace OroCRM\Bundle\ZendeskBundle\Exception;

use Guzzle\Http\Exception\BadResponseException as GuzzleBadResponseException;

class BadResponseException extends GuzzleBadResponseException implements Exception
{
}
