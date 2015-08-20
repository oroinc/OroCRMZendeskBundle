<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Processor\ImportUserProcessor;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole as ZendeskUserRole;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class ImportUserProcessorTest extends WebTestCase
{
    /**
     * @var ImportUserProcessor
     */
    protected $processor;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var Channel
     */
    protected $channel;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadZendeskUserData']);

        $this->registry  = $this->getContainer()->get('doctrine');
        $this->processor = $this->getContainer()->get('orocrm_zendesk.importexport.processor.import_user');
        $this->context   = $this->getMock('Oro\\Bundle\\ImportExportBundle\\Context\\ContextInterface');
        $this->channel   = $this->getReference('zendesk_channel:first_test_channel');
        $this->context->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(array('channel', null, $this->channel->getId()))));
        $this->processor->setImportExportContext($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Imported entity must be instance of OroCRM\Bundle\ZendeskBundle\Entity\User,
     * stdClass given.
     */
    public function testProcessInvalidArgumentFails()
    {
        $this->processor->process(new \stdClass());
    }

    public function testProcessNewZendeskUser()
    {
        $zendeskUser = $this->createZendeskUser()->setOriginId(1);

        $this->assertEquals($zendeskUser, $this->processor->process($zendeskUser));
        $this->assertFalse($this->registry->getManager()->contains($zendeskUser));
    }

    public function testProcessExistingZendeskUser()
    {
        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1015)
            ->setUrl('https://foo.zendesk.com/api/v2/users/1015.json?1')
            ->setName('John Doe')
            ->setEmail('john.doe@example.com')
            ->setRole(new ZendeskUserRole(ZendeskUserRole::ROLE_AGENT))
            ->setPhone('555-111-222')
            ->setActive(true)
            ->setAlias('johndoe')
            ->setDetails('Some details')
            ->setExternalId(115)
            ->setOriginCreatedAt(new \DateTime('2014-06-10T12:12:21Z'))
            ->setOriginUpdatedAt(new \DateTime('2014-06-10T17:45:22Z'))
            ->setLastLoginAt(new \DateTime('2014-06-11T15:26:11Z'))
            ->setOnlyPrivateComments(true)
            ->setTicketRestriction('ticket_restriction')
            ->setVerified(true)
            ->setTimeZone('Arizona')
            ->setLocale('en-US')
            ->setChannel($this->channel);

        $result = $this->processor->process($zendeskUser);

        $this->assertNotSame($zendeskUser, $result);
        $this->assertNotNull($result->getId());
        $this->assertEquals($zendeskUser->getOriginId(), $result->getOriginId());
        $this->assertEquals($zendeskUser->getName(), $result->getName());
        $this->assertEquals($zendeskUser->getEmail(), $result->getEmail());
        $this->assertEquals($zendeskUser->getRole(), $result->getRole());
        $this->assertEquals($zendeskUser->getPhone(), $result->getPhone());
        $this->assertEquals($zendeskUser->getActive(), $result->getActive());
        $this->assertEquals($zendeskUser->getAlias(), $result->getAlias());
        $this->assertEquals($zendeskUser->getDetails(), $result->getDetails());
        $this->assertEquals($zendeskUser->getExternalId(), $result->getExternalId());
        $this->assertEquals($zendeskUser->getOriginCreatedAt(), $result->getOriginCreatedAt());
        $this->assertEquals($zendeskUser->getOriginUpdatedAt(), $result->getOriginUpdatedAt());
        $this->assertEquals($zendeskUser->getLastLoginAt(), $result->getLastLoginAt());
        $this->assertEquals($zendeskUser->getOnlyPrivateComments(), $result->getOnlyPrivateComments());
        $this->assertEquals($zendeskUser->getTicketRestriction(), $result->getTicketRestriction());
        $this->assertEquals($zendeskUser->getVerified(), $result->getVerified());
        $this->assertEquals($zendeskUser->getTimeZone(), $result->getTimeZone());
        $this->assertEquals($zendeskUser->getLocale(), $result->getLocale());
        $this->assertFalse($this->registry->getManager()->contains($zendeskUser));
        $this->assertTrue($this->registry->getManager()->contains($result));
    }

    public function testProcessSkipSyncExistingZendeskUserIfItAlreadyUpdated()
    {
        $existingUser = $this->getReference('zendesk_user:fred.taylor@example.com');

        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1015)
            ->setUrl('https://foo.zendesk.com/api/v2/users/1015.json?1')
            ->setName('John Doe')
            ->setEmail('john.doe@example.com')
            ->setRole(new ZendeskUserRole(ZendeskUserRole::ROLE_AGENT))
            ->setPhone('555-111-222')
            ->setActive(true)
            ->setAlias('johndoe')
            ->setDetails('Some details')
            ->setExternalId(115)
            ->setOriginCreatedAt(new \DateTime('2014-06-10T12:12:21Z'))
            ->setOriginUpdatedAt($existingUser->getOriginUpdatedAt())
            ->setLastLoginAt(new \DateTime('2014-06-11T15:26:11Z'))
            ->setOnlyPrivateComments(true)
            ->setTicketRestriction('ticket_restriction')
            ->setVerified(true)
            ->setTimeZone('Arizona')
            ->setLocale('en-US');

        $result = $this->processor->process($zendeskUser);

        $this->assertNull($result);
    }

    public function testProcessLinksZendeskUserRole()
    {
        $roleName = ZendeskUserRole::ROLE_AGENT;

        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1)
            ->setChannel($this->getReference('zendesk_channel:first_test_channel'))
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->processor->process($zendeskUser));

        $this->assertInstanceOf('OroCRM\\Bundle\\ZendeskBundle\\Entity\\UserRole', $zendeskUser->getRole());
        $this->assertEquals($roleName, $zendeskUser->getRole()->getName());
        $this->assertTrue($this->registry->getManager()->contains($zendeskUser->getRole()));
    }

    /**
     * @dataProvider userCompatibleRoleDataProvider
     */
    public function testProcessLinksRelatedUser($roleName)
    {
        $email = 'bob.miller@example.com';

        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1)
            ->setEmail($email)
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->processor->process($zendeskUser));

        $this->assertInstanceOf('Oro\\Bundle\\UserBundle\\Entity\\User', $zendeskUser->getRelatedUser());
        $this->assertEquals($email, $zendeskUser->getRelatedUser()->getEmail());
        $this->assertTrue($this->registry->getManager()->contains($zendeskUser->getRelatedUser()));
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
        $email = 'bob.miller@example.com';

        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1)
            ->setEmail($email);

        $this->assertEquals($zendeskUser, $this->processor->process($zendeskUser));
        $this->assertNull($zendeskUser->getRelatedUser());
    }

    public function testProcessLinksRelatedContact()
    {
        $roleName = ZendeskUserRole::ROLE_END_USER;
        $email    = 'mike.johnson@example.com';

        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1)
            ->setEmail($email)
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->processor->process($zendeskUser));

        $relatedContact = $zendeskUser->getRelatedContact();
        $this->assertInstanceOf('OroCRM\\Bundle\\ContactBundle\\Entity\\Contact', $relatedContact);
        $this->assertEquals($email, $relatedContact->getPrimaryEmail());
        $this->assertTrue($this->registry->getManager()->contains($relatedContact));
    }

    public function testProcessCreatesRelatedContact()
    {
        $roleName = ZendeskUserRole::ROLE_END_USER;
        $email    = 'bob.miller@example.com';

        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1)
            ->setEmail($email)
            ->setName('Mr. Bob Miller Jr.')
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->processor->process($zendeskUser));

        $relatedContact = $zendeskUser->getRelatedContact();
        $this->assertInstanceOf('OroCRM\\Bundle\\ContactBundle\\Entity\\Contact', $relatedContact);
        $this->assertFalse($this->registry->getManager()->contains($relatedContact));
        $this->assertEquals($email, $relatedContact->getPrimaryEmail());
        $this->assertEquals('Bob', $relatedContact->getFirstName());
        $this->assertEquals('Miller', $relatedContact->getLastName());
        $this->assertEquals('Mr.', $relatedContact->getNamePrefix());
        $this->assertEquals('Jr.', $relatedContact->getNameSuffix());
    }

    protected function createZendeskUser()
    {
        return new ZendeskUser();
    }
}
