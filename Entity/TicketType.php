<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
* Entity that represents Ticket Type
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'orocrm_zd_ticket_type')]
#[Gedmo\TranslationEntity(class: TicketTypeTranslation::class)]
#[Config(defaultValues: ['grouping' => ['groups' => ['dictionary']], 'dictionary' => ['virtual_fields' => ['label']]])]
class TicketType implements Translatable
{
    const TYPE_TASK = 'task';
    const TYPE_PROBLEM = 'problem';
    const TYPE_INCIDENT = 'incident';
    const TYPE_QUESTION = 'question';

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
     * @return TicketType
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
     * @return TicketType
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
     * @return TicketPriority
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
     * @param TicketType $other
     * @return bool
     */
    public function isEqualTo(TicketType $other)
    {
        return $this->getName() == $other->getName();
    }
}
