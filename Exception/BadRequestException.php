<?php

namespace OroCRM\Bundle\ZendeskBundle\Exception;

use Guzzle\Http\Exception\RequestException;

class BadRequestException extends RequestException implements Exception
{
}
