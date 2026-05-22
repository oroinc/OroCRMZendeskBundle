<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;

/**
 * Backfills authorization type for existing Zendesk transports.
 */
class FillAuthorizationTypeForExistingTransports implements Migration, OrderedMigrationInterface
{
    private const TABLE_NAME = 'oro_integration_transport';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new SqlMigrationQuery([sprintf(
            "UPDATE %s SET orocrm_zd_authorization_type = '%s'"
                . " WHERE type = 'zendeskresttransport' AND orocrm_zd_authorization_type IS NULL",
            self::TABLE_NAME,
            AuthorizationType::EMAIL_TOKEN->value
        )]));
    }

    #[\Override]
    public function getOrder(): int
    {
        return 200;
    }
}
