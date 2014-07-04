<?php

namespace OroCRM\Bundle\ZendeskBundle\Model\EntityProvider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User as OroUser;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRM\Bundle\ContactBundle\Entity\ContactEmail;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactPhone;
use OroCRM\Bundle\ZendeskBundle\Provider\ChannelType;

class OroEntityProvider
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
     * @param Channel $channel
     * @return null|OroUser
     */
    public function getDefaultUser(Channel $channel)
    {
        $user = $channel->getDefaultUserOwner();
        if ($user) {
            $user = $this->entityManager->getRepository('OroUserBundle:User')
                ->find($user->getId());
        }
        return $user;
    }

    /**
     * @param ZendeskUser $user
     * @return OroUser|null
     */
    public function getUser(ZendeskUser $user)
    {
        $oroUser = $this->entityManager->getRepository('OroUserBundle:User')
            ->findOneBy(array('email' => $user->getEmail()));

        if (!$oroUser) {
            /**
             * @var Email $email
             */
            $email = $this->entityManager->getRepository('OroUserBundle:Email')
                ->findOneBy(
                    array(
                        'email' => $user->getEmail()
                    )
                );

            if ($email) {
                $oroUser = $email->getUser();
            }
        }

        return $oroUser;
    }

    /**
     * @param ZendeskUser $user
     * @return Contact|null
     */
    public function getContact(ZendeskUser $user)
    {
        if (!$user->getEmail()) {
            return null;
        }
        /**
         * @var ContactEmail $contactEmail
         */
        $contactEmail = $this->entityManager->getRepository('OroCRMContactBundle:ContactEmail')
            ->findOneBy(
                array(
                    'email' => $user->getEmail()
                ),
                array('primary' => 'DESC')
            );

        if ($contactEmail) {
            return $contactEmail->getOwner();
        }

        $contact = new Contact();

        if ($user->getPhone()) {
            $phone = new ContactPhone();
            $phone->setPrimary(true);
            $phone->setPhone($phone);
            $contact->addPhone($phone);
        }

        $email = new ContactEmail();
        $email->setPrimary(true);
        $email->setEmail($user->getEmail());
        $contact->addEmail($email);

        $nameParts = array_pad(explode(' ', $user->getName(), 2), 2, '');
        $contact->setFirstName($nameParts[0]);
        $contact->setLastName($nameParts[1]);

        return $contact;
    }

    /**
     * @param $channelId
     * @return null|Channel
     */
    public function getChannelById($channelId)
    {
        return $this->entityManager->getRepository('OroIntegrationBundle:Channel')->find($channelId);
    }

    /**
     * @return array
     */
    public function getAvailableChannels()
    {
        return $this->entityManager->getRepository('OroIntegrationBundle:Channel')
            ->findBy(array('type' => ChannelType::TYPE, 'enabled' => true));
    }

    /**
     * @param $id
     *
     * @return null|CaseEntity
     */
    public function getCaseById($id)
    {
        return $this->entityManager->getRepository('OroCRMCaseBundle:CaseEntity')
            ->find($id);
    }
}
