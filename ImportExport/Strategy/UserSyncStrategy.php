<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\ZendeskBundle\Entity\UserRole as ZendeskUserRole;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;

use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\ContactProvider;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\OroUserProvider;

class UserSyncStrategy extends AbstractSyncStrategy
{
    /**
     * @var ContactProvider
     */
    protected $contactProvider;

    /**
     * @var OroUserProvider
     */
    protected $oroUserProvider;

    /**
     * @param EntityManager $entityManager
     * @param ContactProvider $contactProvider
     * @param OroUserProvider $oroUserProvider
     */
    public function __construct(
        EntityManager $entityManager,
        ContactProvider $contactProvider,
        OroUserProvider $oroUserProvider
    ) {
        parent::__construct($entityManager);
        $this->contactProvider = $contactProvider;
        $this->oroUserProvider = $oroUserProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof ZendeskUser) {
            throw new InvalidArgumentException('Imported entity must be instance of Zendesk User');
        }

        if (!$entity->getId()) {
            $message = $this->buildMessage(
                'Can\'t process record [id=null].',
                $this->getContext()->getReadCount(),
                'read_index'
            );

            $this->getContext()->addError($message);
            $this->getLogger()->error($message);

            $this->getContext()->incrementErrorEntriesCount();

            return null;
        }

        $role = null;
        if ($entity->getRole()) {
            $role = $this->findExistingEntity($entity->getRole(), 'name');
            if (!$role) {
                $roleName = $entity->getRole()->getName();
                $this->getLogger()->warning($this->buildMessage("Can't find user role [name=$roleName].", $entity));
            }
        } else {
            $this->getLogger()->warning($this->buildMessage("Role is empty.", $entity));
        }
        $entity->setRole($role);

        $existingUser = $this->findExistingEntity($entity);
        if ($existingUser) {
            $this->syncProperties($existingUser, $entity, array('relatedUser', 'relatedContact'));
            $entity = $existingUser;

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
