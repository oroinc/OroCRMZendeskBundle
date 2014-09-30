<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport;

use Psr\Log\LogLevel;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use OroCRM\Bundle\ZendeskBundle\Logger\AbstractLoggerDecorator;

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
