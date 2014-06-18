<?php

namespace OroCRM\Bundle\ZendeskBundle\Exception;

use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

class RestException extends \Exception implements ZendeskException
{
    /**
     * @param string|null $message
     * @param RequestInterface|null $request
     * @param Response|null $response
     * @param \Exception $previous
     * @return RestException
     */
    public static function create(
        $message = null,
        RequestInterface $request = null,
        Response $response = null,
        \Exception $previous = null
    ) {
        $messageParts = [];

        if ($message) {
            $messageParts[] = '[error] ' . $message;
        }

        if (!$previous instanceof GuzzleException) {
            if ($request) {
                $messageParts[] = '[url] ' . $request->getUrl();
                $messageParts[] = '[method] ' . $request->getMethod();
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
}
