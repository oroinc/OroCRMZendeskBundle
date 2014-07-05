<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Symfony\Component\Serializer\SerializerInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use OroCRM\Bundle\ZendeskBundle\Entity\UserRole;
use OroCRM\Bundle\ZendeskBundle\Entity\User;
use OroCRM\Bundle\ZendeskBundle\ImportExport\ImportExportLogger;
use OroCRM\Bundle\ZendeskBundle\Model\SyncHelper\UserSyncHelper;
use OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;

abstract class AbstractExportWriter implements
    ItemWriterInterface,
    StepExecutionAwareInterface,
    ContextAwareInterface,
    LoggerAwareInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var ZendeskTransportInterface
     */
    protected $transport;

    /**
     * @var ImportExportLogger
     */
    private $logger;

    /**
     * @var ConnectorContextMediator
     */
    protected $connectorContextMediator;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var UserSyncHelper
     */
    protected $userSyncHelper;

    /**
     * @var Channel
     */
    private $channel = null;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param ZendeskTransportInterface $transport
     */
    public function setTransport(ZendeskTransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @param UserSyncHelper $userSyncHelper
     */
    public function setUserSyncHelper(UserSyncHelper $userSyncHelper)
    {
        $this->userSyncHelper = $userSyncHelper;
    }

    /**
     * @param ContextRegistry $contextRegistry
     */
    public function setContextRegistry(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * @param array $entities
     * @throws \Exception
     */
    public function write(array $entities)
    {
        $this->transport->init($this->getChannel()->getTransport());

        try {
            $this->entityManager->beginTransaction();
            foreach ($entities as $entity) {
                $this->writeItem($entity);
            }
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
        $this->entityManager->flush();
        $this->postFlush();
        $this->entityManager->clear();
    }

    /**
     * Hook runs after entity manager flush and before clearing entity manager
     */
    protected function postFlush()
    {
    }

    /**
     * @param mixed $entity
     */
    abstract protected function writeItem($entity);

    /**
     * @return ContextInterface
     */
    protected function getContext()
    {
        return $this->context;
    }

    /**
     * @param ConnectorContextMediator $connectorContextMediator
     */
    public function setConnectorContextMediator(ConnectorContextMediator $connectorContextMediator)
    {
        $this->connectorContextMediator = $connectorContextMediator;
    }

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        if ($this->channel === null) {
            $this->channel = $this->connectorContextMediator->getChannel($this->context);
        }

        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->setImportExportContext($this->contextRegistry->getByStepExecution($stepExecution));
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @return ImportExportLogger
     */
    protected function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new ImportExportLogger(new NullLogger());
        }
        $this->logger->setImportExportContext($this->getContext());
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = new ImportExportLogger($logger);
    }

    /**
     * @param User $user
     */
    protected function createUser(User $user)
    {
        $this->getLogger()->info(sprintf('Create user in Zendesk API [id=%d].', $user->getId()));

        if (!$user->isRoleEqual(UserRole::ROLE_END_USER)) {
            $this->getLogger()->error("Not allowed to create user [role={$user->getRole()->getName()}] in Zendesk.");
            return;
        }

        try {
            $data = $this->transport->createUser($this->serializer->serialize($user, null));

            $createdUser = $this->serializer->deserialize(
                $data,
                'OroCRM\\Bundle\\ZendeskBundle\\Entity\\User',
                null,
                ['channel' => $this->getChannel()->getId()]
            );

            $this->getLogger()->info("Created user [origin_id={$createdUser->getOriginId()}].");
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                "Can't create user [id={$user->getId()}] in Zendesk API.",
                ['exception' => $exception]
            );
            return;
        }

        $user->setChannel($this->getChannel());
        $this->entityManager->persist($user);

        $this->userSyncHelper->refreshEntity($createdUser, $this->getChannel());
        $this->userSyncHelper->copyEntityProperties($user, $createdUser);

        $this->userSyncHelper->rememberUser($user, $this->getChannel());
    }
}
