<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider;

use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;

use Oro\Bundle\ZendeskBundle\Model\SyncState;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

trait ExecutionContextTrait
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $executionContext;

    protected function initExecutionContext()
    {
        $this->executionContext = $this->createMock(ExecutionContext::class);
        $this->executionContext
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY:
                            return [];
                        default:
                            return null;
                    }
                }
            );

        return $this->executionContext;
    }

    /**
     * @return \Closure
     */
    protected function getIsUpdatedLastSyncDateCallback()
    {
        $isUpdatedLastSyncDate = false;

        /**
         * @var $this \PHPUnit_Framework_TestCase
         */
        $this->executionContext
            ->expects($this->atLeastOnce())
            ->method('put')
            ->willReturnCallback(
                function ($id, $value) use (&$isUpdatedLastSyncDate) {
                    if (ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY === $id &&
                        isset($value[SyncState::LAST_SYNC_DATE_KEY])
                    ) {
                        /**
                         * Check if date string contains specific date format
                         */
                        $isUpdatedLastSyncDate = false !== \DateTime::createFromFormat(
                            \DateTime::ISO8601,
                            $value[SyncState::LAST_SYNC_DATE_KEY]
                        );
                    }
                }
            );

        return function () use (&$isUpdatedLastSyncDate) {
            return $isUpdatedLastSyncDate;
        };
    }
}
