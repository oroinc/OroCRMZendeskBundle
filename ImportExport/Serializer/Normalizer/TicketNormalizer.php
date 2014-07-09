<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer;

class TicketNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldRules()
    {
        return array(
            'originId' => array(
                'normalizeName' => 'id',
                'primary' => true,
            ),
            'url' => array(
                'normalize' => false,
            ),
            'external_id',
            'subject',
            'description' => array(
                'normalize' => false,
            ),
            'recipient' => array(
                'normalize' => false,
            ),
            'has_incidents' => array(
                'normalize' => false,
            ),
            'problem' => array(
                'normalize' => false,
                'normalizeName' => 'problem_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket'
            ),
            'collaborators' => array(
                'normalizeName' => 'collaborator_ids',
                'type' => 'ArrayCollection<OroCRM\\Bundle\\ZendeskBundle\\Entity\\User>',
                'context' => array('mode' => self::SHORT_MODE),
            ),
            'type' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketType',
                'context' => array('mode' => self::SHORT_MODE),
            ),
            'status' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketStatus',
                'context' => array('mode' => self::SHORT_MODE),
            ),
            'priority' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketPriority',
                'context' => array('mode' => self::SHORT_MODE),
            ),
            'requester' => array(
                'normalizeName' => 'requester_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
                'context' => array('mode' => self::SHORT_MODE),
            ),
            'submitter' => array(
                'normalizeName' => 'submitter_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
                'context' => array('mode' => self::SHORT_MODE),
            ),
            'assignee' => array(
                'normalizeName' => 'assignee_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
                'context' => array('mode' => self::SHORT_MODE),
            ),
            'due_at' => array(
                'type' => 'DateTime',
                'context' => array('type' => 'datetime'),
            ),
            'originCreatedAt' => array(
                'type' => 'DateTime',
                'normalize' => false,
                'normalizeName' => 'created_at',
                'context' => array('type' => 'datetime'),
            ),
            'originUpdatedAt' => array(
                'type' => 'DateTime',
                'normalize' => false,
                'normalizeName' => 'updated_at',
                'context' => array('type' => 'datetime'),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $result = parent::normalize($object, $format, $context);

        if (!is_array($result) || $object->getOriginId()) {
            return $result;
        }

        // Comment is required by Zendesk API when create ticket
        // @see http://developer.zendesk.com/documentation/rest_api/tickets.html#creating-tickets
        unset($result['id']);
        $result['comment'] = array(
            'body' => $object->getDescription(),
        );

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket';
    }
}
