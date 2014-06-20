<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\ZendeskBundle\Entity\UserRole as ZendeskUserRole;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\OroEntityProvider;

class UserSyncStrategy extends AbstractSyncStrategy
{
    /**
     * @var OroEntityProvider
     */
    protected $oroEntityProvider;

    /**
     * @param OroEntityProvider $oroEntityProvider
     */
    public function __construct(OroEntityProvider $oroEntityProvider)
    {
        $this->oroEntityProvider = $oroEntityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof ZendeskUser) {
            throw new InvalidArgumentException(
                sprintf(
                    'Imported entity must be instance of OroCRM\\Bundle\\ZendeskBundle\\Entity\\User, %s given.',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        if (!$this->validateOriginId($entity)) {
            return null;
        }

        $this->getLogger()->setMessagePrefix("Zendesk User [id={$entity->getOriginId()}]: ");

        $this->refreshDictionaryField($entity, 'role', 'userRole');

        $existingUser = $this->findExistingEntity($entity, 'originId');
        if ($existingUser) {
            $this->syncProperties($existingUser, $entity, array('relatedUser', 'relatedContact', 'id'));
            $entity = $existingUser;

            $this->getLogger()->debug("Update found Zendesk user.");
            $this->getContext()->incrementUpdateCount();
        } else {
            $this->getLogger()->debug("Add new Zendesk user.");
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
            $this->getLogger()->info(
                "Unset related contact [id={$entity->getRelatedContact()->getId()}] due to incompatible role change."
            );
            $entity->setRelatedContact(null);
        }

        if ($this->isRelativeWithContact($entity) && $entity->getRelatedUser()) {
            $this->getLogger()->info(
                "Unset related user [id={$entity->getRelatedUser()->getId()}] due to incompatible role change."
            );
            $entity->setRelatedUser(null);
        }

        if ($entity->getRelatedUser() || $entity->getRelatedContact()) {
            return;
        }

        if ($this->isRelativeWithUser($entity)) {
            $relatedUser = $this->oroEntityProvider->getUser($entity);
            if ($relatedUser) {
                if ($relatedUser->getId()) {
                    $this->getLogger()->debug("Related user found [email={$relatedUser->getEmail()}].");
                } else {
                    $this->getLogger()->debug("Related user created [email={$relatedUser->getEmail()}].");
                }
                $entity->setRelatedUser($relatedUser);
            }
        } elseif ($this->isRelativeWithContact($entity)) {
            $relatedContact = $this->oroEntityProvider->getContact($entity);
            if ($relatedContact) {
                if ($relatedContact->getId()) {
                    $this->getLogger()->debug("Related contact found [email={$relatedContact->getEmail()}].");
                } else {
                    $this->getLogger()->debug("Related contact created [email={$relatedContact->getEmail()}].");
                }
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
