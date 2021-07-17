<?php

namespace Oro\Bundle\ZendeskBundle\Model\EntityProvider;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ContactBundle\Entity\ContactEmail;
use Oro\Bundle\ContactBundle\Entity\ContactPhone;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User as OroUser;
use Oro\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use Oro\Bundle\ZendeskBundle\Provider\ChannelType;

class OroEntityProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $namePrefixes;

    /**
     * @var array
     */
    protected $nameSuffixes;

    public function __construct(ManagerRegistry $registry, array $namePrefixes, array $nameSuffixes)
    {
        $this->registry     = $registry;
        $this->namePrefixes = $namePrefixes;
        $this->nameSuffixes = $nameSuffixes;
    }

    /**
     * @param Channel $channel
     *
     * @return null|OroUser
     */
    public function getDefaultUser(Channel $channel)
    {
        $user = $channel->getDefaultUserOwner();
        if ($user) {
            $user = $this->registry->getRepository('OroUserBundle:User')
                ->find($user->getId());
        }

        return $user;
    }

    /**
     * @param ZendeskUser $user
     * @param bool        $defaultIfNotExist
     *
     * @return OroUser|null
     */
    public function getUser(ZendeskUser $user, $defaultIfNotExist = false)
    {
        $oroUser = $this->registry->getRepository('OroUserBundle:User')
            ->findOneBy(array('email' => $user->getEmail()));

        if (!$oroUser) {
            /**
             * @var Email $email
             */
            $email = $this->registry->getRepository('OroUserBundle:Email')
                ->findOneBy(array('email' => $user->getEmail()));

            if ($email) {
                $oroUser = $email->getUser();
            }
        }

        if ($defaultIfNotExist && !$oroUser) {
            $oroUser = $this->getDefaultUser($user->getChannel());
        }

        return $oroUser;
    }

    /**
     * @param ZendeskUser $user
     *
     * @return Contact|null
     */
    public function getContact(ZendeskUser $user)
    {
        if (!$user->getEmail()) {
            return null;
        }

        $contactEmails = $this->registry->getRepository('OroContactBundle:ContactEmail')
            ->findBy(
                array(
                    'email' => $user->getEmail()
                ),
                array('primary' => 'DESC')
            );

        if (count($contactEmails) > 0) {
            foreach ($contactEmails as $contactEmail) {
                /** @var ContactEmail $contactEmail $owner */
                $owner = $contactEmail->getOwner();
                if ($owner->getOrganization()->getId() === $user->getChannel()->getOrganization()->getId()) {
                    return $contactEmail->getOwner();
                }
            }
        }

        $contact = new Contact();
        $contact->setOwner($this->getDefaultUser($user->getChannel()));
        $contact->setOrganization($user->getChannel()->getOrganization());

        if ($user->getPhone()) {
            $phone = new ContactPhone();
            $phone->setPrimary(true);
            $phone->setPhone($user->getPhone());
            $contact->addPhone($phone);
        }

        $email = new ContactEmail();
        $email->setPrimary(true);
        $email->setEmail($user->getEmail());
        $contact->addEmail($email);

        return $this->setContactName($user, $contact);
    }

    /**
     * @param $channelId
     *
     * @return null|Channel
     */
    public function getChannelById($channelId)
    {
        return $this->registry->getManager()->find('OroIntegrationBundle:Channel', $channelId);
    }

    /**
     * Get all enabled Zendesk channels
     *
     * @return Channel[]
     */
    public function getEnabledChannels()
    {
        return $this->registry->getRepository('OroIntegrationBundle:Channel')
            ->getConfiguredChannelsForSync(ChannelType::TYPE);
    }

    /**
     * Get all enabled Zendesk channels with enabled two way sync
     *
     * @return Channel[]
     */
    public function getEnabledTwoWaySyncChannels()
    {
        return array_filter(
            $this->getEnabledChannels(),
            function (Channel $channel) {
                return $channel->getSynchronizationSettings()->offsetGetOr('isTwoWaySyncEnabled', false);
            }
        );
    }

    public function getAccountByContact(Contact $contact)
    {
        /** @var EntityRepository $repository */
        $repository = $this->registry->getRepository('OroAccountBundle:Account');
        $qb         = $repository->createQueryBuilder('account');
        $qb->where('account.defaultContact = :contact')
            ->setMaxResults(1)
            ->setParameter('contact', $contact);

        $result = $qb->getQuery()->execute();

        if (count($result) > 0) {
            return $result[0];
        }

        $qb = $repository->createQueryBuilder('account');
        $qb->leftJoin('account.contacts', 'contact')
            ->where('contact.id = :id')
            ->setMaxResults(1);
        $result = $qb->getQuery()->execute(array('id' => $contact->getId()));

        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * @param $id
     *
     * @return null|CaseEntity
     */
    public function getCaseById($id)
    {
        return $this->registry->getManager()->find('OroCaseBundle:CaseEntity', $id);
    }

    /**
     * @param ZendeskUser $user
     * @param Contact     $contact
     *
     * @return null|Contact
     */
    protected function setContactName(ZendeskUser $user, Contact $contact)
    {
        $userName = trim($user->getName());

        if (empty($userName)) {
            return null;
        }

        $nameParts = preg_split('/[\s]+/', $userName, 5);

        $nameParts = $this->setContactNamePrefixAndSuffix($nameParts, $contact);

        $contact->setFirstName($nameParts[0]);

        $namePartsLength = count($nameParts);

        if ($namePartsLength > 2) {
            $contact->setMiddleName($nameParts[1]);
            $contact->setLastName(implode(' ', array_slice($nameParts, 2)));
        } else {
            $contact->setLastName(isset($nameParts[1]) ? $nameParts[1] : $nameParts[0]);
        }

        return $contact;
    }

    /**
     * @param array   $nameParts
     * @param Contact $contact
     *
     * @return array
     */
    protected function setContactNamePrefixAndSuffix(array $nameParts, Contact $contact)
    {
        if (count($nameParts) > 2 && $this->isNamePrefix(reset($nameParts))) {
            $contact->setNamePrefix(current($nameParts));
            unset($nameParts[key($nameParts)]);
        }

        if (count($nameParts) > 2 && $this->isNameSuffix(end($nameParts))) {
            $contact->setNameSuffix(current($nameParts));
            unset($nameParts[key($nameParts)]);
        }

        return array_values($nameParts);
    }

    /**
     * @param string $namePart
     *
     * @return bool
     */
    protected function isNamePrefix($namePart)
    {
        if (substr($namePart, -1) == '.') {
            $namePart = substr_replace($namePart, '', -1);
        }

        return array_search($namePart, $this->namePrefixes) !== false;
    }

    /**
     * @param string $namePart
     *
     * @return bool
     */
    protected function isNameSuffix($namePart)
    {
        if (substr($namePart, -1) == '.') {
            $namePart = substr_replace($namePart, '', -1);
        }

        return array_search($namePart, $this->nameSuffixes) !== false;
    }
}
