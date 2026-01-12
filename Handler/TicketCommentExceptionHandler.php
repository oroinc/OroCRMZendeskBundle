<?php

namespace Oro\Bundle\ZendeskBundle\Handler;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\InvalidRecordException;

/**
 * Handles exceptions that occur during ticket comment export operations.
 */
class TicketCommentExceptionHandler implements ExceptionHandlerInterface
{
    public const TICKED_IS_CLOSED_ERROR_CODE = 422;

    /** @var string[] */
    protected $errors = [
        self::TICKED_IS_CLOSED_ERROR_CODE => 'Error ticket comment not exported because ticket is closed'
    ];

    #[\Override]
    public function process(\Exception $exception, ContextInterface $context)
    {
        if (!($exception instanceof InvalidRecordException)) {
            return false;
        }

        if (isset($this->errors[$exception->getCode()])) {
            $context->addError($this->errors[$exception->getCode()]);
            $context->incrementErrorEntriesCount();
            return true;
        }

        return false;
    }
}
