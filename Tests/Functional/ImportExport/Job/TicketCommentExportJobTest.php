<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Job;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\ZendeskBundle\Provider\TicketCommentConnector;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\InvalidRecordException;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadTicketData;

/**
 * @dbIsolationPerTest
 */
class TicketCommentExportJobTest extends AbstractImportExportJobTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadTicketData::class]);
    }

    public function testExportTicketCommentForCloseTicket()
    {
        /** @var Channel $channel */
        $channel = $this->getReference('zendesk_channel:first_test_channel');
        $ticketComment = $this->getReference('zendesk_ticket_42_comment_3');

        $exception = new InvalidRecordException('', 422);

        $this->resource->expects($this->once())
            ->method('addTicketComment')
            ->with($ticketComment)
            ->willThrowException($exception);

        $jobLog = [];

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            TicketCommentConnector::TYPE,
            [
                'id' => $ticketComment->getId()
            ],
            $jobLog
        );

        $this->assertTrue($result);

        /** @var Status $status */
        $status = $channel->getStatuses()->first();

        self::assertStringContainsString('Some entities were skipped due to warnings', $status->getMessage());
        self::assertStringContainsString(
            'Error ticket comment not exported because ticket is closed',
            $status->getMessage()
        );
    }
}
