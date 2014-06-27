<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider\Transport;

use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

interface ZendeskTransportInterface extends TransportInterface
{
    /**
     * Get Zendesk users data.
     *
     * @param \DateTime $lastUpdatedAt
     * @return \Iterator
     */
    public function getUsers(\DateTime $lastUpdatedAt = null);

    /**
     * Get Zendesk tickets data.
     *
     * @param \DateTime $lastUpdatedAt
     * @return \Iterator
     */
    public function getTickets(\DateTime $lastUpdatedAt = null);

    /**
     * Get Zendesk ticket comments data.
     *
     * @param integer $ticketId
     * @return \Iterator
     */
    public function getTicketComments($ticketId);
}
