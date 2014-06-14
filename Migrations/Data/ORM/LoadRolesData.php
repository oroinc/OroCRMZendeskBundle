<?php

namespace OroCRM\Bundle\ZendeskBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskUserRole;

class LoadRolesData extends AbstractTranslatableEntityFixture
{
    const TRANSLATION_PREFIX = 'zendesk_user_role';

    /**
     * @var array
     */
    protected $names = array(
        ZendeskUserRole::ROLE_ADMIN,
        ZendeskUserRole::ROLE_AGENT,
        ZendeskUserRole::ROLE_END_USER
    );
    /**
     * Load entities to DB
     *
     * @param ObjectManager $manager
     */
    protected function loadEntities(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroCRMZendeskBundle:ZendeskUserRole');

        $translationLocales = $this->getTranslationLocales();

        foreach ($translationLocales as $locale) {
            foreach ($this->names as $name) {
                /** @var ZendeskUserRole $zendeskUserRole */
                $zendeskUserRole = $repository->findOneBy(array('name' => $name));
                if (!$zendeskUserRole) {
                    $zendeskUserRole = new ZendeskUserRole($name);
                }

                // set locale and label
                $label = $this->translate($name, LoadStatusData::TRANSLATION_PREFIX, $locale);
                $zendeskUserRole->setLocale($locale)
                    ->setLabel($label);

                // save
                $manager->persist($zendeskUserRole);
            }

            $manager->flush();
        }
    }
}
