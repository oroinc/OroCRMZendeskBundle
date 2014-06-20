<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use OroCRM\Bundle\ZendeskBundle\Logger\AbstractLoggerDecorator;

class SyncLogger extends AbstractLoggerDecorator
{
    /**
     * @var string
     */
    protected $messagePrefix = '';

    /**
     * {@inheritdoc}
     */
    protected function getMessage($level, $message, array $context)
    {
        return $this->messagePrefix . $message;
    }

    /**
     * @param string $prefix
     */
    public function setMessagePrefix($prefix)
    {
        $this->messagePrefix = $prefix;
    }
}
