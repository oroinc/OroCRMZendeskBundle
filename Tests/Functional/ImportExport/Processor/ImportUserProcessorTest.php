<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\User as ZendeskUser;
use Oro\Bundle\ZendeskBundle\Entity\UserRole as ZendeskUserRole;
use Oro\Bundle\ZendeskBundle\ImportExport\Processor\ImportUserProcessor;
use Oro\Bundle\ZendeskBundle\Tests\Functional\DataFixtures\LoadZendeskUserData;

class ImportUserProcessorTest extends WebTestCase
{
    private ManagerRegistry $registry;
    private Channel $channel;
    private ImportUserProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadZendeskUserData::class]);

        $this->registry = $this->getContainer()->get('doctrine');
        $this->processor = $this->getContainer()->get('oro_zendesk.importexport.processor.import_user');
        $this->channel = $this->getReference('zendesk_channel:first_test_channel');

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->any())
            ->method('getOption')
            ->with('channel', null)
            ->willReturn($this->channel->getId());

        $this->processor->setImportExportContext($context);
    }

    public function testProcessInvalidArgumentFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Imported entity must be instance of %s, stdClass given.',
            ZendeskUser::class
        ));

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

        $this->assertInstanceOf(ZendeskUserRole::class, $zendeskUser->getRole());
        $this->assertEquals($roleName, $zendeskUser->getRole()->getName());
        $this->assertTrue($this->registry->getManager()->contains($zendeskUser->getRole()));
    }

    /**
     * @dataProvider userCompatibleRoleDataProvider
     */
    public function testProcessLinksRelatedUser(string $roleName)
    {
        $email = 'bob.miller@example.com';

        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1)
            ->setEmail($email)
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->processor->process($zendeskUser));

        $this->assertInstanceOf(User::class, $zendeskUser->getRelatedUser());
        $this->assertEquals($email, $zendeskUser->getRelatedUser()->getEmail());
        $this->assertTrue($this->registry->getManager()->contains($zendeskUser->getRelatedUser()));
    }

    public function userCompatibleRoleDataProvider(): array
    {
        return [
            ZendeskUserRole::ROLE_AGENT => [ZendeskUserRole::ROLE_AGENT],
            ZendeskUserRole::ROLE_ADMIN => [ZendeskUserRole::ROLE_ADMIN],
        ];
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
        $email = 'mike.johnson@example.com';

        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1)
            ->setEmail($email)
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->processor->process($zendeskUser));

        $relatedContact = $zendeskUser->getRelatedContact();
        $this->assertInstanceOf(Contact::class, $relatedContact);
        $this->assertEquals($email, $relatedContact->getPrimaryEmail());
        $this->assertTrue($this->registry->getManager()->contains($relatedContact));
    }

    public function testProcessCreatesRelatedContact()
    {
        $roleName = ZendeskUserRole::ROLE_END_USER;
        $email = 'bob.miller@example.com';

        $zendeskUser = $this->createZendeskUser()
            ->setOriginId(1)
            ->setEmail($email)
            ->setName('Mr. Bob Miller Jr.')
            ->setRole(new ZendeskUserRole($roleName));

        $this->assertEquals($zendeskUser, $this->processor->process($zendeskUser));

        $relatedContact = $zendeskUser->getRelatedContact();
        $this->assertInstanceOf(Contact::class, $relatedContact);
        $this->assertFalse($this->registry->getManager()->contains($relatedContact));
        $this->assertEquals($email, $relatedContact->getPrimaryEmail());
        $this->assertEquals('Bob', $relatedContact->getFirstName());
        $this->assertEquals('Miller', $relatedContact->getLastName());
        $this->assertEquals('Mr.', $relatedContact->getNamePrefix());
        $this->assertEquals('Jr.', $relatedContact->getNameSuffix());
    }

    private function createZendeskUser(): ZendeskUser
    {
        return new ZendeskUser();
    }
}
