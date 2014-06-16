<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;

class LoadTicketTypeData extends AbstractTranslatableEntityFixture
{
    const TRANSLATION_PREFIX = 'ticket_type';

    /**
     * @var array
     */
    protected $names = array(
        TicketType::TYPE_INCIDENT,
        TicketType::TYPE_PROBLEM,
        TicketType::TYPE_QUESTION,
        TicketType::TYPE_TASK
    );
    /**
     * Load entities to DB
     *
     * @param ObjectManager $manager
     */
    protected function loadEntities(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroCRMZendeskBundle:TicketType');

        $translationLocales = $this->getTranslationLocales();

        foreach ($translationLocales as $locale) {
            foreach ($this->names as $name) {
                /** @var TicketType $ticketType */
                $ticketType = $repository->findOneBy(array('name' => $name));
                if (!$ticketType) {
                    $ticketType = new TicketType($name);
                }

                // set locale and label
                $label = $this->translate($name, static::TRANSLATION_PREFIX, $locale);
                $ticketType->setLocale($locale)
                    ->setLabel($label);

                // save
                $manager->persist($ticketType);
            }

            $manager->flush();
        }
    }
}
