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
                'normalized' => 'problem_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket'
            ),
            'collaborators' => array(
                'normalized' => 'collaborator_ids',
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
                'normalized' => 'requester_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User'
            ),
            'submitter' => array(
                'normalized' => 'submitter_id',
                'type' => 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User'
            ),
            'assignee' => array(
                'normalized' => 'assignee_id',
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
            'comments' => array(
                'type' => 'ArrayCollection<OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment>',
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
