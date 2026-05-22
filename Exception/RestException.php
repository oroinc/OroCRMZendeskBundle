<?php

namespace Oro\Bundle\ZendeskBundle\Exception;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An exception that represents Zendesk integration REST API transport errors
 */
class RestException extends Exception implements ZendeskException
{
    /**
     * @param string|null            $message
     * @param RequestInterface|null $request
     * @param ResponseInterface|null $response
     * @param Exception|null $previous
     * @return RestException
     */
    public static function create(
        $message = null,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
        ?Exception $previous = null
    ) {
        $messageParts = [];

        if ($message) {
            $messageParts[] = '[error] ' . $message;
        }

        if (!$previous instanceof GuzzleException) {
            if ($request) {
                $messageParts[] = '[url] '.(string)$request->getUri();
                $messageParts[] = '[method] '.$request->getMethod();
            }

            if ($response) {
                $messageParts[] = '[status code] ' . $response->getStatusCode();
                $messageParts[] = '[reason phrase] ' . $response->getReasonPhrase();
            }
        } else {
            $messageParts[] = $previous->getMessage();
        }

        $message = 'Zendesk API request error' . PHP_EOL . implode(PHP_EOL, $messageParts);

        return new static($message, 0, $previous);
    }

    /**
     * Factory method for creating exceptions from Zendesk REST responses.
     *
     * @param string|null $message
     * @param RestResponseInterface|null $response
     * @param Exception|null $exception
     * @return RestException
     */
    public static function createFromZendeskResponse(
        ?string $message = null,
        ?RestResponseInterface $response = null,
        ?Exception $exception = null
    ): self {
        $messageParts = [];

        if ($message) {
            $messageParts[] = '[error] ' . $message;
        }

        if (!$exception instanceof GuzzleException) {
            if ($response) {
                $messageParts[] = '[status code] ' . $response->getStatusCode();
            }
        } else {
            $messageParts[] = $exception->getMessage();
        }

        $message = 'Zendesk API request error' . PHP_EOL . implode(PHP_EOL, $messageParts);

        return new static($message, 0, $exception);
    }
}
