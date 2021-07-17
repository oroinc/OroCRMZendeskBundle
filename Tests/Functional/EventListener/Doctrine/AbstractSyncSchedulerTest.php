<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\EventListener\Doctrine;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

abstract class AbstractSyncSchedulerTest extends WebTestCase
{
    protected function setSecurityContextTokenByUser(User $user)
    {
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $token = new UsernamePasswordToken($user, $user->getUsername(), 'main');
        $tokenStorage->setToken($token);
    }

    /**
     * Get administrator user entity
     *
     * @return User
     */
    protected function getAdminUser()
    {
        $result = $this->getContainer()->get('doctrine')
            ->getRepository('OroUserBundle:User')
            ->findOneByUsername(LoadAdminUserData::DEFAULT_ADMIN_USERNAME);

        $this->assertNotNull($result, 'Can\'t get admin user');

        return $result;
    }
}
