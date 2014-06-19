<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\Persistence\ObjectManager;

class LoadCaseEntityData extends AbstractZendeskFixture implements ContainerAwareInterface
{
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

        foreach (static::$casesData as $caseData) {
            $case = $caseManager->createCase()
                ->setSubject($caseData['subject'])
                ->setDescription($caseData['description']);

            $manager->persist($case);

            if (isset($caseData['reference'])) {
                $this->setReference($caseData['reference'], $case);
            }
        }

        $manager->flush();
    }

    public static function getCaseData()
    {
        return self::$casesData;
    }
}
