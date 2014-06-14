<?php

namespace OroCRM\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;

use Oro\Bundle\UserBundle\Entity\User;
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
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var TicketType
     *
     * @ORM\ManyToOne(targetEntity="TicketType", cascade={"persist"})
     * @ORM\JoinColumn(name="type_name", referencedColumnName="name", onDelete="SET NULL")
     */
    protected $type;

    /**
     * @var TicketStatus
     *
     * @ORM\ManyToOne(targetEntity="TicketStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="status_name", referencedColumnName="name", onDelete="SET NULL")
     */
    protected $status;

    /**
     * @var TicketPriority
     *
     * @ORM\ManyToOne(targetEntity="TicketPriority", cascade={"persist"})
     * @ORM\JoinColumn(name="priority_name", referencedColumnName="name", onDelete="SET NULL")
     */
    protected $priority;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_email", type="string", length=100)
     */
    protected $recipient;

    /**
     * @var ZendeskUser
     *
     * @ORM\ManyToOne(targetEntity="ZendeskUser")
     * @ORM\JoinColumn(name="requester_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $requester;

    /**
     * @var ZendeskUser
     *
     * @ORM\ManyToOne(targetEntity="ZendeskUser")
     * @ORM\JoinColumn(name="submitter_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $submitter;

    /**
     * @var ZendeskUser
     *
     * @ORM\ManyToOne(targetEntity="ZendeskUser")
     * @ORM\JoinColumn(name="assigned_to_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $assignedTo;

    /**
     * @var bool
     *
     * @ORM\Column(name="public", type="boolean", options={"default"=false})
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
     */
    protected $case;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @param ZendeskUser $assignedTo
     * @return Ticket
     */
    public function setAssignedTo(ZendeskUser $assignedTo)
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    /**
     * @return ZendeskUser
     */
    public function getAssignedTo()
    {
        return $this->assignedTo;
    }

    /**
     * @param CaseEntity $case
     * @return Ticket
     */
    public function setCase(CaseEntity $case)
    {
        $this->case = $case;

        return $this;
    }

    /**
     * @return CaseEntity
     */
    public function getCase()
    {
        return $this->case;
    }

    /**
     * @param \DateTime $createdAt
     * @return Ticket
     */
    public function setCreatedAt(\DateTime $createdAt)
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
     * @param string $description
     * @return Ticket
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param \DateTime $dueAt
     * @return Ticket
     */
    public function setDueAt(\DateTime $dueAt)
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDueAt()
    {
        return $this->dueAt;
    }

    /**
     * @param boolean $hasIncidents
     * @return Ticket
     */
    public function setHasIncidents($hasIncidents)
    {
        $this->hasIncidents = $hasIncidents;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getHasIncidents()
    {
        return $this->hasIncidents;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param User $owner
     * @return Ticket
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param TicketPriority $priority
     * @return Ticket
     */
    public function setPriority(TicketPriority $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return TicketPriority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $recipient
     * @return Ticket
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param ZendeskUser $requester
     * @return Ticket
     */
    public function setRequester(ZendeskUser $requester)
    {
        $this->requester = $requester;

        return $this;
    }

    /**
     * @return ZendeskUser
     */
    public function getRequester()
    {
        return $this->requester;
    }

    /**
     * @param TicketStatus $status
     * @return Ticket
     */
    public function setStatus(TicketStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return TicketStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $subject
     * @return Ticket
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param ZendeskUser $submitter
     * @return Ticket
     */
    public function setSubmitter(ZendeskUser $submitter)
    {
        $this->submitter = $submitter;

        return $this;
    }

    /**
     * @return ZendeskUser
     */
    public function getSubmitter()
    {
        return $this->submitter;
    }

    /**
     * @param TicketType $type
     * @return Ticket
     */
    public function setType(TicketType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return TicketType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \DateTime $updatedAt
     * @return Ticket
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

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
     * @param string $url
     * @return Ticket
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
