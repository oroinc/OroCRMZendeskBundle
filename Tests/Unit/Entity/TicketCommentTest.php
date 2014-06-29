<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Entity;

use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;

class TicketCommentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TicketComment
     */
    protected $target;

    public function setUp()
    {
        $this->target = new TicketComment();
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters($property, $value)
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

        $this->assertInstanceOf('\DateTime', $this->target->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $this->target->getUpdatedAt());

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
        $this->assertInstanceOf('\DateTime', $this->target->getUpdatedAt());
    }

    /**
     * @return array
     */
    public function settersAndGettersDataProvider()
    {
        $zendeskUser = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $ticket = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\Ticket')
            ->disableOriginalConstructor()
            ->getMock();
        $comment = $this->getMockBuilder('OroCRM\Bundle\CaseBundle\Entity\CaseComment')
            ->disableOriginalConstructor()
            ->getMock();

        $channel = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        return array(
            array('originId', 123456789),
            array('body', 'test message'),
            array('htmlBody', '<strong>test message</strong>'),
            array('public', true),
            array('author', $zendeskUser),
            array('ticket', $ticket),
            array('createdAt', new \DateTime()),
            array('originCreatedAt', new \DateTime()),
            array('updatedAt', new \DateTime()),
            array('channel', $channel),
            array('relatedComment', $comment)
        );
    }
}
