<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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

    /**
     * @var Channel
     */
    protected $channel;

    protected function setUp()
    {
        $this->initClient();
        $fixtures = array('OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadSyncStatusData');
        $this->loadFixtures($fixtures);
        $this->channel = $this->getReference('zendesk_channel:first_test_channel');
        $this->serializer = $this->getContainer()->get('oro_importexport.serializer');
    }

    /**
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize($data, TicketComment $expected)
    {

        $actual = $this->serializer->deserialize(
            $data,
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketComment',
            null,
            array('channel' => $this->channel->getId())
        );
        $expected->setChannel($this->channel);
        if ($expected->getAuthor()) {
            $expected->getAuthor()->setChannel($this->channel);
        }
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
                    ->setOriginCreatedAt(new \DateTime($createdAt))
            ),
            'short' => array(
                'data' => 100,
                'expected' => $this->createTicketComment()->setOriginId(100)
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
     * @return User
     */
    protected function createUser($id)
    {
        $result = new User();
        $result->setOriginId($id);
        return $result;
    }
}
