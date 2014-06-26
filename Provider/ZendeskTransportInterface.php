<?php

namespace OroCRM\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

interface ZendeskTransportInterface extends TransportInterface
{
    /**
     * @param \DateTime $updated
     * @return \Iterator
     */
    public function getTickets(\DateTime $updated = null);

    /**
     * @param int $id
     * @return array
     */
    public function getTicket($id);

    /**
     * @param \DateTime $updated
     * @return \Iterator
     */
    public function getUsers(\DateTime $updated = null);

    /**
     * @param int $id
     * @return array
     */
    public function getUser($id);

    /**
     * @param int $ticketId
     * @return \Iterator
     */
    public function getTicketComments($ticketId);
}
