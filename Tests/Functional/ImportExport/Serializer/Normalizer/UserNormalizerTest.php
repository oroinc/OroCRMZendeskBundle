<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class UserNormalizerTest extends WebTestCase
{
    /**
     * @var Serializer
     */
    protected $serializer;

    protected function setUp()
    {
        $this->initClient();
        $fixtures = array('OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadSyncStatusData');
        $this->loadFixtures($fixtures);
        $this->serializer = $this->getContainer()->get('oro_importexport.serializer');
    }

    /**
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize($data, User $expected)
    {
        $channel = $this->getReference('zendesk_channel:test@mail.com');
        $actual = $this->serializer->deserialize(
            $data,
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
            null,
            array('channel' => $channel->getId())
        );

        $expected->setChannel($channel);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider denormalizeProvider
     * @expectedException \LogicException
     * @expectedExceptionMessage Context should contain reference to channel
     */
    public function testLogicException($data)
    {
        $this->serializer->deserialize(
            $data,
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
            null,
            array()
        );
    }

    public function denormalizeProvider()
    {
        return array(
            'full' => array(
                'data' => array(
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
                    'role' => 'agent',
                ),
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
                    ->setRole(new UserRole('agent'))
            ),
            'short' => array(
                'data' => 100,
                'expected' => $this->createUser()->setOriginId(100)
            ),
        );
    }

    /**
     * @return User
     */
    protected function createUser()
    {
        return new User();
    }
}
