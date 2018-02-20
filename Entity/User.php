<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\UserBundle\Entity\User as OroUser;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="orocrm_zd_user",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="zd_user_oid_cid_unq", columns={"origin_id", "channel_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="fa-list-alt"
 *      }
 *  }
 * )
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class User implements EmailHolderInterface
{
    const SEARCH_TYPE = 'user';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="origin_id", type="bigint", nullable=true)
     */
    protected $originId;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="external_id", type="string", length=50, nullable=true)
     */
    protected $externalId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="details", type="text", nullable=true)
     */
    protected $details;

    /**
     * @var string
     *
     * @ORM\Column(name="ticket_restrictions", type="string", length=30, nullable=true)
     */
    protected $ticketRestriction;

    /**
     * @var bool
     *
     * @ORM\Column(name="only_private_comments", type="boolean", options={"default"=false})
     */
    protected $onlyPrivateComments = false;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    protected $notes;

    /**
     * @var bool
     *
     * @ORM\Column(name="verified", type="boolean", options={"default"=false})
     */
    protected $verified = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", options={"default"=false})
     */
    protected $active = false;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=100, nullable=true)
     */
    protected $alias;

    /**
     * @var UserRole
     *
     * @ORM\ManyToOne(targetEntity="UserRole", cascade={"persist"})
     * @ORM\JoinColumn(name="role_name", referencedColumnName="name", onDelete="SET NULL")
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=50, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="time_zone", type="string", length=30, nullable=true)
     */
    protected $timeZone;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=30, nullable=true)
     */
    protected $locale;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login_at", type="datetime", nullable=true)
     */
    protected $lastLoginAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="origin_created_at", type="datetime", nullable=true)
     */
    protected $originCreatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="origin_updated_at", type="datetime", nullable=true)
     */
    protected $originUpdatedAt;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ContactBundle\Entity\Contact", cascade={"persist"})
     * @ORM\JoinColumn(name="related_contact_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $relatedContact;

    /**
     * @var OroUser
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="related_user_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $relatedUser;

    /**
     * @var Integration
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Channel")
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $channel;

    /**
     * @var bool
     */
    private $updatedAtLocked = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOriginId()
    {
        return $this->originId;
    }

    /**
     * @param int $originId
     * @return User
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;
        return $this;
    }

    /**
     * @param string $url
     * @return User
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $externalId
     * @return User
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param string $name
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $details
     * @return User
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param string $ticketRestriction
     * @return User
     */
    public function setTicketRestriction($ticketRestriction)
    {
        $this->ticketRestriction = $ticketRestriction;

        return $this;
    }

    /**
     * @return string
     */
    public function getTicketRestriction()
    {
        return $this->ticketRestriction;
    }

    /**
     * @param boolean $onlyPrivateComments
     * @return User
     */
    public function setOnlyPrivateComments($onlyPrivateComments)
    {
        $this->onlyPrivateComments = (bool)$onlyPrivateComments;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getOnlyPrivateComments()
    {
        return $this->onlyPrivateComments;
    }

    /**
     * @param string $notes
     * @return User
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param boolean $verified
     * @return User
     */
    public function setVerified($verified)
    {
        $this->verified = (bool)$verified;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getVerified()
    {
        return $this->verified;
    }

    /**
     * @param boolean $active
     * @return User
     */
    public function setActive($active)
    {
        $this->active = (bool)$active;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param string $alias
     * @return User
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param UserRole $role
     * @return User
     */
    public function setRole(UserRole $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return UserRole
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param UserRole[]|string[] $roles
     * @return bool
     */
    public function isRoleIn(array $roles)
    {
        if (!$this->getRole()) {
            return false;
        }
        foreach ($roles as $role) {
            if ($this->isRoleEqual($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param UserRole|string $role
     * @return bool
     */
    public function isRoleEqual($role)
    {
        if (!$this->getRole()) {
            return false;
        }
        if ($role instanceof UserRole) {
            $roleName = $role->getName();
        } else {
            $roleName = $role;
        }
        return $this->getRole() && $roleName == $this->getRole()->getName();
    }

    /**
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $phone
     * @return User
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $timeZone
     * @return User
     */
    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @param string $locale
     * @return User
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param \DateTime $lastLoginAt
     * @return User
     */
    public function setLastLoginAt(\DateTime $lastLoginAt = null)
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastLoginAt()
    {
        return $this->lastLoginAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return User
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return User
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        $this->updatedAtLocked = true;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $originCreatedAt
     * @return User
     */
    public function setOriginCreatedAt(\DateTime $originCreatedAt = null)
    {
        $this->originCreatedAt = $originCreatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getOriginCreatedAt()
    {
        return $this->originCreatedAt;
    }

    /**
     * @param \DateTime $originUpdatedAt
     * @return User
     */
    public function setOriginUpdatedAt(\DateTime $originUpdatedAt = null)
    {
        $this->originUpdatedAt = $originUpdatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getOriginUpdatedAt()
    {
        return $this->originUpdatedAt;
    }

    /**
     * @param Contact $contact
     * @return User
     */
    public function setRelatedContact(Contact $contact = null)
    {
        $this->relatedContact = $contact;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getRelatedContact()
    {
        return $this->relatedContact;
    }

    /**
     * @param OroUser $user
     * @return User
     */
    public function setRelatedUser(OroUser $user = null)
    {
        $this->relatedUser = $user;

        return $this;
    }

    /**
     * @return OroUser
     */
    public function getRelatedUser()
    {
        return $this->relatedUser;
    }

    /**
     * @param Integration $integration
     * @return $this
     */
    public function setChannel(Integration $integration)
    {
        $this->channel = $integration;

        return $this;
    }

    /**
     * @return Integration
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return string
     */
    public function getChannelName()
    {
        return $this->channel ? $this->channel->getName() : null;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt  = $this->createdAt ? $this->createdAt : new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = $this->updatedAt? $this->updatedAt : new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        if (!$this->updatedAtLocked) {
            $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
    }
}
