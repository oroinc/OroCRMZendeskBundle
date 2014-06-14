<?php

namespace OroCRM\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 * @ORM\Table(name="orocrm_ticket_priority")
 * @Gedmo\TranslationEntity(class="OroCRM\Bundle\ZendeskBundle\Entity\TicketPriorityTranslation")
 */
class TicketPriority implements Translatable
{
    const PRIORITY_LOW     = 'low';
    const PRIORITY_NORMAL  = 'normal';
    const PRIORITY_HIGH    = 'high';
    const PRIORITY_URGENT  = 'urgent';

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="name", type="string", length=16)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     * @Gedmo\Translatable
     */
    protected $label;

    /**
     * @Gedmo\Locale
     */
    protected $locale;

    /**
     * @param string $name
     */
    public function __construct($name)
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
     * @param string $label
     * @return TicketPriority
     */
    public function setLabel($label)
    {
        $this->label = $label;

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
    public function __toString()
    {
        return (string)$this->label;
    }

    /**
     * @param mixed $other
     * @return bool
     */
    public function isEqualTo($other)
    {
        if (!$other instanceof TicketPriority) {
            return false;
        }

        return $this->getName() == $other->getName();
    }
}
