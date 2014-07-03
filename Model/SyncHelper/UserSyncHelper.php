<?php

namespace OroCRM\Bundle\ZendeskBundle\Model\SyncHelper;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole as ZendeskUserRole;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;

class UserSyncHelper extends AbstractSyncHelper
{
    /**
     * {@inheritdoc}
     */
    public function findEntity($user, Channel $channel)
    {
        return $this->zendeskProvider->getUser($user, $channel);
    }

    /**
     * {@inheritdoc}
     */
    public function copyEntityProperties($targetUser, $sourceUser)
    {
        $this->syncProperties(
            $targetUser,
            $sourceUser,
            ['id', 'channel', 'relatedUser', 'relatedContact', 'updatedAtLocked', 'createdAt', 'updatedAt']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function refreshEntity($user, Channel $channel)
    {
        $this->refreshDictionaryField($user, 'role', 'userRole');
    }

    /**
     * {@inheritdoc}
     */
    public function syncRelatedEntities($entity, Channel $channel)
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
            $relatedUser = $this->oroProvider->getUser($entity);
            if ($relatedUser) {
                if ($relatedUser->getId()) {
                    $this->getLogger()->debug("Related user found [email={$relatedUser->getEmail()}].");
                } else {
                    $this->getLogger()->debug("Related user created [email={$relatedUser->getEmail()}].");
                }
                $entity->setRelatedUser($relatedUser);
            }
        } elseif ($this->isRelativeWithContact($entity)) {
            $relatedContact = $this->oroProvider->getContact($entity);
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
        return $entity->isRoleIn([ZendeskUserRole::ROLE_ADMIN, ZendeskUserRole::ROLE_AGENT]);
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
