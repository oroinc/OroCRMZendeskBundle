<?php

namespace Oro\Bundle\ZendeskBundle\Model\SyncHelper;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole as ZendeskUserRole;

/**
 * Contains user sync logic that is used in both import and export.
 */
class UserSyncHelper extends AbstractSyncHelper
{
    public function rememberUser(User $user, Channel $channel)
    {
        $this->zendeskProvider->rememberUser($user, $channel);
    }

    /**
     * Find User
     *
     * @param User $user
     * @param Channel $channel
     * @return null|User
     */
    public function findUser(User $user, Channel $channel)
    {
        return $this->zendeskProvider->getUser($user, $channel);
    }

    /**
     * Finds default user
     *
     * @param Channel $channel
     * @return null|User
     */
    public function findDefaultUser(Channel $channel)
    {
        return $this->zendeskProvider->getDefaultZendeskUser($channel);
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

    public function refreshTicket(User $user, Channel $channel)
    {
        $this->refreshChannel($user, $channel);
        $this->refreshDictionaryField($user, 'role', 'userRole');
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function syncRelatedEntities(User $entity)
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
            $relatedUser = $this->oroProvider->getUser($entity, true);
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
     * @param User $entity
     * @return bool
     */
    protected function isRelativeWithUser(User  $entity)
    {
        return $entity->isRoleIn([ZendeskUserRole::ROLE_ADMIN, ZendeskUserRole::ROLE_AGENT]);
    }

    /**
     * @param User $entity
     * @return bool
     */
    protected function isRelativeWithContact(User $entity)
    {
        return $entity->isRoleEqual(ZendeskUserRole::ROLE_END_USER);
    }
}
