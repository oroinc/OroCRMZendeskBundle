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

    public function testGetZendeskUrl()
    {
        $withSchema = 'https://company.zendesk.com';
        $withoutSchema = 'company.zendesk.com';

        $this->configurationManager->expects($this->at(0))
            ->method('get')
            ->with(ConfigurationProvider::ZENDESK_URL_FIELD_NAME)
            ->will($this->returnValue($withSchema));

        $this->configurationManager->expects($this->at(1))
            ->method('get')
            ->with(ConfigurationProvider::ZENDESK_URL_FIELD_NAME)
            ->will($this->returnValue($withoutSchema));

        $actual = $this->target->getZendeskUrl();
        $this->assertEquals($withSchema, $actual, 'failed if url full');
        $actual = $this->target->getZendeskUrl();

        $this->assertEquals($withSchema, $actual, 'failed if url partial');
    }

    public function testGetEmail()
    {
        $expected = 'admin@example.com';
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::API_EMAIL_FIELD_NAME)
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

    public function testGetCronSchedule()
    {
        $expected = 42;
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::CRON_SCHEDULE_FIELD_NAME)
            ->will($this->returnValue($expected));
        $actual = $this->target->getCronSchedule();
        $this->assertEquals($expected, $actual);
    }

    public function testGetZendeskDefaultUserEmail()
    {
        $expected = 'Alex Smith';
        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::ZENDESK_DEFAULT_USER_EMAIL_FIELD_NAME)
            ->will($this->returnValue($expected));
        $actual = $this->target->getZendeskDefaultUserEmail();
        $this->assertEquals($expected, $actual);
    }

    public function testGetOroDefaultUser()
    {
        $username = 'username';
        $expectedUser = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::ORO_DEFAULT_USERNAME_FIELD_NAME)
            ->will($this->returnValue($username));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(array('username' => $username))
            ->will($this->returnValue($expectedUser));

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($repository));

        $actual = $this->target->getOroDefaultUser();
        $this->assertEquals($expectedUser, $actual);
    }

    public function testGetZendeskDefaultUser()
    {
        $email = 'user@example.com';
        $expectedUser = $this->getMock('OroCRM\Bundle\ZendeskBundle\Entity\User');

        $this->configurationManager->expects($this->once())
            ->method('get')
            ->with(ConfigurationProvider::ZENDESK_DEFAULT_USER_EMAIL_FIELD_NAME)
            ->will($this->returnValue($email));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(array('email' => $email))
            ->will($this->returnValue($expectedUser));

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroCRMZendeskBundle:User')
            ->will($this->returnValue($repository));

        $actual = $this->target->getZendeskDefaultUser();
        $this->assertEquals($expectedUser, $actual);
    }
}
