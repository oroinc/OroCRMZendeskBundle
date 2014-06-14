<?php

namespace OroCRM\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;

use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\CaseBundle\Entity\CaseComment;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="orocrm_zendesk_comment"
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
class TicketComment
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text")
     */
    protected $body;

    /**
     * @var string
     *
     * @ORM\Column(name="html_body", type="text")
     */
    protected $htmlBody;

    /**
     * @var bool
     *
     * @ORM\Column(name="public", type="boolean", options={"default"=false})
     */
    protected $public;

    /**
     * @var ZendeskUser
     *
     * @ORM\ManyToOne(targetEntity="ZendeskUser")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $author;

    /**
     * @var Ticket
     *
     * @ORM\ManyToOne(targetEntity="Ticket")
     * @ORM\JoinColumn(name="ticket_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $ticket;

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
     * @var CaseComment
     */
    protected $caseComment;

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
