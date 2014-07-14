<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\Model\EntityProvider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
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

    public function testGetContactCreateValidContact()
    {
        $firstName = 'Alex';
        $lastName = 'Smith';
        $phone = '123456789';
        $email = 'not_exist_email@mail.com';
        $name = "{$firstName} {$lastName}";

        $user = $this->getUser($email, $name, $phone);
        $contact = $this->target->getContact($user);
        $this->checkContact($contact, $phone, $email, $firstName, $lastName);

        $name = "     {$firstName}    {$lastName}    ";
        $user = $this->getUser($email, $name, $phone);
        $contact = $this->target->getContact($user);
        $this->checkContact($contact, $phone, $email, $firstName, $lastName);

        $name = "{$firstName}    ";
        $user = $this->getUser($email, $name, $phone);
        $contact = $this->target->getContact($user);
        $this->checkContact($contact, $phone, $email, $firstName, $firstName);

        $user = $this->getUser($email, $firstName, $phone);
        $contact = $this->target->getContact($user);
        $this->checkContact($contact, $phone, $email, $firstName, $firstName);
    }

    /**
     * @param Contact|null $contact
     * @param string       $phone
     * @param string       $email
     * @param string       $firstName
     * @param string       $lastName
     */
    protected function checkContact($contact, $phone, $email, $firstName, $lastName)
    {
        $this->assertInstanceOf('OroCRM\Bundle\ContactBundle\Entity\Contact', $contact);
        $this->assertEquals(
            $contact->getPrimaryPhone()
                ->getPhone(),
            $phone
        );
        $this->assertEquals(
            $contact->getPrimaryEmail()
                ->getEmail(),
            $email
        );
        $this->assertEquals($contact->getFirstName(), $firstName);
        $this->assertEquals($contact->getLastName(), $lastName);
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
