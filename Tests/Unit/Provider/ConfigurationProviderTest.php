<?php

namespace Unit\Provider;

use OroCRM\Bundle\ZendeskBundle\Provider\ConfigurationProvider;

class ConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationProvider
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationManager;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configurationManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->target = new ConfigurationProvider($this->entityManager, $this->configurationManager);
    }

    public function testGetEmail()
    {
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::EMAIL_FIELD_NAME);
        $this->target->getEmail();
    }

    public function testGetApiToken()
    {
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::API_TOKEN_FIELD_NAME);
        $this->target->getApiToken();
    }

    public function testGetSyncTimeout()
    {
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::SYNC_TIMEOUT_FIELD_NAME);
        $this->target->getSyncTimeOut();
    }

    public function testGetZendescDefaultUser()
    {
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::ZENDESK_DEFAULT_USER_FIELD_NAME);
        $this->target->getZendeskDefaultUser();
    }

    public function testGetDefaultUser()
    {
        $username = 'username';
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::OROCRM_DEFAULT_USER_FIELD_NAME)
            ->will($this->returnValue($username));
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(array('username' => $username));
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($repository));
        $this->target->getDefaultUser();
    }
}
