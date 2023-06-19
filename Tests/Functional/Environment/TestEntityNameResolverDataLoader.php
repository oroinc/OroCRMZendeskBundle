<?php

namespace Oro\Bundle\ZendeskBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\ZendeskBundle\Entity\Ticket;
use Oro\Bundle\ZendeskBundle\Entity\TicketPriority;
use Oro\Bundle\ZendeskBundle\Entity\TicketStatus;
use Oro\Bundle\ZendeskBundle\Entity\TicketType;
use Oro\Bundle\ZendeskBundle\Entity\User;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (User::class === $entityClass) {
            $zendeskUser = new User();
            $zendeskUser->setName('Test Zendesk User');
            $zendeskUser->setLocale('es');
            $zendeskUser->setEmail('zendesk_user@example.com');
            $zendeskUser->setPhone('123-123');
            $repository->setReference('zendeskUser', $zendeskUser);
            $em->persist($zendeskUser);
            $em->flush();

            return ['zendeskUser'];
        }

        if (Ticket::class === $entityClass) {
            $zendeskTicket = new Ticket();
            $zendeskTicket->setPriority($em->find(TicketPriority::class, TicketPriority::PRIORITY_LOW));
            $zendeskTicket->setStatus($em->find(TicketStatus::class, TicketStatus::STATUS_NEW));
            $zendeskTicket->setType($em->find(TicketType::class, TicketType::TYPE_TASK));
            $zendeskTicket->setSubject('Test Zendesk Ticket');
            $zendeskTicket->setDescription('test description');
            $zendeskTicket->setUrl('http://example.com');
            $repository->setReference('zendeskTicket', $zendeskTicket);
            $em->persist($zendeskTicket);
            $em->flush();

            return ['zendeskTicket'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (User::class === $entityClass) {
            return 'Test Zendesk User';
        }
        if (Ticket::class === $entityClass) {
            return 'Test Zendesk Ticket';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }
}
