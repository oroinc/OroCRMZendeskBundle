<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\Model\EntityProvider;

use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadZendeskUserData;

class OroEntityProviderTest extends WebTestCase
{
    private OroEntityProvider $target;
    private Channel $channel;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadZendeskUserData::class]);

        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->target = $this->getContainer()
            ->get('oro_zendesk.entity_provider.oro');
    }

    /**
     * @dataProvider getContactDataProvider
     */
    public function testGetContact(array $expected, User $user)
    {
        $user->setChannel($this->channel);
        $defaultOwner = $this->channel->getDefaultUserOwner();
        $contact = $this->target->getContact($user);
        $this->assertInstanceOf(Contact::class, $contact);
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
     */
    public function getContactDataProvider(): array
    {
        return [
            'Create valid contact' => [
                'expected' => [
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'Alex',
                    'last_name' => $lastName = 'Smith',
                    'middle_name' => $middleName = 'M.',
                    'prefix' => $prefix = 'Mr.',
                    'suffix' => $suffix = 'Jr.',
                    'phone'=> $phone = '123456789'
                ],
                'user' => $this->getUser($email, "{$prefix} {$firstName} {$middleName} {$lastName} {$suffix}", $phone)
            ],
            'Create valid contact is name divided by tabs' => [
                'expected' => [
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'prefix' => $prefix = 'Mr',
                    'suffix' => $suffix = 'Jr',
                    'phone'=> $phone
                ],
                'user' => $this->getUser(
                    $email,
                    "{$prefix}\t{$firstName}\t{$middleName}\t{$lastName}\t{$suffix}",
                    $phone
                )
            ],
            'Create valid contact if prefix and suffix not recognisable' => [
                'expected' => [
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'TestPrefix.',
                    'last_name' => $lastName = 'M. Smith Test_Suffix.',
                    'middle_name' => $middleName = 'Alex',
                    'phone'=> $phone = '123456789'
                ],
                'user' => $this->getUser($email, "{$firstName} {$middleName} {$lastName}", $phone)
            ],
            'Create valid contact if no suffix' => [
                'expected' => [
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'Alex',
                    'last_name' => $lastName = 'Smith',
                    'middle_name' => $middleName = 'M.',
                    'prefix' => $prefix = 'Dr',
                    'phone'=> $phone = '123456789'
                ],
                'user' => $this->getUser($email, "{$prefix} {$firstName} {$middleName} {$lastName}", $phone)
            ],
            'Create valid contact if no prefix' => [
                'expected' => [
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'Alex',
                    'last_name' => $lastName = 'Smith',
                    'middle_name' => $middleName = 'M.',
                    'suffix' => $suffix = 'Jnr.',
                    'phone'=> $phone = '123456789'
                ],
                'user' => $this->getUser($email, "{$firstName} {$middleName} {$lastName} {$suffix}", $phone)
            ],
            'Create valid contact if no prefix and suffix' => [
                'expected' => [
                    'email' => $email = 'not_exist_email@mail.com',
                    'first_name' => $firstName = 'Alex',
                    'last_name' => $lastName = 'Smith',
                    'middle_name' => $middleName = 'M.',
                    'phone'=> $phone = '123456789'
                ],
                'user' => $this->getUser($email, "{$firstName} {$middleName} {$lastName}", $phone)
            ],
            'Create valid contact if name have no middle name and suffix' => [
                'expected' => [
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => '',
                    'prefix' => $prefix,
                    'last_name' => $suffix,
                    'phone'=> $phone
                ],
                'user' => $this->getUser($email, "{$prefix} {$firstName} {$suffix}", $phone)
            ],
            'Create valid contact if name have only two parts' => [
                'expected' => [
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => '',
                    'last_name' => $lastName,
                    'phone'=> $phone
                ],
                'user' => $this->getUser($email, "{$firstName} {$lastName}", $phone)
            ],
            'Create valid contact if name have only two parts and it is equal prefix and suffix' => [
                'expected' => [
                    'email' => $email,
                    'first_name' => $prefix,
                    'middle_name' => '',
                    'last_name' => $suffix,
                    'phone'=> $phone
                ],
                'user' => $this->getUser($email, "{$prefix} {$suffix}", $phone)
            ],
            'Create valid contact if user name have spaces' => [
                'expected' => [
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'phone'=> $phone
                ],
                'user' => $this->getUser($email, "  {$firstName} {$middleName} {$lastName}   ", $phone)
            ],
            'Create valid contact if only first name specified' => [
                'expected' => [
                    'email' => $email,
                    'first_name' => $firstName,
                    'middle_name' => '',
                    'last_name' => $firstName,
                    'phone'=> $phone
                ],
                'user' => $this->getUser($email, "  {$firstName}   ", $phone)
            ]
        ];
    }

    private function getUser(string $email, string $name, string $phone): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPhone($phone);

        return $user;
    }
}
