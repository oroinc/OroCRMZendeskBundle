<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindForRenamedEntitiesMigration;

class MigrateNotesRelations extends UpdateNoteAssociationKindForRenamedEntitiesMigration
{
    protected $entitiesNames = [
        'Ticket',
        'TicketComment',
        'TicketPriority',
        'TicketStatus',
        'TicketType',
        'User',
        'UserRole',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getRenamedEntitiesNames(Schema $schema)
    {
        $oldNameSpace = 'OroCRM\Bundle\ZendeskBundle\Entity';
        $newNameSpace = 'Oro\Bundle\ZendeskBundle\Entity';

        $renamedEntityNamesMapping = [];
        foreach ($this->entitiesNames as $entityName) {
            $renamedEntityNamesMapping["$newNameSpace\\$entityName"] = "$oldNameSpace\\$entityName";
        }

        return $renamedEntityNamesMapping;
    }
}
