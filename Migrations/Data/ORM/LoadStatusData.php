<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;

/**
 * Loads Zendesk ticket statuses.
 */
class LoadStatusData extends AbstractTranslatableEntityFixture
{
    private const TRANSLATION_PREFIX = 'ticket_status';

    #[\Override]
    protected function loadEntities(ObjectManager $manager): void
    {
        $ticketStatusRepository = $manager->getRepository(TicketStatus::class);
        $translationLocales = $this->getTranslationLocales();
        $names = [
            TicketStatus::STATUS_CLOSED,
            TicketStatus::STATUS_HOLD,
            TicketStatus::STATUS_NEW,
            TicketStatus::STATUS_OPEN,
            TicketStatus::STATUS_PENDING,
            TicketStatus::STATUS_SOLVED
        ];
        foreach ($translationLocales as $locale) {
            foreach ($names as $name) {
                /** @var TicketStatus $ticketStatus */
                $ticketStatus = $ticketStatusRepository->findOneBy(['name' => $name]);
                if (!$ticketStatus) {
                    $ticketStatus = new TicketStatus($name);
                }

                $ticketStatus->setLocale($locale);
                $ticketStatus->setLabel($this->translate($name, self::TRANSLATION_PREFIX, $locale));
                $manager->persist($ticketStatus);
            }
            $manager->flush();
        }
    }
}
