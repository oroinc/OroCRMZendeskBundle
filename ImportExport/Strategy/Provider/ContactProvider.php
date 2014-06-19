<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider;

use Doctrine\ORM\EntityManager;

use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactPhone;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;

class ContactProvider
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
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
