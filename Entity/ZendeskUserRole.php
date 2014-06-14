<?php

namespace OroCRM\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 * @ORM\Table(name="orocrm_zendesk_user_role")
 * @Gedmo\TranslationEntity(class="OroCRM\Bundle\ZendeskBundle\Entity\ZendeskUserRoleTranslation")
 */
class ZendeskUserRole implements Translatable
{
    const ROLE_AGENT = 'agent';
    const ROLE_ADMIN = 'admin';
    const ROLE_END_USER = 'end-user';

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
     * @param ZendeskUserRole $other
     * @return bool
     */
    public function isEqualTo(ZendeskUserRole $other)
    {
        return $this->getName() == $other->getName();
    }
}
