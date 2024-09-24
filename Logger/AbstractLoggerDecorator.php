<?php

namespace Oro\Bundle\ZendeskBundle\Logger;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * Allows to decorate logger with another logger
 */
abstract class AbstractLoggerDecorator implements LoggerInterface
{
    use LoggerAwareTrait;

    /**
     * Constructor allows us to pass logger when strategy is instantiating or whenever you want
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger ?: new NullLogger());
    }

    #[\Override]
    public function emergency($message, array $context = array())
    {
        return $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    #[\Override]
    public function alert($message, array $context = array())
    {
        return $this->log(LogLevel::ALERT, $message, $context);
    }

    #[\Override]
    public function critical($message, array $context = array())
    {
        return $this->log(LogLevel::CRITICAL, $message, $context);
    }

    #[\Override]
    public function error($message, array $context = array())
    {
        return $this->log(LogLevel::ERROR, $message, $context);
    }

    #[\Override]
    public function warning($message, array $context = array())
    {
        return $this->log(LogLevel::WARNING, $message, $context);
    }

    #[\Override]
    public function notice($message, array $context = array())
    {
        return $this->log(LogLevel::NOTICE, $message, $context);
    }

    #[\Override]
    public function info($message, array $context = array())
    {
        return $this->log(LogLevel::INFO, $message, $context);
    }

    #[\Override]
    public function debug($message, array $context = array())
    {
        return $this->log(LogLevel::DEBUG, $message, $context);
    }

    #[\Override]
    public function log($level, $message, array $context = array())
    {
        return $this->logger->log($level, $this->getMessage($level, $message, $context), $context);
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return string
     */
    abstract protected function getMessage($level, $message, array $context);
}
