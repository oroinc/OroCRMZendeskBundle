<?php

namespace Oro\Bundle\ZendeskBundle\Provider;

use Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface;
use Oro\Bundle\ZendeskBundle\Entity\User;

class UserPhoneProvider implements PhoneProviderInterface
{
    /**
     * Gets a phone number of the given User object
     *
     * @param User $object
     *
     * @return string|null
     */
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
