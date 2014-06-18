<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider;

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
        $expected = 'admin@example.com';
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::EMAIL_FIELD_NAME)
            ->will($this->returnValue($expected));
        $email = $this->target->getEmail();
        $this->assertEquals($email, $expected);
    }

    public function testGetApiToken()
    {
        $expected = md5('api_token');
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::API_TOKEN_FIELD_NAME)
            ->will($this->returnValue($expected));
        $actual = $this->target->getApiToken();
        $this->assertEquals($expected, $actual);
    }

    public function testGetSyncTimeout()
    {
        $expected = 42;
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::SYNC_TIMEOUT_FIELD_NAME)
            ->will($this->returnValue($expected));
        $actual = $this->target->getSyncTimeOut();
        $this->assertEquals($expected, $actual);
    }

    public function testGetZendeskDefaultUser()
    {
        $expected = 'Alex Smith';
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::ZENDESK_DEFAULT_USER_EMAIL_FIELD_NAME)
            ->will($this->returnValue($expected));
        $actual = $this->target->getZendeskDefaultUserEmail();
        $this->assertEquals($expected, $actual);
    }

    public function testGetDefaultUser()
    {
        $username = 'username';
        $expects = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::OROCRM_DEFAULT_USERNAME_FIELD_NAME)
            ->will($this->returnValue($username));
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(array('username' => $username))
            ->will($this->returnValue($expects));
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($repository));
        $actual = $this->target->getDefaultUser();
        $this->assertEquals($expects, $actual);
    }
}
