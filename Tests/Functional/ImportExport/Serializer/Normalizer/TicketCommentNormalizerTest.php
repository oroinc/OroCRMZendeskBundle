<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\User;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class TicketCommentNormalizerTest extends WebTestCase
{
    /**
     * @var Serializer
     */
    protected $serializer;

    protected function setUp()
    {
        $this->initClient();
        $this->serializer = $this->getContainer()->get('oro_importexport.serializer');
    }

    /**
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize($data, TicketComment $expected, $context = [])
    {
        $actual = $this->serializer->deserialize(
            $data,
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment',
            null,
            $context
        );

        $this->assertEquals($expected, $actual);
    }

    public function denormalizeProvider()
    {
        return array(
            'full' => array(
                'data' => array(
                    'id' => $originId = 100,
                    'author_id' => $authorId = 105,
                    'body' => $body = 'Body',
                    'html_body' => $htmlBody = '<p>Body</p>',
                    'public' => $public = true,
                    'ticket_id' => $ticketId = 202,
                    'created_at' => $createdAt = '2014-06-12T11:45:21Z',
                ),
                'expected' => $this->createTicketComment()
                    ->setOriginId($originId)
                    ->setAuthor($this->createUser($authorId))
                    ->setBody($body)
                    ->setHtmlBody($htmlBody)
                    ->setPublic($public)
                    ->setTicket($this->createTicket($ticketId))
                    ->setOriginCreatedAt(new \DateTime($createdAt))
            ),
            'with ticket_id' => array(
                'data' => array(
                    'id' => $originId = 100,
                    'author_id' => $authorId = 105,
                    'body' => $body = 'Body',
                    'html_body' => $htmlBody = '<p>Body</p>',
                    'public' => $public = true,
                    'created_at' => $createdAt = '2014-06-12T11:45:21Z',
                ),
                'expected' => $this->createTicketComment()
                    ->setOriginId($originId)
                    ->setAuthor($this->createUser($authorId))
                    ->setBody($body)
                    ->setHtmlBody($htmlBody)
                    ->setPublic($public)
                    ->setTicket($this->createTicket($ticketId = 202))
                    ->setOriginCreatedAt(new \DateTime($createdAt)),
                'context' => array('ticket_id' => $ticketId),
            ),
            'short' => array(
                'data' => 100,
                'expected' => $this->createTicketComment()->setOriginId(100)
            ),
        );
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize($denormalized, $normalized, $context = array())
    {
        $actual = $this->serializer->serialize($denormalized, null, $context);

        $this->assertEquals($normalized, $actual);
    }

    public function normalizeDataProvider()
    {
        return array(
            'full' => array(
                'denormalized' => $this->createTicketComment()
                    ->setOriginId($originId = 100)
                    ->setAuthor($this->createUser($userId = 101))
                    ->setTicket($this->createTicket($ticketId = 103))
                    ->setBody($body = 'Body')
                    ->setHtmlBody('<p>Body</p>')
                    ->setPublic($public = true)
                    ->setCreatedAt(new \DateTime('2014-06-10T10:26:21+0000')),
                'normalized' => array(
                    'id' => $originId,
                    'author_id' => $userId,
                    'ticket_id' => $ticketId,
                    'body' => $body,
                    'public' => $public,
                ),
            ),
        );
    }

    /**
     * @return TicketComment
     */
    protected function createTicketComment()
    {
        $result = new TicketComment();
        return $result;
    }

    /**
     * @param int $id
     * @return Ticket
     */
    protected function createTicket($id)
    {
        $result = new Ticket();
        $result->setOriginId($id);
        return $result;
    }

    /**
     * @param int $id
     * @return User
     */
    protected function createUser($id)
    {
        $result = new User();
        $result->setOriginId($id);
        return $result;
    }
}
