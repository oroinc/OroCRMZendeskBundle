<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider\Transport\Rest;

use Symfony\Component\HttpFoundation\ParameterBag;

use OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\AbstractRestTransport;

class ZendeskRestTransport extends AbstractRestTransport implements ZendeskTransportInterface
{
    const ACTION_GET_USERS = 'getUsers';
    const ACTION_GET_TICKETS = 'getTickets';
    const ACTION_GET_TICKET_COMMENTS = 'getTicketComments';

    /**
     * {@inheritdoc}
     */
    public function getUsers(\DateTime $lastUpdatedAt = null)
    {
        $query = 'type:user';
        if ($lastUpdatedAt) {
            $query .= sprintf(' updated>%s', $lastUpdatedAt->format(\DateTime::ISO8601));
        }

        return new ZendeskRestIterator(
            $this->getClient(),
            'search.json',
            'results',
            [
                'query' => $query,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTickets(\DateTime $lastUpdatedAt = null)
    {
        $query = 'type:ticket';
        if ($lastUpdatedAt) {
            $query .= sprintf(' updated>%s', $lastUpdatedAt->format(\DateTime::ISO8601));
        }

        return new ZendeskRestIterator(
            $this->getClient(),
            'search.json',
            'results',
            [
                'query' => $query,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTicketComments($ticketId)
    {
        if (!$ticketId) {
            return new \EmptyIterator();
        }

        $sourceIterator = new ZendeskRestIterator(
            $this->getClient(),
            sprintf('tickets/%s/comments.json', $ticketId),
            'comments'
        );

        $callback = function (&$current) use ($ticketId) {
            if (is_array($current)) {
                $current['ticket_id'] = $ticketId;
            }
            return true;
        };

        return new \CallbackFilterIterator($sourceIterator, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.zendesk.transport.rest.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return 'orocrm_zendesk_rest_transport_setting_form_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\ZendeskRestTransport';
    }

    /**
     * {@inheritdoc}
     */
    protected function getClientBaseUrl(ParameterBag $parameterBag)
    {
        return $parameterBag->get('url');
    }

    /**
     * {@inheritdoc}
     */
    protected function getClientOptions(ParameterBag $parameterBag)
    {
        $email = $parameterBag->get('email');
        $token = $parameterBag->get('token');
        return array(
            'auth' => array("{$email}/token", $token)
        );
    }
}
