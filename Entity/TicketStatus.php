<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
* Entity that represents Ticket Status
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'orocrm_zd_ticket_status')]
#[Gedmo\TranslationEntity(class: TicketStatusTranslation::class)]
#[Config(defaultValues: ['grouping' => ['groups' => ['dictionary']], 'dictionary' => ['virtual_fields' => ['label']]])]
class TicketStatus implements Translatable
{
    public const STATUS_NEW = 'new';
    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_HOLD = 'hold';
    public const STATUS_SOLVED = 'solved';
    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\Column(name: 'name', type: Types::STRING, length: 16)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255)]
    #[Gedmo\Translatable]
    protected ?string $label = null;

    #[Gedmo\Locale]
    protected ?string $locale = null;

    /**
     * @param string $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return TicketStatus
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return TicketStatus
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return TicketStatus
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Returns locale code
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->label;
    }

    /**
     * @param TicketStatus $other
     * @return bool
     */
    public function isEqualTo(TicketStatus $other)
    {
        return $this->getName() == $other->getName();
    }
}
