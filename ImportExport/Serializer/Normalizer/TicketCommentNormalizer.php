<?php

namespace Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\User;

/**
 * Normalizes/denormalizes a ticket comment.
 */
class TicketCommentNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldRules(): array
    {
        return [
            'originId' => [
                'normalizeName' => 'id',
                'primary' => true,
            ],
            'author' => [
                'normalizeName' => 'author_id',
                'type' => User::class,
                'context' => ['mode' => self::SHORT_MODE],
            ],
            'body',
            'htmlBody' => [
                'normalizeName' => 'html_body',
                'normalize' => false,
            ],
            'public',
            'originCreatedAt' => [
                'type' => 'DateTime',
                'normalizeName' => 'created_at',
                'normalize' => false,
                'context' => ['type' => 'datetime'],
            ],
            'ticket' => [
                'normalizeName' => 'ticket_id',
                'type' => Ticket::class,
                'context' => ['mode' => self::SHORT_MODE],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (is_array($data) && isset($context['ticket_id'])) {
            $data['ticket_id'] = $context['ticket_id'];
        }

        return parent::denormalize($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName(): string
    {
        return TicketComment::class;
    }
}
