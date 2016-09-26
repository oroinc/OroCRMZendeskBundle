<?php

namespace Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException as BaseException;
use Oro\Bundle\ZendeskBundle\Exception\ZendeskException;

class RestException extends BaseException implements ZendeskException
{
    /**
     * @param \Exception $exception
     * @return mixed
     */
    public static function checkInvalidRecordException(\Exception $exception)
    {
        if ($exception instanceof BaseException &&
            $exception->getResponse() &&
            $exception->getResponse()->isClientError()
        ) {
            return InvalidRecordException::createFromResponse($exception->getResponse(), null, $exception);
        }
        return $exception;
    }

    /**
     * @param RestResponseInterface $response
     * @param string|null $message
     * @param \Exception|null $previous
     * @return RestException
     */
    public static function createFromResponse(
        RestResponseInterface $response,
        $message = null,
        \Exception $previous = null
    ) {
        if ($response->isClientError()) {
            return InvalidRecordException::createFromResponse($response, $message, $previous);
        }

        return parent::createFromResponse($response, $message, $previous);
    }
}
