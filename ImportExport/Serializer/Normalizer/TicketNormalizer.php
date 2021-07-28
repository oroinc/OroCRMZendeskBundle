<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;
use Oro\Bundle\ZendeskBundle\Entity\User;

/**
 * Normalizes/denormalizes a ticket.
 */
class TicketNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldRules()
    {
        return [
            'originId' => [
                'normalizeName' => 'id',
                'primary' => true,
            ],
            'url' => [
                'normalize' => false,
            ],
            'external_id',
            'subject',
            'description' => [
                'normalize' => false,
            ],
            'recipient' => [
                'normalize' => false,
            ],
            'has_incidents' => [
                'normalize' => false,
            ],
            'problem' => [
                'normalize' => false,
                'normalizeName' => 'problem_id',
                'type' => Ticket::class,
            ],
            'collaborators' => [
                'normalizeName' => 'collaborator_ids',
                'type' => 'ArrayCollection<Oro\\Bundle\\ZendeskBundle\\Entity\\User>',
                'context' => ['mode' => self::SHORT_MODE],
            ],
            'type' => [
                'type' => TicketType::class,
                'context' => ['mode' => self::SHORT_MODE],
            ],
            'status' => [
                'type' => TicketStatus::class,
                'context' => ['mode' => self::SHORT_MODE],
            ],
            'priority' => [
                'type' => TicketPriority::class,
                'context' => ['mode' => self::SHORT_MODE],
            ],
            'requester' => [
                'normalizeName' => 'requester_id',
                'type' => User::class,
                'context' => ['mode' => self::SHORT_MODE],
            ],
            'submitter' => [
                'normalizeName' => 'submitter_id',
                'type' => User::class,
                'context' => ['mode' => self::SHORT_MODE],
            ],
            'assignee' => [
                'normalizeName' => 'assignee_id',
                'type' => User::class,
                'context' => ['mode' => self::SHORT_MODE],
            ],
            'due_at' => [
                'type' => 'DateTime',
                'context' => ['type' => 'datetime'],
            ],
            'originCreatedAt' => [
                'type' => 'DateTime',
                'normalize' => false,
                'normalizeName' => 'created_at',
                'context' => ['type' => 'datetime'],
            ],
            'originUpdatedAt' => [
                'type' => 'DateTime',
                'normalize' => false,
                'normalizeName' => 'updated_at',
                'context' => ['type' => 'datetime'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $result = parent::normalize($object, $format, $context);

        if (!is_array($result) || $object->getOriginId()) {
            return $result;
        }

        // Comment is required by Zendesk API when create ticket
        // @see http://developer.zendesk.com/documentation/rest_api/tickets.html#creating-tickets
        unset($result['id']);
        $result['comment'] = [
            'body' => $object->getDescription(),
        ];

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return Ticket::class;
    }
}
