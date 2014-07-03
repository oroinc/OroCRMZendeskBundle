<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Doctrine\ORM\EntityManager;

use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\TicketSyncStrategy;
use OroCRM\Bundle\ZendeskBundle\ImportExport\SyncPropertiesHelper;
use OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;
use Symfony\Component\Serializer\SerializerInterface;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;

class TicketExportWriter implements ItemWriterInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var ZendeskTransportInterface
     */
    protected $transport;

    /**
     * @var TicketSyncHelperInterface
     */
    protected $ticketSyncHelper;

    /**
     * @param EntityManager $entityManager
     * @param SerializerInterface $serializer
     * @param ZendeskTransportInterface $transport
     * @param TicketSyncHelperInterface $ticketSyncHelper
     */
    public function __construct(
        EntityManager $entityManager,
        SerializerInterface $serializer,
        ZendeskTransportInterface $transport,
        TicketSyncHelperInterface $ticketSyncHelper
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->transport = $transport;
    }

    /**
     * @param Ticket[] $tickets
     */
    public function write(array $tickets)
    {
        foreach ($tickets as $ticket) {

        }
    }

    /**
     * @param Ticket $ticket
     */
    protected function syncTicket(Ticket $ticket)
    {
        if ($ticket->getOriginId()) {
            $this->updateTicket($ticket);
        } else {
            $this->createTicket($ticket);
        }
    }

    /**
     * @param Ticket $ticket
     * @return object
     */
    protected function updateTicket(Ticket $ticket)
    {
        $data = $this->transport->updateTicket(
            $this->serializer->serialize($ticket, null)
        );

        $updatedTicket = $this->serializer->deserialize(
            $data,
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
            null
        );
    }

    /**
     * @param Ticket $ticket
     * @return object
     */
    protected function createTicket(Ticket $ticket)
    {
        $data = $this->transport->createTicket($this->serializer->serialize($ticket, null));

        $createdTicket = $this->serializer->deserialize(
            $data['ticket'],
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
            null
        );

        $this->loadTicketFields($createdTicket);

        SyncPropertiesHelper::syncProperties(
            $ticket,
            $createdTicket,
            ['id', 'originId', 'relatedCase', 'updatedAtLocked', 'createdAt', 'updatedAt']
        );

        $createdComment = $this->serializer->deserialize(
            $data['comment'],
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment',
            null
        );
    }

    protected function loadTicketFields(Ticket $ticket)
    {

    }
}
