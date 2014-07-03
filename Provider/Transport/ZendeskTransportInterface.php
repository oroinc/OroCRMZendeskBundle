<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider\Transport;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

interface ZendeskTransportInterface extends TransportInterface
{
    /**
     * Get Zendesk users data.
     *
     * @param \DateTime $lastUpdatedAt
     * @return \Iterator
     * @throws RestException
     */
    public function getUsers(\DateTime $lastUpdatedAt = null);

    /**
     * Get Zendesk tickets data.
     *
     * @param \DateTime $lastUpdatedAt
     * @return \Iterator
     * @throws RestException
     */
    public function getTickets(\DateTime $lastUpdatedAt = null);

    /**
     * Get Zendesk ticket comments data.
     *
     * @param integer $ticketId
     * @return \Iterator
     * @throws RestException
     */
    public function getTicketComments($ticketId);

    /**
     * Create Zendesk user.
     *
     * @param array $userData
     * @return array User data
     * @throws RestException
     */
    public function createUser(array $userData);

    /**
     * Create Zendesk ticket.
     *
     * @param array $ticketData
     * @return array Array in format: array("ticket" => Array, "comment" => Array|null)
     * @throws RestException
     */
    public function createTicket(array $ticketData);

    /**
     * Update Zendesk ticket.
     *
     * @param array $ticketData Must contain value of "id"
     * @return array Ticket data
     * @throws RestException
     */
    public function updateTicket(array $ticketData);

    /**
     * Add Zendesk ticket comment.
     *
     * @param array $commentData Must contain value of "ticket_id"
     * @return array Comment data
     * @throws RestException
     */
    public function addTicketComment(array $commentData);
}
