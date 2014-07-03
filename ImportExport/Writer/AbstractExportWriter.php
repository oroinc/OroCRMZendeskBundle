<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Symfony\Component\Serializer\SerializerInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;

use OroCRM\Bundle\ZendeskBundle\ImportExport\ImportExportLogger;
use OroCRM\Bundle\ZendeskBundle\Provider\Transport\ZendeskTransportInterface;

abstract class AbstractExportWriter implements ItemWriterInterface, ContextAwareInterface, LoggerAwareInterface
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
     * @param array $entities
     * @throws \Exception
     */
    public function write(array $entities)
    {
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
}
