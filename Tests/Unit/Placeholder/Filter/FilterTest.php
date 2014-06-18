<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Placeholder\Filter;

use OroCRM\Bundle\ZendeskBundle\Placeholder\Filter\Filter;

class FilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new Filter($this->entityManager);
    }

    public function testFilter()
    {
        $entity = new \StdClass();
        $this->assertFalse($this->filter->filter($entity));
        $entity = $this->getMock('OroCRM\Bundle\CaseBundle\Entity\CaseEntity');
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->will($this->returnValue($repository));
        $this->assertFalse($this->filter->filter($entity));

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(array('case' => $entity))
            ->will($this->returnValue(true));

        $this->assertTrue($this->filter->filter($entity));
    }
}
