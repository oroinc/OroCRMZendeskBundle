<?php

namespace Oro\Bundle\ZendeskBundle\Handler;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

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
