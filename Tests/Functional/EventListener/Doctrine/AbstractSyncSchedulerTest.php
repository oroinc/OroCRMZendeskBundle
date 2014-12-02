<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\EventListener\Doctrine;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractSyncSchedulerTest extends WebTestCase
{
    /**
     * @param User $user
     */
    protected function setSecurityContextTokenByUser(User $user)
    {
        $securityContext = $this->getContainer()->get('security.context');
        $token = new UsernamePasswordToken($user, $user->getUsername(), 'main');
        $securityContext->setToken($token);
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
