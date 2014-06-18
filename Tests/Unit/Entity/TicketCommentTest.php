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

        return array(
            array('body', 'test message'),
            array('htmlBody', '<strong>test message</strong>'),
            array('public', true),
            array('author', $zendeskUser),
            array('ticket', $ticket),
            array('createdAt', new \DateTime()),
            array('caseComment', $comment)
        );
    }
}
