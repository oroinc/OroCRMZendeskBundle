<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Unit\Entity;

use Oro\Bundle\CaseBundle\Entity\CaseComment;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketComment;
use Oro\Bundle\ZendeskBundle\Entity\User;

class TicketCommentTest extends \PHPUnit\Framework\TestCase
{
    private TicketComment $target;

    protected function setUp(): void
    {
        $this->target = new TicketComment();
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
        $ticket = $this->createMock(Ticket::class);
        $comment = $this->createMock(CaseComment::class);
        $channel = $this->createMock(Channel::class);

        return [
            ['originId', 123456789],
            ['body', 'test message'],
            ['htmlBody', '<strong>test message</strong>'],
            ['public', true],
            ['author', $zendeskUser],
            ['ticket', $ticket],
            ['createdAt', new \DateTime()],
            ['originCreatedAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['channel', $channel],
            ['relatedComment', $comment]
        ];
    }
}
