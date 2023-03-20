<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Provider;

use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\ZendeskBundle\Model\SyncState;

trait ExecutionContextTrait
{
    /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject */
    private $executionContext;

    private function initExecutionContext()
    {
        $this->executionContext = $this->createMock(ExecutionContext::class);
        $this->executionContext->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($id) {
                if (ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY === $id) {
                    return [];
                }

                return null;
            });

        return $this->executionContext;
    }

    private function getIsUpdatedLastSyncDateCallback(): \Closure
    {
        $isUpdatedLastSyncDate = false;

        $this->executionContext->expects($this->atLeastOnce())
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

                    return $this->executionContext;
                }
            );

        return function () use (&$isUpdatedLastSyncDate) {
            return $isUpdatedLastSyncDate;
        };
    }
}
