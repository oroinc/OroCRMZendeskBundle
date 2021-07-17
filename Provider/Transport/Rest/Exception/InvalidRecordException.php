<?php

namespace Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

class InvalidRecordException extends RestException
{
    /**
     * Array of validation errors, for example
     *
     * array(
     *      'name' => array(
     *          'Name: is too short (minimum one character)'
     *      )
     * )
     *
     * @var array
     */
    protected $validationErrors;

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
        try {
            $data = $response->json();
        } catch (\Exception $exception) {
            $data = array();
        }

        $validationErrors = [];
        $validationErrorsAsString = '';
        static::parseValidationErrors($data, $validationErrors, $validationErrorsAsString);

        if ($validationErrorsAsString) {
            if (isset($data['description'])) {
                $validationErrorsAsString = $data['description'] . ':' . PHP_EOL . $validationErrorsAsString;
            } else {
                $validationErrorsAsString = 'Record validation errors:' . PHP_EOL . $validationErrorsAsString;
            }
            if ($message) {
                $message .= PHP_EOL . $validationErrorsAsString;
            } else {
                $message .= $validationErrorsAsString;
            }
        } elseif (!$message) {
            if (isset($data['description'])) {
                $message = $data['description'];
            } else {
                $message = 'Record invalid error.';
            }
        }

        /** @var InvalidRecordException $result */
        $result = new static($message, $response->getStatusCode(), $previous);
        $result->setResponse($response);
        $result->setValidationErrors($validationErrors);

        return $result;
    }

    /**
     * @param array $data
     * @param array $resultValidationErrors
     * @param string $validationErrorsAsString
     */
    protected static function parseValidationErrors(
        array $data,
        array &$resultValidationErrors,
        &$validationErrorsAsString
    ) {
        if (isset($data['details']) && is_array($data['details'])) {
            foreach ($data['details'] as $property => $validationErrors) {
                $propertyErrors = [];
                if (!is_array($validationErrors)) {
                    $validationErrors = [$validationErrors];
                }
                foreach ($validationErrors as $error) {
                    if (isset($error['description'])) {
                        $errorAsString = $error['description'];
                    } elseif (is_array($error)) {
                        $errorAsString = json_encode($error);
                    } else {
                        $errorAsString = (string)$error;
                    }
                    $propertyErrors[] = $errorAsString;
                    if ($validationErrorsAsString) {
                        $validationErrorsAsString .= PHP_EOL;
                    }
                    $validationErrorsAsString .= sprintf('[%s] %s', $property, $errorAsString);
                }
                $resultValidationErrors[$property] = $propertyErrors;
            }
        }
    }

    public function setValidationErrors(array $errors)
    {
        $this->validationErrors = $errors;
    }

    /**
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }
}
