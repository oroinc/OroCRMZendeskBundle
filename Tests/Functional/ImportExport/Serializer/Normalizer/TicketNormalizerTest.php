<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Serializer\Normalizer\TicketNormalizer;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class TicketNormalizerTest extends WebTestCase
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
        $this->serializer = $this->getContainer()->get('oro_importexport.serializer');
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize($normalized, Ticket $denormalized)
    {
        $actual = $this->serializer->deserialize(
            $normalized,
            'OroCRM\\Bundle\\ZendeskBundle\\Entity\\Ticket',
            null
        );

        $this->assertEquals($denormalized, $actual);
    }

    protected function setChannel(Ticket $entity)
    {
        $entity->setChannel($this->channel);
        if ($entity->getProblem()) {
            $entity->getProblem()->setChannel($this->channel);
        }
        if ($entity->getRequester()) {
            $entity->getRequester()->setChannel($this->channel);
        }
        if ($entity->getSubmitter()) {
            $entity->getSubmitter()->setChannel($this->channel);
        }
        if ($entity->getAssignee()) {
            $entity->getAssignee()->setChannel($this->channel);
        }
        foreach ($entity->getCollaborators() as $collaborator) {
            $collaborator->setChannel($this->channel);
        }
    }

    public function denormalizeDataProvider()
    {
        return array(
            'full' => array(
                'normalized' => array(
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
                ),
                'denormalized' => $this->createTicket()
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
            ),
            'short' => array(
                'data' => 100,
                'expected' => $this->createTicket()->setOriginId(100)
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
                'denormalized' => $this->createTicket()
                    ->setOriginId($originId = 100)
                    ->setExternalId($externalId = 123)
                    ->setSubject($subject = 'Ticket subject')
                    ->addCollaborator($this->createUser($collaboratorIds[] = 102))
                    ->addCollaborator($this->createUser($collaboratorIds[] = 103))
                    ->addCollaborator($this->createUser($collaboratorIds[] = 104))
                    ->setType(new TicketType($typeName = TicketType::TYPE_TASK))
                    ->setStatus(new TicketStatus($statusName = TicketStatus::STATUS_OPEN))
                    ->setPriority(new TicketPriority($priorityName = TicketPriority::PRIORITY_NORMAL))
                    ->setRequester($this->createUser($requesterId = 105))
                    ->setSubmitter($this->createUser($submitterId = 106))
                    ->setAssignee($this->createUser($assigneeId = 107))
                    ->setDueAt(new \DateTime($dueAt = '2014-06-10T10:26:21+0000')),
                'normalized' => array(
                    'id' => $originId,
                    'external_id' => $externalId,
                    'subject' => $subject,
                    'collaborator_ids' => $collaboratorIds,
                    'type' => $typeName,
                    'status' => $statusName,
                    'priority' => $priorityName,
                    'requester_id' => $requesterId,
                    'submitter_id' => $submitterId,
                    'assignee_id' => $assigneeId,
                    'due_at' => $dueAt,
                ),
                'context' => array(
                    'format' => \DateTime::ISO8601
                )
            ),
            'new' => array(
                'denormalized' => $this->createTicket()
                    ->setDescription($description = 'Ticket description'),
                'normalized' => array(
                    'comment' => array(
                        'body' => $description,
                    ),
                    'external_id' => null,
                    'subject' => null,
                    'collaborator_ids' => array(),
                    'type' => null,
                    'status' => null,
                    'priority' => null,
                    'requester_id' => null,
                    'submitter_id' => null,
                    'assignee_id' => null,
                    'due_at' => null,
                ),
            ),
            'short' => array(
                'denormalized' => $this->createTicket()->setOriginId($originId = 100),
                'normalized' => $originId,
                'context' => array('mode' => TicketNormalizer::SHORT_MODE),
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
     * @return User
     */
    protected function createUser($id)
    {
        $result = new User();
        $result->setOriginId($id);
        return $result;
    }
}
