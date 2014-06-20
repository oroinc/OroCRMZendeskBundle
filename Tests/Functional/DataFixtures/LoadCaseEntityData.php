<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\Persistence\ObjectManager;

class LoadCaseEntityData extends AbstractZendeskFixture implements ContainerAwareInterface
{
    /**
     * @var array
     */
    protected static $data = array(
        array(
            'subject'       => 'Case #1',
            'description'   => 'Case #1: Description',
            'reference'     => 'orocrm_zendesk_case_1'
        ),
        array(
            'subject'       => 'Case #2',
            'description'   => 'Case #2: Description',
            'reference'     => 'orocrm_zendesk_case_2'
        ),
        array(
            'subject'       => 'Case #3',
            'description'   => 'Case #3: Description',
            'reference'     => 'orocrm_zendesk_case_3'
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $caseManager = $this->container->get('orocrm_case.manager');

        foreach (static::$data as $data) {
            $entity = $caseManager->createCase();

            if (isset($data['reference'])) {
                $this->setReference($data['reference'], $entity);
            }

            $this->setEntityPropertyValues($entity, $data, array('reference'));

            $manager->persist($entity);
        }

        $manager->flush();
    }

    public static function getData()
    {
        return self::$data;
    }
}
