<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;

/**
 * Loads Zendesk ticket types.
 */
class LoadTicketTypeData extends AbstractTranslatableEntityFixture
{
    private const TRANSLATION_PREFIX = 'ticket_type';

    #[\Override]
    protected function loadEntities(ObjectManager $manager): void
    {
        $ticketTypeRepository = $manager->getRepository(TicketType::class);
        $translationLocales = $this->getTranslationLocales();
        $names = [
            TicketType::TYPE_INCIDENT,
            TicketType::TYPE_PROBLEM,
            TicketType::TYPE_QUESTION,
            TicketType::TYPE_TASK
        ];
        foreach ($translationLocales as $locale) {
            foreach ($names as $name) {
                /** @var TicketType $ticketType */
                $ticketType = $ticketTypeRepository->findOneBy(['name' => $name]);
                if (!$ticketType) {
                    $ticketType = new TicketType($name);
                }

                $ticketType->setLocale($locale);
                $ticketType->setLabel($this->translate($name, self::TRANSLATION_PREFIX, $locale));
                $manager->persist($ticketType);
            }
            $manager->flush();
        }
    }
}
