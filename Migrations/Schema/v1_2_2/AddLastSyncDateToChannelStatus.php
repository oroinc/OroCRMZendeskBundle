<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Schema\v1_2_2;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ZendeskBundle\Model\SyncState;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;
use Oro\Bundle\ZendeskBundle\Provider\TicketConnector;
use Oro\Bundle\ZendeskBundle\Provider\UserConnector;
use Psr\Log\LoggerInterface;

class AddLastSyncDateToChannelStatus extends ParametrizedMigrationQuery implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new self());
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return "This migration update field 'data' " .
            "in table 'oro_integration_channel_status' (this table keeps data about last sync with integration) " .
            "with information about 'last sync datetime' " .
            "for all records that relate to channel 'zendesk' and one of next connectors 'user' or 'ticket'";
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $selectStatusQuery = <<<SQL
SELECT main.id, main.date, main.data FROM oro_integration_channel_status main WHERE main.id IN (
SELECT MAX(s.id) AS id
FROM oro_integration_channel_status s
INNER JOIN oro_integration_channel ch ON ch.type = :channel_type AND s.channel_id = ch.id
WHERE s.code = :completed_status_code AND s.connector IN (:connectors)
GROUP BY s.channel_id, s.connector, s.code )
SQL;
        $selectStatusParams = [
            'channel_type'          => ChannelType::TYPE,
            'completed_status_code' => (string) Status::STATUS_COMPLETED,
            'connectors'            => [UserConnector::TYPE, TicketConnector::TYPE]
        ];

        $selectStatusTypes = [
            'channel_type'          => Types::STRING,
            'completed_status_code' => Types::STRING,
            'connectors'            => Connection::PARAM_STR_ARRAY
        ];

        $this->logQuery($logger, $selectStatusQuery, $selectStatusParams, $selectStatusTypes);
        $result = $this->connection->fetchAll($selectStatusQuery, $selectStatusParams, $selectStatusTypes);

        $jsonArrayType = Type::getType(Types::JSON_ARRAY);
        $platform = $this->connection->getDatabasePlatform();

        try {
            $this->connection->beginTransaction();
            foreach ($result as $statusRecord) {
                $id = $statusRecord['id'];
                $statusDateRaw = $statusRecord['date'];
                $statusDataRaw = $statusRecord['data'];
                $statusData = $jsonArrayType->convertToPHPValue($statusDataRaw, $platform);

                if (!isset($statusData[SyncState::LAST_SYNC_DATE_KEY])) {
                    $statusDate = new \DateTime($statusDateRaw, new \DateTimeZone('UTC'));
                    $statusDate->setTime(0, 0, 0);
                    $statusData[SyncState::LAST_SYNC_DATE_KEY] = $statusDate->format(\DateTime::ISO8601);
                    $this->connection->update(
                        'oro_integration_channel_status',
                        ['data' => $statusData],
                        ['id' => $id],
                        [Types::JSON_ARRAY]
                    );
                }
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
