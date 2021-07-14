<?php

namespace Oro\Bundle\ZendeskBundle\Provider\Transport\Rest;

use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\AbstractRestTransport;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport as ZendeskTransportSettingsEntity;
use Oro\Bundle\ZendeskBundle\Form\Type\RestTransportSettingsFormType;
use Oro\Bundle\ZendeskBundle\Provider\Transport\Rest\Exception\RestException;
use Oro\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Contains methods for getting and creating tickets, users, ticket comments
 *
 * @link http://developer.zendesk.com/documentation/rest_api/introduction.html
 */
class ZendeskRestTransport extends AbstractRestTransport implements ZendeskTransportInterface
{
    const API_URL_PREFIX = 'api/v2';
    const ACTION_GET_USERS = 'getUsers';
    const ACTION_GET_TICKETS = 'getTickets';
    const ACTION_GET_TICKET_COMMENTS = 'getTicketComments';
    const COMMENT_EVENT_TYPE = 'Comment';

    /** @var SerializerInterface */
    protected $serializer;

    /** @var string */
    private $resultIteratorClass;

    public function __construct(SerializerInterface $serializer, ?string $resultIteratorClass = null)
    {
        $this->serializer = $serializer;
        $this->resultIteratorClass = $resultIteratorClass ?? ZendeskRestIterator::class;
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/search.html
     */
    public function getUsers(\DateTime $lastSyncDate = null)
    {
        return $this->getSearchResult(User::class, $lastSyncDate);
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/search.html
     */
    public function getTickets(\DateTime $lastSyncDate = null)
    {
        return $this->getSearchResult(Ticket::class, $lastSyncDate);
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

        $result = new $this->resultIteratorClass(
            $this->getClient(),
            sprintf('tickets/%s/comments.json', $ticketId),
            'comments'
        );

        $result->setupDeserialization($this->serializer, TicketComment::class, ['ticket_id' => $ticketId]);

        return $result;
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/users.html#create-user
     */
    public function createUser(User $user)
    {
        $userData = $this->normalize($user);

        return $this->denormalize($this->createEntity('users.json', 'user', $userData), User::class);
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/tickets.html#creating-tickets
     */
    public function createTicket(Ticket $ticket)
    {
        $ticketData = $this->normalize($ticket);

        $responseData = [];
        $ticketData = $this->createEntity('tickets.json', 'ticket', $ticketData, $responseData);
        $commentData = $this->getCommentFromTicketResponse($responseData);

        $resultTicket = $this->denormalize($ticketData, Ticket::class);

        $resultComment = null;
        if ($commentData) {
            $resultComment = $this->denormalize($commentData, TicketComment::class);
        }

        return [
            'ticket' => $resultTicket,
            'comment' => $resultComment,
        ];
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/tickets.html#getting-tickets
     */
    public function getTicket($id)
    {
        $response = $this->client->get(
            sprintf('tickets/%d.json', $id)
        );

        if (!$response->isSuccessful()) {
            throw RestException::createFromResponse(
                $response,
                sprintf('Can\'t get ticket [origin_id=%s].', $id)
            );
        }

        try {
            $responseData = $response->json();
        } catch (\Exception $exception) {
            throw RestException::createFromResponse($response, 'Can\'t parse get ticket response.', $exception);
        }

        $ticketData = $responseData['ticket'] ?? null;

        return $this->denormalize($ticketData, Ticket::class);
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/tickets.html#updating-tickets
     */
    public function updateTicket(Ticket $ticket)
    {
        if (!$ticket->getOriginId()) {
            throw new \InvalidArgumentException('Ticket must have "originId" value.');
        }

        $id = $ticket->getOriginId();
        $ticketData = $this->normalize($ticket);
        $updatedTicketData = $this->updateEntity(sprintf('tickets/%d.json', $id), 'ticket', $ticketData);

        return $this->denormalize($updatedTicketData, Ticket::class);
    }

    /**
     * {@inheritdoc}
     * @link http://developer.zendesk.com/documentation/rest_api/tickets.html#creating-tickets
     */
    public function addTicketComment(TicketComment $comment)
    {
        if (!$comment->getTicket() || !$comment->getTicket()->getOriginId()) {
            throw new \InvalidArgumentException('Ticket comment data must have "ticket" with "originId" value.');
        }
        $ticketId = $comment->getTicket()->getOriginId();

        $commentData = $this->normalize($comment);
        $ticketData = ['comment' => $commentData];
        $this->updateEntity(sprintf('tickets/%d.json', $ticketId), 'ticket', $ticketData, $responseData);
        $createdTicketData = $this->getCommentFromTicketResponse($responseData);

        if (!$createdTicketData) {
            throw RestException::createFromResponse(
                $this->getClient()->getLastResponse(),
                'Can\'t get comment data from response.'
            );
        }

        return $this->denormalize($createdTicketData, TicketComment::class);
    }

    /**
     * @param string $classType
     * @param \DateTime|null $lastUpdatedAt
     *
     * @return ZendeskRestIterator
     */
    protected function getSearchResult($classType, \DateTime $lastUpdatedAt = null)
    {
        if (!defined(sprintf('%s::%s', $classType, 'SEARCH_TYPE'))) {
            throw new InvalidConfigurationException(
                sprintf(
                    "Class `%s` must contain constant `SEARCH_TYPE` to make search request !",
                    $classType
                )
            );
        }

        $query = sprintf(
            'type:%s',
            $classType::SEARCH_TYPE
        );

        $dateFilter = $this->getDateFilter($lastUpdatedAt);
        if (is_string($dateFilter)) {
            $query = sprintf(
                '%s %s',
                $query,
                $dateFilter
            );
        }

        $requestParams = array_merge(['query' => $query], $this->getSortingParams());

        $result = new $this->resultIteratorClass(
            $this->getClient(),
            'search.json',
            'results',
            $requestParams
        );

        $result->setupDeserialization($this->serializer, $classType);

        return $result;
    }

    /**
     * Sorting params that help to stabilize page result to prevent duplication within batch items
     *
     * @return array
     */
    protected function getSortingParams()
    {
        return [
            'sort_by' => 'created_at',
            'sort_order' => 'asc',
        ];
    }

    /**
     * @param \DateTime|null $lastUpdatedAt
     *
     * @return string
     */
    protected function getDateFilter(\DateTime $lastUpdatedAt = null)
    {
        if ($lastUpdatedAt) {
            return sprintf(
                'updated>=%s',
                $lastUpdatedAt->format(\DateTime::ISO8601)
            );
        }

        $todayDateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        return sprintf('created<=%s', $todayDateTime->format(\DateTime::ISO8601));
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
                if (isset($event['type']) && $event['type'] === static::COMMENT_EVENT_TYPE) {
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
     * @param array|null $responseData
     * @return array
     * @throws RestException
     */
    protected function createEntity($resource, $name, array $entityData, array &$responseData = null)
    {
        try {
            $response = $this->getClient()->post(
                $resource,
                [$name => $entityData]
            );
        } catch (\Exception $exception) {
            throw RestException::checkInvalidRecordException($exception);
        }

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
     * @param array|null $responseData
     * @return array
     * @throws RestException
     */
    protected function updateEntity($resource, $name, array $entityData, array &$responseData = null)
    {
        unset($entityData['id']);

        try {
            $response = $this->getClient()->put(
                $resource,
                [$name => $entityData]
            );
        } catch (\Exception $exception) {
            throw RestException::checkInvalidRecordException($exception);
        }

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
        return 'oro.zendesk.transport.rest.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return RestTransportSettingsFormType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return ZendeskTransportSettingsEntity::class;
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

        return [
            'auth' => ["{$email}/token", $token],
        ];
    }

    private function normalize(object $object): array
    {
        $json = $this->serializer->serialize($object, 'json');

        return $json ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    /**
     * @param array $data
     * @param string $className
     * @return array|object
     */
    private function denormalize(array $data, string $className)
    {
        return $this->serializer->deserialize($data, $className, '');
    }
}
