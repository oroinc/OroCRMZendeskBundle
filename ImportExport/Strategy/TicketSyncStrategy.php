<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;

class TicketSyncStrategy extends AbstractSyncStrategy
{
    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof Ticket) {
            throw new InvalidArgumentException('Imported entity must be instance of Zendesk Ticket');
        }

        if (!$this->validateOriginId($entity)) {
            return null;
        }

        $this->refreshDictionaryField($entity, 'status', 'ticketStatus');
        $this->refreshDictionaryField($entity, 'priority', 'ticketPriority');
        $this->refreshDictionaryField($entity, 'type', 'ticketType');

        $existingTicket = $this->zendeskProvider->getTicket($entity);
        if ($existingTicket) {
            $this->syncProperties($existingTicket, $entity, array('relatedCase', 'id'));
            $entity = $existingTicket;

            $this->getLogger()->debug($this->buildMessage("Update found record.", $entity));
            $this->getContext()->incrementUpdateCount();
        } else {
            $this->getLogger()->debug($this->buildMessage("Add new record.", $entity));
            $this->getContext()->incrementAddCount();
        }

        $this->syncRelatedEntities($entity);

        return $entity;
    }

    /**
     * @param ZendeskUser $entity
     */
    protected function syncRelatedEntities(ZendeskUser $entity)
    {
        if ($this->isRelativeWithUser($entity) && $entity->getRelatedContact()) {
            $relatedId = $entity->getRelatedContact()->getId();
            $this->getLogger()->info(
                $this->buildMessage(
                    "Unset related contact [id=$relatedId] due to incompatible role change.",
                    $entity
                )
            );
            $entity->setRelatedContact(null);
        }

        if ($this->isRelativeWithContact($entity) && $entity->getRelatedUser()) {
            $relatedId = $entity->getRelatedUser()->getId();
            $this->getLogger()->info(
                $this->buildMessage(
                    "Unset related user [id=$relatedId] due to incompatible role change.",
                    $entity
                )
            );
            $entity->setRelatedUser(null);
        }

        if ($entity->getRelatedUser() || $entity->getRelatedContact() || !$entity->getEmail()) {
            return;
        }

        if ($entity->isRoleIn(array(ZendeskUserRole::ROLE_ADMIN, ZendeskUserRole::ROLE_AGENT))) {
            $relatedUser = $this->oroUserProvider->getUser($entity);
            if ($relatedUser) {
                $this->getLogger()->debug(
                    $this->buildMessage(
                        "Related user found [id={$relatedUser->getId()}]",
                        $entity
                    )
                );
                $entity->setRelatedUser($relatedUser);
            }
        } elseif ($entity->isRoleEqual(ZendeskUserRole::ROLE_END_USER)) {
            $relatedContact = $this->contactProvider->getContact($entity);
            if ($relatedContact) {
                $this->getLogger()->debug(
                    $this->buildMessage(
                        "Related contact found [id={$relatedContact->getId()}]",
                        $entity
                    )
                );
                $entity->setRelatedContact($relatedContact);
            }
        }
    }

    /**
     * @param ZendeskUser $entity
     * @return bool
     */
    protected function isRelativeWithUser(ZendeskUser $entity)
    {
        return $entity->isRoleIn(array(ZendeskUserRole::ROLE_ADMIN, ZendeskUserRole::ROLE_AGENT));
    }

    /**
     * @param ZendeskUser $entity
     * @return bool
     */
    protected function isRelativeWithContact(ZendeskUser $entity)
    {
        return $entity->isRoleEqual(ZendeskUserRole::ROLE_END_USER);
    }
}
