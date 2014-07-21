<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Model\EntityProvider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;

/**
 * @dbIsolation
 */
class OroEntityProviderTest extends WebTestCase
{
    /**
     * @var OroEntityProvider
     */
    protected $target;

    /**
     * @var Channel
     */
    protected $channel;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            array(
                'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadZendeskUserData'
            )
        );
        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->target = $this->getContainer()
            ->get('orocrm_zendesk.entity_provider.oro');
    }

    /**
     * @dataProvider getContactDataProvider
     */
    public function testGetContact(array $expected, User $user)
    {
        $user->setChannel($this->channel);
        $defaultOwner = $this->channel->getDefaultUserOwner();
        $contact = $this->target->getContact($user);
        $this->assertInstanceOf('OroCRM\Bundle\ContactBundle\Entity\Contact', $contact);
        $this->assertEquals(
            $expected['phone'],
            $contact->getPrimaryPhone()
                ->getPhone()
        );
        $this->assertEquals(
            $expected['email'],
            $contact->getPrimaryEmail()
                ->getEmail()
        );
        $this->assertEquals($expected['first_name'], $contact->getFirstName(), 'incorrect first name');
        $this->assertEquals($expected['middle_name'], $contact->getMiddleName(), 'incorrect middle name');
        $this->assertEquals($expected['last_name'], $contact->getLastName(), 'incorrect last name');
        if (isset($expected['prefix'])) {
            $this->assertEquals($expected['prefix'], $contact->getNamePrefix(), 'incorrect prefix');
        }
        if (isset($expected['suffix'])) {
            $this->assertEquals($expected['suffix'], $contact->getNameSuffix(), 'incorrect suffix');
        }
        $this->assertEquals($defaultOwner, $contact->getOwner());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function getContactDataProvider()
    {
        return array(
            'Create valid contact' => array(
                'expected' => array(
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'Alex',
                    'last_name' => $lastName = 'Smith',
                    'middle_name' => $middleName = 'M.',
                    'prefix' => $prefix = 'Mr.',
                    'suffix' => $suffix = 'Jr.',
                    'phone'=> $phone = '123456789'
                ),
                'user' => $this->getUser($email, "{$prefix} {$firstName} {$middleName} {$lastName} {$suffix}", $phone)
            ),
            'Create valid contact is name divided by tabs' => array(
                'expected' => array(
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'prefix' => $prefix = 'Mr',
                    'suffix' => $suffix = 'Jr',
                    'phone'=> $phone
                ),
                'user' => $this->getUser(
                    $email,
                    "{$prefix}\t{$firstName}\t{$middleName}\t{$lastName}\t{$suffix}",
                    $phone
                )
            ),
            'Create valid contact if prefix and suffix not recognisable' => array(
                'expected' => array(
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'TestPrefix.',
                    'last_name' => $lastName = 'M. Smith Test_Suffix.',
                    'middle_name' => $middleName = 'Alex',
                    'phone'=> $phone = '123456789'
                ),
                'user' => $this->getUser($email, "{$firstName} {$middleName} {$lastName}", $phone)
            ),
            'Create valid contact if no suffix' => array(
                'expected' => array(
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'Alex',
                    'last_name' => $lastName = 'Smith',
                    'middle_name' => $middleName = 'M.',
                    'prefix' => $prefix = 'Dr',
                    'phone'=> $phone = '123456789'
                ),
                'user' => $this->getUser($email, "{$prefix} {$firstName} {$middleName} {$lastName}", $phone)
            ),
            'Create valid contact if no prefix' => array(
                'expected' => array(
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'Alex',
                    'last_name' => $lastName = 'Smith',
                    'middle_name' => $middleName = 'M.',
                    'suffix' => $suffix = 'Jnr.',
                    'phone'=> $phone = '123456789'
                ),
                'user' => $this->getUser($email, "{$firstName} {$middleName} {$lastName} {$suffix}", $phone)
            ),
            'Create valid contact if no prefix and suffix' => array(
                'expected' => array(
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'Alex',
                    'last_name' => $lastName = 'Smith',
                    'middle_name' => $middleName = 'M.',
                    'phone'=> $phone = '123456789'
                ),
                'user' => $this->getUser($email, "{$firstName} {$middleName} {$lastName}", $phone)
            ),
            'Create valid contact if name have no middle name and suffix' => array(
                'expected' => array(
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => '',
                    'prefix' => $prefix,
                    'last_name' => $suffix,
                    'phone'=> $phone
                ),
                'user' => $this->getUser($email, "{$prefix} {$firstName} {$suffix}", $phone)
            ),
            'Create valid contact if name have only two parts' => array(
                'expected' => array(
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => '',
                    'last_name' => $lastName,
                    'phone'=> $phone
                ),
                'user' => $this->getUser($email, "{$firstName} {$lastName}", $phone)
            ),
            'Create valid contact if name have only two parts and it is equal prefix and suffix' => array(
                'expected' => array(
                    'email' => $email,
                    'first_name' => $prefix,
                    'middle_name' => '',
                    'last_name' => $suffix,
                    'phone'=> $phone
                ),
                'user' => $this->getUser($email, "{$prefix} {$suffix}", $phone)
            ),
            'Create valid contact if user name have spaces' => array(
                'expected' => array(
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'phone'=> $phone
                ),
                'user' => $this->getUser($email, "  {$firstName} {$middleName} {$lastName}   ", $phone)
            ),
            'Create valid contact if only first name specified' => array(
                'expected' => array(
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => '',
                    'last_name' => $firstName,
                    'phone'=> $phone
                ),
                'user' => $this->getUser($email, "  {$firstName}   ", $phone)
            )
        );
    }

    /**
     * @param bool   $email
     * @param string $name
     * @param string $phone
     * @return User
     */
    protected function getUser($email, $name, $phone)
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPhone($phone);
        return $user;
    }
}
