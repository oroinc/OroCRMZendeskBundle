<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface;
use Oro\Bundle\ZendeskBundle\Entity\User;

/**
 * Provides phone numbers for Zendesk users.
 */
class UserPhoneProvider implements PhoneProviderInterface
{
    /**
     * Gets a phone number of the given User object
     *
     * @param User $object
     *
     * @return string|null
     */
    #[\Override]
    public function getPhoneNumber($object)
    {
        return $object->getPhone();
    }

    /**
     * Gets a list of all phone numbers available for the given User object
     *
     * @param User $object
     *
     * @return array of [phone number, phone owner]
     */
    #[\Override]
    public function getPhoneNumbers($object)
    {
        $result = [];

        $phone = $object->getPhone();
        if (!empty($phone)) {
            $result[] = [$phone, $object];
        }

        return $result;
    }
}
