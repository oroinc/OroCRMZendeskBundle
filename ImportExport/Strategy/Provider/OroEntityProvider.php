<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User as OroUser;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactPhone;
use OroCRM\Bundle\ZendeskBundle\Provider\ConfigurationProvider;

class OroEntityProvider
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ConfigurationProvider
     */
    protected $configurationProvider;

    /**
     * @param EntityManager $entityManager
     * @param ConfigurationProvider $configurationProvider
     */
    public function __construct(EntityManager $entityManager, ConfigurationProvider $configurationProvider)
    {
        $this->entityManager = $entityManager;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param ZendeskUser $user
     * @return OroUser|null
     */
    public function getUser(ZendeskUser $user)
    {
        $oroUser = $this->entityManager->getRepository('OroUserBundle:User')
            ->findOneBy(array('email' => $user->getEmail()));

        return $oroUser;
    }

    /**
     * @return OroUser|null
     */
    public function getDefaultUser()
    {
        return $this->configurationProvider->getOroDefaultUser();
    }

    /**
     * @param ZendeskUser $user
     * @return Contact|null
     */
    public function getContact(ZendeskUser $user)
    {
        $contact = $this->entityManager->getRepository('OroCRMContactBundle:Contact')
            ->findOneBy(
                array(
                    'email' => $user->getEmail()
                )
            );

        if (!$contact) {
            $contact = new Contact();

            if ($user->getPhone()) {
                $phone = new ContactPhone();
                $phone->setPrimary(true);
                $phone->setPhone($phone);
                $contact->addPhone($phone);
            }

            $contact->setEmail($user->getEmail());

            $nameParts = array_pad(explode(' ', $user->getName(), 2), 2, '');
            $contact->setFirstName($nameParts[0]);
            $contact->setLastName($nameParts[1]);
        }

        return $contact;
    }
}
