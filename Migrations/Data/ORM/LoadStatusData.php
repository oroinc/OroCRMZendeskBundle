<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;

class LoadStatusData extends AbstractTranslatableEntityFixture
{
    const TRANSLATION_PREFIX = 'ticket_status';

    /**
     * @var array
     */
    protected $names = array(
        TicketStatus::STATUS_CLOSED,
        TicketStatus::STATUS_HOLD,
        TicketStatus::STATUS_NEW,
        TicketStatus::STATUS_OPEN,
        TicketStatus::STATUS_PENDING,
        TicketStatus::STATUS_SOLVED,
    );
    /**
     * Load entities to DB
     */
    protected function loadEntities(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroZendeskBundle:TicketStatus');

        $translationLocales = $this->getTranslationLocales();

        foreach ($translationLocales as $locale) {
            foreach ($this->names as $name) {
                /** @var TicketStatus $ticketStatus */
                $ticketStatus = $repository->findOneBy(array('name' => $name));
                if (!$ticketStatus) {
                    $ticketStatus = new TicketStatus($name);
                }

                // set locale and label
                $label = $this->translate($name, static::TRANSLATION_PREFIX, $locale);
                $ticketStatus->setLocale($locale)
                    ->setLabel($label);

                // save
                $manager->persist($ticketStatus);
            }

            $manager->flush();
        }
    }
}
