<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider\Transport\Rest;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\AbstractRestTransport;

use OroCRM\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\RestException;
use OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;

/**
 * @link http://developer.zendesk.com/documentation/rest_api/introduction.html
 */
class ZendeskRestTransport extends AbstractRestTransport implements ZendeskTransportInterface
{
    const API_URL_PREFIX = 'api/v2';
    const ACTION_GET_USERS = 'getUsers';
    const ACTION_GET_TICKETS = 'getTickets';
    const ACTION_GET_TICKET_COMMENTS = 'getTicketComments';
    const COMMENT_EVENT_TYPE = 'Comment';

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/search.html
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
     * @link http://developer.zendesk.com/documentation/rest_api/search.html
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
     * @link http://developer.zendesk.com/documentation/rest_api/ticket_comments.html#listing-comments
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
     * @link http://developer.zendesk.com/documentation/rest_api/users.html#create-user
     */
    public function createUser(array $userData)
    {
        return $this->createEntity('users.json', 'user', $userData);
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/tickets.html#creating-tickets
     */
    public function createTicket(array $ticketData)
    {
        $responseData = [];
        $ticketData = $this->createEntity('tickets.json', 'ticket', $ticketData, $responseData);

        return [
            'ticket' => $ticketData,
            'comment' => $this->getCommentFromTicketResponse($responseData),
        ];
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/tickets.html#updating-tickets
     */
    public function updateTicket(array $ticketData)
    {
        if (!isset($ticketData['id'])) {
            throw new \InvalidArgumentException('Ticket data must have "id" value.');
        }
        $id = $ticketData['id'];
        return $this->updateEntity(sprintf('tickets/%d.json', $id), 'ticket', $ticketData);
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/tickets.html#creating-tickets
     */
    public function addTicketComment(array $commentData)
    {
        if (!isset($commentData['ticket_id'])) {
            throw new \InvalidArgumentException('Ticket comment data must have "ticket_id" value.');
        }
        $ticketId = $commentData['ticket_id'];
        unset($commentData['ticket_id']);
        $ticketData = ['comment' => $commentData];
        $this->updateEntity(sprintf('tickets/%d.json', $ticketId), 'ticket', $ticketData, $responseData);
        $result = $this->getCommentFromTicketResponse($responseData);

        if (!$result) {
            throw RestException::createFromResponse(
                $this->getClient()->getLastResponse(),
                'Can\'t get comment data from response.'
            );
        }

        return $result;
    }

    /**
     * @param array $responseData
     * @return array|null
     */
    protected function getCommentFromTicketResponse(array $responseData)
    {
        $result = null;

        if (isset($responseData['audit']['events']) && is_array($responseData['audit']['events'])) {
            foreach ($responseData['audit']['events'] as $event) {
                if (isset($event['type']) && $event['type'] == static::COMMENT_EVENT_TYPE) {
                    $result = $event;
                    unset($result['type']);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $resource
     * @param string $name "ticket" or "user"
     * @param array $entityData
     * @param array $responseData
     * @return array
     * @throws RestException
     */
    protected function createEntity($resource, $name, array $entityData, array &$responseData = null)
    {
        $response = $this->getClient()->post(
            $resource,
            [$name => $entityData]
        );

        if (201 !== $response->getStatusCode()) {
            throw RestException::createFromResponse(
                $response,
                sprintf('Can\'t create %s.', $name)
            );
        }

        try {
            $responseData = $response->json();
        } catch (\Exception $exception) {
            throw RestException::createFromResponse(
                $response,
                sprintf('Can\'t parse create %s response.', $name),
                $exception
            );
        }

        if (!isset($responseData[$name]) || !is_array($responseData[$name])) {
            throw RestException::createFromResponse($response, sprintf('Can\'t get %s data from response.', $name));
        }

        return $responseData[$name];
    }

    /**
     * @param string $resource
     * @param string $name "ticket" or "user"
     * @param array $entityData
     * @param array $responseData
     * @return array
     * @throws RestException
     */
    protected function updateEntity($resource, $name, array $entityData, array &$responseData = null)
    {
        unset($entityData['id']);

        $response = $this->getClient()->put(
            $resource,
            [$name => $entityData]
        );

        if (200 !== $response->getStatusCode()) {
            throw RestException::createFromResponse(
                $response,
                sprintf('Can\'t update %s.', $name)
            );
        }

        try {
            $responseData = $response->json();
        } catch (\Exception $exception) {
            throw RestException::createFromResponse(
                $response,
                sprintf('Can\'t parse update %s response.', $name),
                $exception
            );
        }

        if (!isset($responseData[$name]) || !is_array($responseData[$name])) {
            throw RestException::createFromResponse($response, sprintf('Can\'t get %s data from response.', $name));
        }

        return $responseData[$name];
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
        return rtrim($parameterBag->get('url'), '/') . '/' . ltrim(static::API_URL_PREFIX, '/');
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
