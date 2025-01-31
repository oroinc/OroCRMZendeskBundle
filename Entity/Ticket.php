<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

/**
 * Represents a Zendesk ticket.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\Entity]
#[ORM\Table(name: 'orocrm_zd_ticket')]
#[ORM\UniqueConstraint(name: 'zd_ticket_oid_cid_unq', columns: ['origin_id', 'channel_id'])]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-list-alt']])]
class Ticket
{
    const SEARCH_TYPE = 'ticket';

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'origin_id', type: Types::BIGINT, nullable: true)]
    protected $originId;

    #[ORM\Column(name: 'url', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $url = null;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'external_id', type: Types::STRING, length: 50, nullable: true)]
    protected ?string $externalId = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(name: 'problem_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Ticket $problem = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'orocrm_zd_ticket_collaborators')]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $collaborators = null;

    #[ORM\ManyToOne(targetEntity: TicketType::class)]
    #[ORM\JoinColumn(name: 'type_name', referencedColumnName: 'name', onDelete: 'SET NULL')]
    protected ?TicketType $type = null;

    #[ORM\ManyToOne(targetEntity: TicketStatus::class)]
    #[ORM\JoinColumn(name: 'status_name', referencedColumnName: 'name', onDelete: 'SET NULL')]
    protected ?TicketStatus $status = null;

    #[ORM\ManyToOne(targetEntity: TicketPriority::class)]
    #[ORM\JoinColumn(name: 'priority_name', referencedColumnName: 'name', onDelete: 'SET NULL')]
    protected ?TicketPriority $priority = null;

    #[ORM\Column(name: 'recipient_email', type: Types::STRING, length: 100, nullable: true)]
    protected ?string $recipient = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'requester_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $requester = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'submitter_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $submitter = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assignee_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $assignee = null;

    #[ORM\Column(name: 'has_incidents', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $hasIncidents = false;

    #[ORM\Column(name: 'due_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $dueAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'origin_created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $originCreatedAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'origin_updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $originUpdatedAt = null;

    /**
     * @var Collection<int, TicketComment>
     */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketComment::class, cascade: ['persist'], orphanRemoval: true)]
    protected ?Collection $comments = null;

    #[ORM\OneToOne(targetEntity: CaseEntity::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?CaseEntity $relatedCase = null;

    #[ORM\ManyToOne(targetEntity: Integration::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Integration $channel = null;

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
     * @param Ticket|null $problem
     * @return Ticket
     */
    public function setProblem(?Ticket $problem = null)
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
     * @param TicketType|null $type
     * @return Ticket
     */
    public function setType(?TicketType $type = null)
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
     * @param TicketStatus|null $status
     * @return Ticket
     */
    public function setStatus(?TicketStatus $status = null)
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
     * @param TicketPriority|null $priority
     * @return Ticket
     */
    public function setPriority(?TicketPriority $priority = null)
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
     * @param User|null $requester
     * @return Ticket
     */
    public function setRequester(?User $requester = null)
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
     * @param User|null $submitter
     * @return Ticket
     */
    public function setSubmitter(?User $submitter = null)
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
     * @param User|null $assignee
     * @return Ticket
     */
    public function setAssignee(?User $assignee = null)
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
     * @param \DateTime|null $dueAt
     * @return Ticket
     */
    public function setDueAt(?\DateTime $dueAt = null)
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
     * @param \DateTime|null $createdAt
     * @return Ticket
     */
    public function setCreatedAt(?\DateTime $createdAt = null)
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
     * @param \DateTime|null $originCreatedAt
     * @return Ticket
     */
    public function setOriginCreatedAt(?\DateTime $originCreatedAt = null)
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
     * @param \DateTime|null $updatedAt
     * @return Ticket
     */
    public function setUpdatedAt(?\DateTime $updatedAt = null)
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
     * @param \DateTime|null $originUpdatedAt
     * @return Ticket
     */
    public function setOriginUpdatedAt(?\DateTime $originUpdatedAt = null)
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
     * @param CaseEntity|null $case
     * @return Ticket
     */
    public function setRelatedCase(?CaseEntity $case = null)
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

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt  = $this->createdAt ? $this->createdAt : new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = $this->updatedAt ? $this->updatedAt : new \DateTime('now', new \DateTimeZone('UTC'));
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        if (!$this->updatedAtLocked) {
            $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
    }
}
