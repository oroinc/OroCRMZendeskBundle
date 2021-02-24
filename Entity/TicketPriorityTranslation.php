<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\AbstractTranslation;

/**
 * Represents Gedmo translation dictionary for TicketPriority entity.
 *
 * @ORM\Table(name="orocrm_zd_ticket_priority_tran", indexes={
 *      @ORM\Index(
 *          name="orocrm_zd_ticket_priority_tran_idx", columns={"locale", "object_class", "field", "foreign_key"}
 *      )
 * })
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class TicketPriorityTranslation extends AbstractTranslation
{
    /**
     * @var string $foreignKey
     *
     * @ORM\Column(name="foreign_key", type="string", length=16)
     */
    protected $foreignKey;
}
