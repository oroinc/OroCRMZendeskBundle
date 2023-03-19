<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\User;

class TicketCommentNormalizerTest extends WebTestCase
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
    public function testDenormalize($data, TicketComment $expected, $context = [])
    {
        $this->markTestSkipped('CRM-8206');

        $actual = $this->serializer->deserialize($data, TicketComment::class, '', $context);

        $this->assertEquals($expected, $actual);
    }

    public function denormalizeProvider(): array
    {
        return [
            'full' => [
                'data' => [
                    'id' => $originId = 100,
                    'author_id' => $authorId = 105,
                    'body' => $body = 'Body',
                    'html_body' => $htmlBody = '<p>Body</p>',
                    'public' => $public = true,
                    'ticket_id' => $ticketId = 202,
                    'created_at' => $createdAt = '2014-06-12T11:45:21Z',
                ],
                'expected' => $this->createTicketComment()
                    ->setOriginId($originId)
                    ->setAuthor($this->createUser($authorId))
                    ->setBody($body)
                    ->setHtmlBody($htmlBody)
                    ->setPublic($public)
                    ->setTicket($this->createTicket($ticketId))
                    ->setOriginCreatedAt(new \DateTime($createdAt))
            ],
            'with ticket_id' => [
                'data' => [
                    'id' => $originId = 100,
                    'author_id' => $authorId = 105,
                    'body' => $body = 'Body',
                    'html_body' => $htmlBody = '<p>Body</p>',
                    'public' => $public = true,
                    'created_at' => $createdAt = '2014-06-12T11:45:21Z',
                ],
                'expected' => $this->createTicketComment()
                    ->setOriginId($originId)
                    ->setAuthor($this->createUser($authorId))
                    ->setBody($body)
                    ->setHtmlBody($htmlBody)
                    ->setPublic($public)
                    ->setTicket($this->createTicket($ticketId = 202))
                    ->setOriginCreatedAt(new \DateTime($createdAt)),
                'context' => ['ticket_id' => $ticketId],
            ],
            'short' => [
                'data' => 100,
                'expected' => $this->createTicketComment()->setOriginId(100)
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
                'denormalized' => $this->createTicketComment()
                    ->setOriginId($originId = 100)
                    ->setAuthor($this->createUser($userId = 101))
                    ->setTicket($this->createTicket($ticketId = 103))
                    ->setBody($body = 'Body')
                    ->setHtmlBody('<p>Body</p>')
                    ->setPublic($public = true)
                    ->setCreatedAt(new \DateTime('2014-06-10T10:26:21+0000')),
                'normalized' => [
                    'id' => $originId,
                    'author_id' => $userId,
                    'ticket_id' => $ticketId,
                    'body' => $body,
                    'public' => $public,
                ],
            ],
        ];
    }

    private function createTicketComment(): TicketComment
    {
        return new TicketComment();
    }

    private function createTicket(int $originId): Ticket
    {
        $result = new Ticket();
        $result->setOriginId($originId);

        return $result;
    }

    private function createUser(int $originId): User
    {
        $result = new User();
        $result->setOriginId($originId);

        return $result;
    }
}
