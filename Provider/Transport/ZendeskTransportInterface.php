<?php

namespace Oro\Bundle\ZendeskBundle\Provider\Transport;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\User;

interface ZendeskTransportInterface extends TransportInterface
{
    /**
     * Get Zendesk users data.
     *
     * @param \DateTime $lastSyncDate
     *
     * @return \Iterator Iterator of User
     * @throws RestException
     */
    public function getUsers(\DateTime $lastSyncDate = null);

    /**
     * Get Zendesk tickets data.
     *
     * @param \DateTime $lastSyncDate
     *
     * @return \Iterator Iterator of Ticket
     * @throws RestException
     */
    public function getTickets(\DateTime $lastSyncDate = null);

    /**
     * Get Zendesk ticket comments data.
     *
     * @param integer $ticketId
     * @return \Iterator Iterator of TicketComment
     * @throws RestException
     */
    public function getTicketComments($ticketId);

    /**
     * Create Zendesk user.
     *
     * @param User $user
     * @return User Created user
     * @throws RestException
     */
    public function createUser(User $user);

    /**
     * Create Zendesk ticket.
     *
     * @param Ticket $ticket
     * @return array Array in format: array("ticket" => Ticket, "comment" => TicketComment|null)
     * @throws RestException
     */
    public function createTicket(Ticket $ticket);

    /**
     * Get Zendesk ticket.
     *
     * @param int $id Ticket id
     * @return Ticket
     * @throws RestException
     */
    public function getTicket($id);

    /**
     * Update Zendesk ticket.
     *
     * @param Ticket $ticket Must contain value of "originId"
     * @return Ticket Updated ticket
     * @throws RestException
     */
    public function updateTicket(Ticket $ticket);

    /**
     * Add Zendesk ticket comment.
     *
     * @param TicketComment $comment Must contain value of "ticket"
     * @return TicketComment Created comment
     * @throws RestException
     */
    public function addTicketComment(TicketComment $comment);
}
