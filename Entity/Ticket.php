<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

/**
 * Represents a Zendesk ticket.
 *
 * @ORM\Entity
 * @ORM\Table(
 *      name="orocrm_zd_ticket",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="zd_ticket_oid_cid_unq", columns={"origin_id", "channel_id"})
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
class Ticket
{
    const SEARCH_TYPE = 'ticket';

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
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="external_id", type="string", length=50, nullable=true)
     */
    protected $externalId;

    /**
     * @var Ticket
     *
     * @ORM\ManyToOne(targetEntity="Ticket")
     * @ORM\JoinColumn(name="problem_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $problem;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="orocrm_zd_ticket_collaborators",
     *      joinColumns={@ORM\JoinColumn(name="ticket_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $collaborators;

    /**
     * @var TicketType
     *
     * @ORM\ManyToOne(targetEntity="TicketType")
     * @ORM\JoinColumn(name="type_name", referencedColumnName="name", onDelete="SET NULL")
     */
    protected $type;

    /**
     * @var TicketStatus
     *
     * @ORM\ManyToOne(targetEntity="TicketStatus")
     * @ORM\JoinColumn(name="status_name", referencedColumnName="name", onDelete="SET NULL")
     */
    protected $status;

    /**
     * @var TicketPriority
     *
     * @ORM\ManyToOne(targetEntity="TicketPriority")
     * @ORM\JoinColumn(name="priority_name", referencedColumnName="name", onDelete="SET NULL")
     */
    protected $priority;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_email", type="string", length=100, nullable=true)
     */
    protected $recipient;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="requester_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $requester;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="submitter_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $submitter;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="assignee_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $assignee;

    /**
     * @var bool
     *
     * @ORM\Column(name="has_incidents", type="boolean", options={"default"=false})
     */
    protected $hasIncidents = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due_at", type="datetime", nullable=true)
     */
    protected $dueAt;

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
     * @ORM\Column(name="origin_created_at", type="datetime", nullable=true)
     */
    protected $originCreatedAt;

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
     * @ORM\Column(name="origin_updated_at", type="datetime", nullable=true)
     */
    protected $originUpdatedAt;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="TicketComment",
     *     mappedBy="ticket",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    protected $comments;

    /**
     * @var CaseEntity
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\CaseBundle\Entity\CaseEntity", cascade={"persist"})
     * @ORM\JoinColumn(name="case_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $relatedCase;

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
     * Constructor
     */
    public function __construct()
    {
        $this->collaborators = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

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
     * @return Ticket
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;

        return $this;
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
     * @param string $externalId
     * @return Ticket
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
     * @param Ticket $problem
     * @return Ticket
     */
    public function setProblem(Ticket $problem = null)
    {
        $this->problem = $problem;

        return $this;
    }

    /**
     * @return Ticket
     */
    public function getProblem()
    {
        return $this->problem;
    }

    /**
     * @param Collection $collaborators
     * @return Ticket
     */
    public function setCollaborators(Collection $collaborators)
    {
        $this->collaborators = $collaborators;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getCollaborators()
    {
        return $this->collaborators;
    }

    /**
     * @param User $user
     * @return Ticket
     */
    public function addCollaborator(User $user)
    {
        if (!$this->getCollaborators()->contains($user)) {
            $this->getCollaborators()->add($user);
        }
        return $this;
    }

    /**
     * @param User $user
     * @return Ticket
     */
    public function removeCollaborator(User $user)
    {
        if (!$this->getCollaborators()->contains($user)) {
            $this->getCollaborators()->remove($user);
        }
        return $this;
    }

    /**
     * @param TicketType $type
     * @return Ticket
     */
    public function setType(TicketType $type = null)
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
     * @param TicketStatus $status
     * @return Ticket
     */
    public function setStatus(TicketStatus $status = null)
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
     * @param TicketPriority $priority
     * @return Ticket
     */
    public function setPriority(TicketPriority $priority = null)
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
     * @param User $requester
     * @return Ticket
     */
    public function setRequester(User $requester = null)
    {
        $this->requester = $requester;

        return $this;
    }

    /**
     * @return User
     */
    public function getRequester()
    {
        return $this->requester;
    }

    /**
     * @param User $submitter
     * @return Ticket
     */
    public function setSubmitter(User $submitter = null)
    {
        $this->submitter = $submitter;

        return $this;
    }

    /**
     * @return User
     */
    public function getSubmitter()
    {
        return $this->submitter;
    }

    /**
     * @param User $assignee
     * @return Ticket
     */
    public function setAssignee(User $assignee = null)
    {
        $this->assignee = $assignee;

        return $this;
    }

    /**
     * @return User
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * @param boolean $hasIncidents
     * @return Ticket
     */
    public function setHasIncidents($hasIncidents)
    {
        $this->hasIncidents = (bool)$hasIncidents;

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
     * @param \DateTime $dueAt
     * @return Ticket
     */
    public function setDueAt(\DateTime $dueAt = null)
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
     * @param \DateTime $createdAt
     * @return Ticket
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
     * @param \DateTime $originCreatedAt
     * @return Ticket
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
     * @param \DateTime $updatedAt
     * @return Ticket
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
     * @param \DateTime $originUpdatedAt
     * @return Ticket
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
     * @return Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param Collection $comments
     * @return Ticket
     */
    public function setComments(Collection $comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * @param TicketComment $comment
     * @return Ticket
     */
    public function addComment(TicketComment $comment)
    {
        $this->getComments()->add($comment);
        $comment->setTicket($this);

        return $this;
    }

    /**
     * @param TicketComment $comment
     * @return Ticket
     */
    public function removeComment(TicketComment $comment)
    {
        if (!$this->getComments()->contains($comment)) {
            $this->getComments()->remove($comment);
            $comment->setTicket(null);
        }
        return $this;
    }

    /**
     * @param CaseEntity $case
     * @return Ticket
     */
    public function setRelatedCase(CaseEntity $case = null)
    {
        $this->relatedCase = $case;

        return $this;
    }

    /**
     * @return CaseEntity
     */
    public function getRelatedCase()
    {
        return $this->relatedCase;
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
