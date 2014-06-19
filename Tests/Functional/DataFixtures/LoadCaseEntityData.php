<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

class LoadCaseEntityData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    protected static $casesData = array(
        array(
            'subject'       => 'Case #1',
            'description'   => 'Case #1: Description',
            'reference'     => 'orocrm_zendesk_case'
        ),
        array(
            'subject'       => 'Case #2',
            'description'   => 'Case #2: Description'
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $caseManager = $this->container->get('orocrm_case.manager');

        $adminUser = $manager->getRepository('OroUserBundle:User')->findOneByUsername('admin');

        foreach (static::$casesData as $caseData) {
            $case = $caseManager->createCase()
                ->setSubject($caseData['subject'])
                ->setDescription($caseData['description'])
                ->setOwner($adminUser);

            $manager->persist($case);

            if (isset($caseData['reference'])) {
                $this->setReference($caseData['reference'], $case);
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public static function getCaseData()
    {
        return self::$casesData;
    }
}
