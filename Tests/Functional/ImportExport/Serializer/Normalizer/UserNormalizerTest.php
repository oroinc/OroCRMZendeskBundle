<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\User;
use Oro\Bundle\ZendeskBundle\Entity\UserRole;
use Oro\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer\UserNormalizer;

class UserNormalizerTest extends WebTestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->initClient();
        $this->serializer = $this->getContainer()->get('oro_importexport.serializer');
    }

    /**
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize($data, User $expected)
    {
        $this->markTestSkipped('CRM-8206');

        $actual = $this->serializer->deserialize($data, User::class, '');

        $this->assertEquals($expected, $actual);
    }

    public function denormalizeProvider(): array
    {
        return [
            'full' => [
                'data' => [
                    'id' => 1,
                    'url' => $url = 'https://foo.zendesk.com/api/v2/users/123.json',
                    'external_id' => $externalId = 123,
                    'name' => $name = 'Jane Doe',
                    'details' => $details = 'Details',
                    'ticket_restriction' => $ticketRestriction = 'Organization',
                    'only_private_comments' => $onlyPrivateComments = true,
                    'notes' => $notes = 'Notes',
                    'verified' => $verified = true,
                    'active' => $active = true,
                    'alias' => $alias = 'J. Doe',
                    'email' => $email = 'j.doe@example.com',
                    'phone' => $phone = '555 666 777',
                    'time_zone' => $timeZone = 'Arizona',
                    'locale' => $locale = 'en-US',
                    'created_at' => $createdAt = '2014-06-10T10:26:21Z',
                    'updated_at' => $updatedAt = '2014-06-12T11:45:21Z',
                    'role' => $roleName = 'agent',
                ],
                'expected' => $this->createUser()
                    ->setOriginId(1)
                    ->setUrl($url)
                    ->setExternalId($externalId)
                    ->setName($name)
                    ->setDetails($details)
                    ->setTicketRestriction($ticketRestriction)
                    ->setOnlyPrivateComments($onlyPrivateComments)
                    ->setNotes($notes)
                    ->setVerified($verified)
                    ->setActive($active)
                    ->setAlias($alias)
                    ->setEmail($email)
                    ->setPhone($phone)
                    ->setTimeZone($timeZone)
                    ->setLocale($locale)
                    ->setOriginCreatedAt(new \DateTime($createdAt))
                    ->setOriginUpdatedAt(new \DateTime($updatedAt))
                    ->setRole(new UserRole($roleName))
            ],
            'short' => [
                'data' => 100,
                'expected' => $this->createUser()->setOriginId(100)
            ],
        ];
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize($denormalized, $normalized, $context = [])
    {
        $actual = $this->serializer->normalize($denormalized, '', $context);

        $this->assertEquals($normalized, $actual);
    }

    public function normalizeDataProvider(): array
    {
        return [
            'full' => [
                'denormalized' => $this->createUser()
                        ->setOriginId($originId = 100)
                        ->setExternalId($externalId = 123)
                        ->setName($name = 'Jane Doe')
                        ->setDetails($details = 'Details')
                        ->setTicketRestriction($ticketRestriction = 'Organization')
                        ->setOnlyPrivateComments($onlyPrivateComments = true)
                        ->setNotes($notes = 'Notes')
                        ->setActive($active = true)
                        ->setAlias($alias = 'J. Doe')
                        ->setEmail($email = 'j.doe@example.com')
                        ->setPhone($phone = '555 666 777')
                        ->setTimeZone($timeZone = 'Arizona')
                        ->setLocale($locale = 'en-US')
                        ->setRole(new UserRole($roleName = 'agent')),
                'normalized' => [
                    'id' => $originId,
                    'external_id' => $externalId,
                    'name' => $name,
                    'details' => $details,
                    'ticket_restriction' => $ticketRestriction,
                    'only_private_comments' => $onlyPrivateComments,
                    'notes' => $notes,
                    'active' => $active,
                    'alias' => $alias,
                    'email' => $email,
                    'phone' => $phone,
                    'time_zone' => $timeZone,
                    'locale' => $locale,
                    'role' => $roleName,
                ],
            ],
            'short' => [
                'denormalized' => $this->createUser()->setOriginId($originId = 100),
                'normalized' => $originId,
                'context' => ['mode' => UserNormalizer::SHORT_MODE],
            ],
        ];
    }

    private function createUser(): User
    {
        return new User();
    }
}
