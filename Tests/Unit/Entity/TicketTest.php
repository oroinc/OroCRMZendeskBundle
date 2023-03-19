<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CaseBundle\Entity\CaseEntity;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;
use Oro\Bundle\ZendeskBundle\Entity\User;

class TicketTest extends \PHPUnit\Framework\TestCase
{
    private Ticket $target;

    protected function setUp(): void
    {
        $this->target = new Ticket();
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $method = 'set' . ucfirst($property);
        $result = $this->target->$method($value);

        $this->assertInstanceOf(get_class($this->target), $result);
        $this->assertEquals($value, $this->target->{'get' . $property}());
    }

    public function testSetUpdatedAtLockedUpdateByLifeCycleCallback()
    {
        $expected = date_create_from_format('Y-m-d', '2012-10-10');
        $this->target->setUpdatedAt($expected);
        $this->target->preUpdate();
        $this->assertSame($expected, $this->target->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $this->assertNull($this->target->getCreatedAt());
        $this->assertNull($this->target->getUpdatedAt());

        $this->target->prePersist();

        $this->assertInstanceOf(\DateTime::class, $this->target->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $this->target->getUpdatedAt());

        $expectedCreated = $this->target->getCreatedAt();
        $expectedUpdated = $this->target->getUpdatedAt();

        $this->target->prePersist();

        $this->assertSame($expectedCreated, $this->target->getCreatedAt());
        $this->assertSame($expectedUpdated, $this->target->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->target->getUpdatedAt());
        $this->target->preUpdate();
        $this->assertInstanceOf(\DateTime::class, $this->target->getUpdatedAt());
    }

    public function settersAndGettersDataProvider(): array
    {
        $zendeskUser = $this->createMock(User::class);
        $ticketType = $this->createMock(TicketType::class);
        $ticketStatus = $this->createMock(TicketStatus::class);
        $ticketPriority = $this->createMock(TicketPriority::class);
        $case = $this->createMock(CaseEntity::class);
        $comment = $this->createMock(TicketComment::class);
        $ticket = new Ticket();
        $collaborators = new ArrayCollection([$zendeskUser]);
        $comments = new ArrayCollection([$comment]);

        $channel = $this->createMock(Channel::class);

        return [
            ['originId', 123456789],
            ['url', 'test.com'],
            ['subject', 'test subject'],
            ['description', 'test description'],
            ['recipient', 'test@mail.com'],
            ['type', $ticketType],
            ['status', $ticketStatus],
            ['priority', $ticketPriority],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['originCreatedAt', new \DateTime()],
            ['originUpdatedAt', new \DateTime()],
            ['dueAt', new \DateTime()],
            ['requester', $zendeskUser],
            ['assignee', $zendeskUser],
            ['submitter', $zendeskUser],
            ['relatedCase', $case],
            ['externalId', 'test_external_id'],
            ['problem', $ticket],
            ['channel', $channel],
            ['collaborators', $collaborators],
            ['hasIncidents', true],
            ['comments', $comments],
        ];
    }
}
