<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Serializer\SerializerInterface;

use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\TicketSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\TicketCommentSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;
use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;

class TicketExportWriter extends AbstractExportWriter
{
    /**
     * @var TicketSyncHelper
     */
    protected $ticketHelper;

    /**
     * @var TicketCommentSyncHelper
     */
    protected $ticketCommentHelper;

    /**
     * @param EntityManager $entityManager
     * @param SerializerInterface $serializer
     * @param ZendeskTransportInterface $transport
     * @param TicketSyncHelper $ticketHelper
     * @param TicketCommentSyncHelper $ticketCommentHelper
     */
    public function __construct(
        EntityManager $entityManager,
        SerializerInterface $serializer,
        ZendeskTransportInterface $transport,
        TicketSyncHelper $ticketHelper,
        TicketCommentSyncHelper $ticketCommentHelper
    ) {
        parent::__construct($entityManager, $serializer, $transport);

        $this->ticketHelper = $ticketHelper;
        $this->ticketCommentHelper = $ticketCommentHelper;
    }

    /**
     * @param Ticket $ticket
     */
    protected function writeItem($ticket)
    {
        $this->getLogger()->setMessagePrefix("Zendesk Ticket Comment [id={$ticket->getId()}]: ");

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
        $this->getLogger()->info("Update in Zendesk API [{id=$ticket->getOriginId()}].");

        $data = $this->transport->updateTicket(
            $this->serializer->serialize($ticket, null)
        );

        $updatedTicket = $this->serializer->deserialize(
            $data,
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
            null
        );

        $this->getLogger()->info('Update entity by response data.');
        $this->ticketHelper->refreshEntity($updatedTicket, $ticket->getChannel());
        $this->ticketHelper->copyEntityProperties($ticket, $updatedTicket);

        $this->getLogger()->info('Update related entities.');
        $this->ticketHelper->syncRelatedEntities($ticket, $ticket->getChannel());

        $this->getContext()->incrementUpdateCount();
    }

    /**
     * @param Ticket $ticket
     * @return object
     */
    protected function createTicket(Ticket $ticket)
    {
        $this->getLogger()->info("Create in Zendesk API.");

        $data = $this->transport->createTicket($this->serializer->serialize($ticket, null));

        $createdTicket = $this->serializer->deserialize(
            $data['ticket'],
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
            null
        );

        $this->getLogger()->info("Created ticket [origin_id={$createdTicket->getOriginId()}].");

        $this->getLogger()->info('Update entity by response data.');
        $this->ticketHelper->refreshEntity($createdTicket, $ticket->getChannel());
        $this->ticketHelper->copyEntityProperties($ticket, $createdTicket);

        $this->getLogger()->info('Update related entities.');
        $this->ticketHelper->syncRelatedEntities($ticket, $ticket->getChannel());

        $createdComment = $this->serializer->deserialize(
            $data['comment'],
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment',
            null
        );

        $this->getLogger()->info("Created ticket comment [origin_id={$createdComment->getOriginId()}].");

        $this->entityManager->persist($createdComment);
        $createdComment->setChannel($ticket->getChannel());

        $this->ticketCommentHelper->refreshEntity($createdComment, $ticket->getChannel());
        $this->ticketCommentHelper->syncRelatedEntities($createdComment, $ticket->getChannel());
    }
}
