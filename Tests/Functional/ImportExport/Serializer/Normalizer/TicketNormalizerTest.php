<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use OroCRM\Bundle\ZendeskBundle\Entity\User;

class TicketNormalizerTest extends WebTestCase
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
    public function testDenormalize($data, $expected)
    {
        $actual = $this->serializer->deserialize($data, 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket', null);

        $this->assertEquals($expected, $actual);
    }

    public function denormalizeProvider()
    {
        return array(
            'full' => array(
                'data' => array(
                    'id' => $originId = 100,
                    'url' => $url = 'https://foo.zendesk.com/api/v2/tickets/123.json',
                    'external_id' => $externalId = 123,
                    'subject' => $subject = 'Ticket subject',
                    'description' => $description = 'Ticket description',
                    'problem_id' => $problemId = 101,
                    'collaborator_ids' => $collaboratorIds = [102, 103, 104],
                    'type' => $typeName = TicketType::TYPE_TASK,
                    'status' => $statusName = TicketStatus::STATUS_OPEN,
                    'priority' => $priorityName = TicketPriority::PRIORITY_NORMAL,
                    'recipient' => $recipient = 'recipient@user.com',
                    'requester_id' => $requesterId = 105,
                    'submitter_id' => $submitterId = 106,
                    'assignee_id' => $assigneeId = 107,
                    'has_incidents' => $hasIncidents = true,
                    'due_at' => $dueAt = '2014-06-10T10:26:21Z',
                    'created_at' => $createdAt = '2014-06-12T11:45:21Z',
                    'updated_at' => $updatedAt = '2014-06-13T09:57:54Z',
                    'comments' => array(
                        array(
                            'id' => $commentOriginId = 100,
                            'author_id' => $commentAuthorId = 105,
                            'body' => $commentBody = 'Body',
                            'html_body' => $commentHtmlBody = '<p>Body</p>',
                            'public' => $commentPublic = true,
                            'created_at' => $commentCreatedAt = '2014-06-12T11:45:21Z',
                        ),
                    ),
                ),
                'expected' => $this->createTicket()
                    ->setOriginId($originId)
                    ->setUrl($url)
                    ->setExternalId($externalId)
                    ->setSubject($subject)
                    ->setDescription($description)
                    ->setProblem($this->createTicket()->setOriginId($problemId))
                    ->addCollaborator($this->createUser($collaboratorIds[0]))
                    ->addCollaborator($this->createUser($collaboratorIds[1]))
                    ->addCollaborator($this->createUser($collaboratorIds[2]))
                    ->setType(new TicketType($typeName))
                    ->setStatus(new TicketStatus($statusName))
                    ->setPriority(new TicketPriority($priorityName))
                    ->setRecipient($recipient)
                    ->setRequester($this->createUser($requesterId))
                    ->setSubmitter($this->createUser($submitterId))
                    ->setAssignee($this->createUser($assigneeId))
                    ->setHasIncidents($hasIncidents)
                    ->setDueAt(new \DateTime($dueAt))
                    ->setOriginCreatedAt(new \DateTime($createdAt))
                    ->setOriginUpdatedAt(new \DateTime($updatedAt))
                    ->addComment(
                        $this->createTicketComment($commentOriginId)
                            ->setAuthor($this->createUser($commentAuthorId))
                            ->setBody($commentBody)
                            ->setHtmlBody($commentHtmlBody)
                            ->setPublic($commentPublic)
                            ->setOriginCreatedAt(new \DateTime($commentCreatedAt))
                    )
            ),
            'short' => array(
                'data' => 100,
                'expected' => $this->createTicket()->setOriginId(100)
            ),
        );
    }

    /**
     * @return Ticket
     */
    protected function createTicket()
    {
        $result = new Ticket();
        return $result;
    }

    /**
     * @param int $id
     * @return TicketComment
     */
    protected function createTicketComment($id)
    {
        $result = new TicketComment();
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
