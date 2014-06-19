<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Strategy;

use Doctrine\ORM\EntityManager;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\UserSyncStrategy;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole as ZendeskUserRole;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class UserSyncStrategyTest extends WebTestCase
{
    /**
     * @var UserSyncStrategy
     */
    protected $strategy;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadZendeskUserData']);

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->strategy = $this->getContainer()->get('orocrm_zendesk.importexport.strategy.user_sync');
        $context = $this->getMock('Oro\\Bundle\\ImportExportBundle\\Context\\ContextInterface');
        $this->strategy->setImportExportContext($context);
    }

    public function testProcessNewZendeskUser()
    {
        $zendeskUser = $this->createZendeskUser()->setId(1);

        $this->assertEquals($zendeskUser, $this->strategy->process($zendeskUser));
        $this->assertFalse($this->entityManager->contains($zendeskUser));
    }

    public function testProcessExistingZendeskUser()
    {
        $zendeskUser = $this->createZendeskUser()
            ->setId(1015)
            ->setUrl('https://foo.zendesk.com/api/v2/users/1015.json?1')
            ->setName('John Doe')
            ->setEmail('john.doe@example.com')
            ->setRole(new ZendeskUserRole(ZendeskUserRole::ROLE_AGENT))
            ->setPhone('555-111-222')
            ->setActive(true)
            ->setAlias('johndoe')
            ->setDetails('Some details')
            ->setExternalId(115)
            ->setCreatedAt(new \DateTime('2014-06-10T12:12:21Z'))
            ->setUpdatedAt(new \DateTime('2014-06-09T17:45:22Z'))
            ->setLastLoginAt(new \DateTime('2014-06-11T15:26:11Z'))
            ->setOnlyPrivateComments(true)
            ->setTicketRestriction('ticket_restriction')
            ->setVerified(true)
            ->setTimeZone('Arizona')
            ->setLocale('en-US');

        $result = $this->strategy->process($zendeskUser);

        $this->assertEquals($zendeskUser, $result);
        $this->assertNotSame($zendeskUser, $result);
        $this->assertFalse($this->entityManager->contains($zendeskUser));
        $this->assertTrue($this->entityManager->contains($result));
    }

    public function testProcessLinksZendeskUserRole()
    {
        $roleName = ZendeskUserRole::ROLE_AGENT;

        $zendeskUser = $this->createZendeskUser()
            ->setId(1)
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->strategy->process($zendeskUser));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\UserRole', $zendeskUser->getRole());
        $this->assertEquals($roleName, $zendeskUser->getRole()->getName());
        $this->assertTrue($this->entityManager->contains($zendeskUser->getRole()));
    }

    /**
     * @dataProvider userCompatibleRoleDataProvider
     */
    public function testProcessLinksRelatedUser($roleName)
    {
        $email = 'bob.miller@orouser.com';

        $zendeskUser = $this->createZendeskUser()
            ->setId(1)
            ->setEmail($email)
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->strategy->process($zendeskUser));

        $this->assertInstanceOf('Oro\\Bundle\\UserBundle\\Entity\\User', $zendeskUser->getRelatedUser());
        $this->assertEquals($email, $zendeskUser->getRelatedUser()->getEmail());
        $this->assertTrue($this->entityManager->contains($zendeskUser->getRelatedUser()));
    }

    public function userCompatibleRoleDataProvider()
    {
        return array(
            ZendeskUserRole::ROLE_AGENT => array(ZendeskUserRole::ROLE_AGENT),
            ZendeskUserRole::ROLE_ADMIN => array(ZendeskUserRole::ROLE_ADMIN),
        );
    }

    public function testProcessSkipsRelatedUser()
    {
        $email = 'bob.miller@orouser.com';

        $zendeskUser = $this->createZendeskUser()
            ->setId(1)
            ->setEmail($email);

        $this->assertEquals($zendeskUser, $this->strategy->process($zendeskUser));
        $this->assertNull($zendeskUser->getRelatedUser());
    }

    public function testProcessLinksRelatedContact()
    {
        $roleName = ZendeskUserRole::ROLE_END_USER;
        $email = 'mike.johnson@contact.com';

        $zendeskUser = $this->createZendeskUser()
            ->setId(1)
            ->setEmail($email)
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->strategy->process($zendeskUser));

        $relatedContact = $zendeskUser->getRelatedContact();
        $this->assertInstanceOf('OroCRM\\Bundle\\ContactBundle\\Entity\\Contact', $relatedContact);
        $this->assertEquals($email, $relatedContact->getEmail());
        $this->assertTrue($this->entityManager->contains($relatedContact));
    }

    public function testProcessCreatesRelatedContact()
    {
        $roleName = ZendeskUserRole::ROLE_END_USER;
        $email = 'bob.miller@orouser.com';

        $zendeskUser = $this->createZendeskUser()
            ->setId(1)
            ->setEmail($email)
            ->setName('Bob Miller Jr.')
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->strategy->process($zendeskUser));

        $relatedContact = $zendeskUser->getRelatedContact();
        $this->assertInstanceOf('OroCRM\\Bundle\\ContactBundle\\Entity\\Contact', $relatedContact);
        $this->assertFalse($this->entityManager->contains($relatedContact));
        $this->assertEquals($email, $relatedContact->getEmail());
        $this->assertEquals('Bob', $relatedContact->getFirstName());
        $this->assertEquals('Miller Jr.', $relatedContact->getLastName());
    }

    protected function createZendeskUser()
    {
        return new ZendeskUser();
    }
}
