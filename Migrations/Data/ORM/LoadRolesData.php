<?php

namespace Oro\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;

/**
 * Loads Zendesk user roles.
 */
class LoadRolesData extends AbstractTranslatableEntityFixture
{
    private const TRANSLATION_PREFIX = 'zendesk_user_role';

    /**
     * {@inheritDoc}
     */
    protected function loadEntities(ObjectManager $manager): void
    {
        $userRoleRepository = $manager->getRepository(UserRole::class);
        $translationLocales = $this->getTranslationLocales();
        $names = [
            UserRole::ROLE_ADMIN,
            UserRole::ROLE_AGENT,
            UserRole::ROLE_END_USER
        ];
        foreach ($translationLocales as $locale) {
            foreach ($names as $name) {
                /** @var UserRole $zendeskUserRole */
                $zendeskUserRole = $userRoleRepository->findOneBy(['name' => $name]);
                if (!$zendeskUserRole) {
                    $zendeskUserRole = new UserRole($name);
                }

                $zendeskUserRole->setLocale($locale);
                $zendeskUserRole->setLabel($this->translate($name, self::TRANSLATION_PREFIX, $locale));
                $manager->persist($zendeskUserRole);
            }
            $manager->flush();
        }
    }
}
