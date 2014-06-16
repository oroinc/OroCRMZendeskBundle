<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;

class LoadPriorityData extends AbstractTranslatableEntityFixture
{
    const TRANSLATION_PREFIX = 'ticket_priority';

    /**
     * @var array
     */
    protected $names = array(
        TicketPriority::PRIORITY_LOW,
        TicketPriority::PRIORITY_NORMAL,
        TicketPriority::PRIORITY_HIGH,
        TicketPriority::PRIORITY_URGENT,
    );

    /**
     * Load entities to DB
     *
     * @param ObjectManager $manager
     */
    protected function loadEntities(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroCRMZendeskBundle:TicketPriority');

        $translationLocales = $this->getTranslationLocales();

        foreach ($translationLocales as $locale) {
            foreach ($this->names as $name) {
                /** @var TicketPriority $ticketPriority */
                $ticketPriority = $repository->findOneBy(array('name' => $name));
                if (!$ticketPriority) {
                    $ticketPriority = new TicketPriority($name);
                }

                // set locale and label
                $label = $this->translate($name, static::TRANSLATION_PREFIX, $locale);
                $ticketPriority->setLocale($locale)
                    ->setLabel($label);

                // save
                $manager->persist($ticketPriority);
            }

            $manager->flush();
        }
    }
}
