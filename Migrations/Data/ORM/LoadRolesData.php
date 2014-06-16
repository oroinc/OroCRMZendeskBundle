<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;

class LoadRolesData extends AbstractTranslatableEntityFixture
{
    const TRANSLATION_PREFIX = 'zendesk_user_role';

    /**
     * @var array
     */
    protected $names = array(
        UserRole::ROLE_ADMIN,
        UserRole::ROLE_AGENT,
        UserRole::ROLE_END_USER
    );
    /**
     * Load entities to DB
     *
     * @param ObjectManager $manager
     */
    protected function loadEntities(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroCRMZendeskBundle:UserRole');

        $translationLocales = $this->getTranslationLocales();

        foreach ($translationLocales as $locale) {
            foreach ($this->names as $name) {
                /** @var UserRole $zendeskUserRole */
                $zendeskUserRole = $repository->findOneBy(array('name' => $name));
                if (!$zendeskUserRole) {
                    $zendeskUserRole = new UserRole($name);
                }

                // set locale and label
                $label = $this->translate($name, static::TRANSLATION_PREFIX, $locale);
                $zendeskUserRole->setLocale($locale)
                    ->setLabel($label);

                // save
                $manager->persist($zendeskUserRole);
            }

            $manager->flush();
        }
    }
}
