<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

/**
 * Represents Zendesk ticket comment
 */
#[ORM\Entity]
#[ORM\Table(name: 'orocrm_zd_comment')]
#[ORM\UniqueConstraint(name: 'zd_comment_oid_cid_unq', columns: ['origin_id', 'channel_id'])]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-list-alt']])]
class TicketComment
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'origin_id', type: Types::BIGINT, nullable: true)]
    protected $originId;

    #[ORM\Column(name: 'body', type: Types::TEXT, nullable: true)]
    protected ?string $body = null;

    #[ORM\Column(name: 'html_body', type: Types::TEXT, nullable: true)]
    protected ?string $htmlBody = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $public = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $author = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Ticket $ticket = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'origin_created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $originCreatedAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToOne(targetEntity: CaseComment::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'related_comment_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?CaseComment $relatedComment = null;

    #[ORM\ManyToOne(targetEntity: Integration::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Integration $channel = null;

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
     * @return TicketComment
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;
        return $this;
    }

    /**
     * @param string $body
     * @return TicketComment
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $htmlBody
     * @return TicketComment
     */
    public function setHtmlBody($htmlBody)
    {
        $this->htmlBody = $htmlBody;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlBody()
    {
        return $this->htmlBody;
    }

    /**
     * @param boolean $public
     * @return TicketComment
     */
    public function setPublic($public)
    {
        $this->public = (bool)$public;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * @param User|null $author
     * @return TicketComment
     */
    public function setAuthor(User $author = null)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param Ticket|null $ticket
     * @return TicketComment
     */
    public function setTicket(Ticket $ticket = null)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * @return Ticket
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * @param CaseComment|null $caseComment
     * @return TicketComment
     */
    public function setRelatedComment(CaseComment $caseComment = null)
    {
        $this->relatedComment = $caseComment;

        return $this;
    }

    /**
     * @return CaseComment
     */
    public function getRelatedComment()
    {
        return $this->relatedComment;
    }

    /**
     * @param \DateTime|null $createdAt
     * @return TicketComment
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
     * @param \DateTime|null $originCreatedAt
     * @return TicketComment
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
     * @param \DateTime|null $updatedAt
     * @return TicketComment
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
