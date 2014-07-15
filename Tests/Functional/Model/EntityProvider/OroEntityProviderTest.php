<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Model\EntityProvider;

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

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            array(
                'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadZendeskUserData'
            )
        );
        $this->target = $this->getContainer()
            ->get('orocrm_zendesk.entity_provider.oro');
    }

    /**
     * @dataProvider getContactDataProvider
     */
    public function testGetContact(array $expected, User $user)
    {

        $contact = $this->target->getContact($user);
        $this->assertInstanceOf('OroCRM\Bundle\ContactBundle\Entity\Contact', $contact);
        $this->assertEquals(
            $contact->getPrimaryPhone()
                ->getPhone(),
            $expected['phone']
        );
        $this->assertEquals(
            $contact->getPrimaryEmail()
                ->getEmail(),
            $expected['email']
        );
        $this->assertEquals($contact->getFirstName(), $expected['first_name']);
        $this->assertEquals($contact->getLastName(), $expected['last_name']);
    }

    /**
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
                    'phone'=> $phone = '123456789'
                ),
                'user' => $this->getUser($email, "{$firstName} {$lastName}", $phone)
            ),
            'Create valid contact is name divided by tabs' => array(
                'expected' => array(
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone'=> $phone
                ),
                'user' => $this->getUser($email, "{$firstName}\t{$lastName}", $phone)
            ),
            'Create valid contact if user name have spaces' => array(
                'expected' => array(
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone'=> $phone
                ),
                'user' => $this->getUser($email, "  {$firstName} {$lastName}   ", $phone)
            ),
            'Create valid contact if only first name specified' => array(
                'expected' => array(
                    'email' => $email,
                    'first_name' => $firstName,
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
