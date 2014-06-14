<?php

namespace OroCRM\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;

use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="orocrm_zendesk_user"
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Oro\Loggable
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-list-alt"
 *      },
 *      "ownership"={
 *          "owner_type"="USER",
 *          "owner_field_name"="owner",
 *          "owner_column_name"="owner_id"
 *      },
 *      "security"={
 *          "type"="ACL"
 *      }
 *  }
 * )
 */
class ZendeskUser
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;

    /**
     * @var ZendeskUserRole
     *
     * @ORM\ManyToOne(targetEntity="ZendeskUserRole", cascade={"persist"})
     * @ORM\JoinColumn(name="role_name", referencedColumnName="name", onDelete="SET NULL")
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100)
     */
    protected $email;

    /**
     * @var string
     */
    protected $phone;

    /**
     * @var string
     */
    protected $timeZone;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var Contact
     */
    protected $contact;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $user;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = $this->createdAt ? $this->createdAt : new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = $this->updatedAt ? $this->updatedAt : new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
