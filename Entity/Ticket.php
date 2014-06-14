<?php

namespace OroCRM\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;

use Oro\Bundle\UserBundle\Entity\User as OroCrmUser;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="orocrm_zendesk_ticket"
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
class Ticket
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     * @Oro\Versioned
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255)
     * @Oro\Versioned
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Oro\Versioned
     */
    protected $description;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $priority;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_email", type="string", length=100)
     * @Oro\Versioned
     */
    protected $recipient;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="requester_id", referencedColumnName="id", onDelete="SET NULL")
     * @Oro\Versioned
     */
    protected $requester;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="submitter_id", referencedColumnName="id", onDelete="SET NULL")
     * @Oro\Versioned
     */
    protected $submitter;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="assigned_to_id", referencedColumnName="id", onDelete="SET NULL")
     * @Oro\Versioned
     */
    protected $assignedTo;

    /**
     * @var bool
     *
     * @ORM\Column(name="public", type="boolean", options={"default"=false})
     * @Oro\Versioned
     */
    protected $hasIncidents;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $dueAt;

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
     * @var CaseEntity
     *
     * @ORM\OneToOne(targetEntity="OroCRM\Bundle\CaseBundle\Entity\CaseEntity")
     * @ORM\JoinColumn(name="case_id", referencedColumnName="id", onDelete="SET NULL")
     * @Oro\Versioned
     */
    protected $case;

    /**
     * @var OroCRMUser
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @Oro\Versioned
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
