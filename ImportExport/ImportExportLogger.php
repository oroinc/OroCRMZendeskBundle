<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ZendeskBundle\Logger\AbstractLoggerDecorator;
use Psr\Log\LogLevel;

class ImportExportLogger extends AbstractLoggerDecorator implements ContextAwareInterface
{
    /**
     * @var string
     */
    protected $messagePrefix = '';

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * {@inheritdoc}
     */
    protected function getMessage($level, $message, array $context)
    {
        return $this->messagePrefix . $message;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        if ($this->context
            && in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR])
        ) {
            $this->context->addError($message);
        }
        return parent::log($level, $message, $context);
    }

    /**
     * @param string $prefix
     */
    public function setMessagePrefix($prefix)
    {
        $this->messagePrefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }
}
