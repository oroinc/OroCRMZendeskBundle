<?php

namespace Oro\Bundle\ZendeskBundle\Handler;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Defines the contract for handling exceptions during import/export operations.
 */
interface ExceptionHandlerInterface
{
    /**
     * If expected exception return true else false
     *
     * @param \Exception        $exception
     * @param ContextInterface  $context
     * @return bool
     */
    public function process(\Exception $exception, ContextInterface $context);
}
