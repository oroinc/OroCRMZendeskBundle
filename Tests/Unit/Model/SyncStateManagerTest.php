<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Model;

use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskSyncState;
use OroCRM\Bundle\ZendeskBundle\Model\SyncStateManager;

class SyncStateManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SyncStateManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new SyncStateManager($this->entityManager);
    }

    public function testGetLastSyncDate()
    {
        $expected = new \DateTime();
        $entity = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\ZendeskSyncState')
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())
            ->method('getLastSync')
            ->will($this->returnValue($expected));
        $this->setupEntityManager($entity);
        $actual = $this->manager->getLastSyncDate();

        $this->assertEquals($expected, $actual);
    }

    public function testGetLastSyncDateCreateNewSyncStateIfSyncStateNotExist()
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with(ZendeskSyncState::STATE_ID)
            ->will($this->returnValue(null));
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));
        $actual = $this->manager->getLastSyncDate();

        $this->assertNull($actual);
    }

    public function testSetLastSyncDate()
    {
        $expected = new \DateTime();
        $entity = $this->getMockBuilder('OroCRM\Bundle\ZendeskBundle\Entity\ZendeskSyncState')
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())
            ->method('setLastSync')
            ->with($expected);
        $this->setupEntityManager($entity);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->manager->setLastSyncDate($expected, true);
    }

    /**
     * @param $entity
     */
    protected function setupEntityManager($entity)
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with(ZendeskSyncState::STATE_ID)
            ->will($this->returnValue($entity));
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));
    }
}
