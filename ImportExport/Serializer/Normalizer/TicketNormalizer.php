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
            array(
                'denormalized' => 'originId',
                'normalized' => 'id',
                'primary' => true,
            ),
            'url',
            'external_id',
            'subject',
            'description',
            'recipient',
            'has_incidents',
            'problem' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket'
            ),
            'collaborators' => array(
                'type' => 'ArrayCollection<OroCRM\\Bundle\\ZendeskBundle\\Entity\\User>',
            ),
            'type' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketType'
            ),
            'status' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketStatus'
            ),
            'priority' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketPriority'
            ),
            'requester' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User'
            ),
            'submitter' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User'
            ),
            'assignee' => array(
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User'
            ),
            'has_incidents',
            'due_at' => array(
                'type' => 'DateTime',
                'context' => array('type' => 'datetime'),
            ),
            'created_at' => array(
                'type' => 'DateTime',
                'context' => array('type' => 'datetime'),
            ),
            'updated_at' => array(
                'type' => 'DateTime',
                'context' => array('type' => 'datetime'),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetClassName()
    {
        return 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket';
    }
}
