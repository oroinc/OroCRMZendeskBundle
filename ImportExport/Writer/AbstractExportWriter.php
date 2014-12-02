<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

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
     * @var ManagerRegistry
     */
    protected $registry;

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
    protected $userHelper;

    /**
     * @var Channel
     */
    private $channel = null;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
    public function setUserHelper(UserSyncHelper $userSyncHelper)
    {
        $this->userHelper = $userSyncHelper;
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
        /** @var EntityManager $em */
        $em = $this->registry->getManager();

        try {
            $em->beginTransaction();
            foreach ($entities as $entity) {
                $this->writeItem($entity);
            }
            $em->flush();
            $em->commit();

            $this->postFlush();
            $em->clear();
        } catch (\Exception $exception) {
            $em->rollback();

            throw $exception;
        }
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
            $createdUser = $this->transport->createUser($user);

            $this->getLogger()->info("Created user [origin_id={$createdUser->getOriginId()}].");
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                "Can't create user [id={$user->getId()}] in Zendesk API.",
                ['exception' => $exception]
            );
            return;
        }

        $user->setChannel($this->getChannel());
        $this->registry->getManager()->persist($user);

        $this->userHelper->refreshTicket($createdUser, $this->getChannel());
        $this->userHelper->copyEntityProperties($user, $createdUser);

        $this->userHelper->rememberUser($user, $this->getChannel());
    }
}
