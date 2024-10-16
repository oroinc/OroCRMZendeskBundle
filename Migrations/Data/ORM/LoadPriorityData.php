<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;

/**
 * Loads Zendesk ticket priorities.
 */
class LoadPriorityData extends AbstractTranslatableEntityFixture
{
    private const TRANSLATION_PREFIX = 'ticket_priority';

    #[\Override]
    protected function loadEntities(ObjectManager $manager): void
    {
        $ticketPriorityRepository = $manager->getRepository(TicketPriority::class);
        $translationLocales = $this->getTranslationLocales();
        $names = [
            TicketPriority::PRIORITY_LOW,
            TicketPriority::PRIORITY_NORMAL,
            TicketPriority::PRIORITY_HIGH,
            TicketPriority::PRIORITY_URGENT
        ];
        foreach ($translationLocales as $locale) {
            foreach ($names as $name) {
                /** @var TicketPriority $ticketPriority */
                $ticketPriority = $ticketPriorityRepository->findOneBy(['name' => $name]);
                if (!$ticketPriority) {
                    $ticketPriority = new TicketPriority($name);
                }

                $ticketPriority->setLocale($locale);
                $ticketPriority->setLabel($this->translate($name, self::TRANSLATION_PREFIX, $locale));
                $manager->persist($ticketPriority);
            }
            $manager->flush();
        }
    }
}
