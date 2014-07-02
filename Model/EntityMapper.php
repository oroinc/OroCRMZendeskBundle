<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\CaseBundle\Entity\CasePriority;
use OroCRM\Bundle\CaseBundle\Entity\CaseStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;

class EntityMapper
{
    const STATUS_KEY = 'status';
    const PRIORITY_KEY = 'priority';
    const CASE_KEY = 'case';
    const TICKET_KEY = 'ticket';

    /**
     * @var array
     */
    protected $map;

    /**
     * @param EntityManager $entityManager
     * @param array $map
     */
    public function __construct(EntityManager $entityManager, array $map)
    {
        $this->entityManager = $entityManager;
        $this->map = $map;
    }

    /**
     * @return array
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * @param TicketStatus|string $ticketStatus
     * @param Channel             $channel
     * @return CaseStatus|null
     */
    public function getCaseStatus($ticketStatus, Channel $channel)
    {
        $name = $this->getCaseStatusName($ticketStatus, $channel);
        return $name ? $this->entityManager->find('OroCRMCaseBundle:CaseStatus', $name) : null;
    }

    /**
     * @param TicketStatus|string $ticketStatus
     * @param Channel             $channel
     * @return string|null
     */
    public function getCaseStatusName($ticketStatus, Channel $channel)
    {
        return $this->getTicket2CaseValue(
            $this->getEntityName($ticketStatus, 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketStatus'),
            self::STATUS_KEY
        );
    }

    /**
     * @param TicketPriority|string $ticketPriority
     * @param Channel               $channel
     * @return CaseStatus|null
     */
    public function getCasePriority($ticketPriority, Channel $channel)
    {
        $name = $this->getCasePriorityName($ticketPriority, $channel);
        return $name ? $this->entityManager->find('OroCRMCaseBundle:CasePriority', $name) : null;
    }

    /**
     * @param TicketPriority|string $ticketPriority
     * @param Channel               $channel
     * @return string|null
     */
    public function getCasePriorityName($ticketPriority, Channel $channel)
    {
        return $this->getTicket2CaseValue(
            $this->getEntityName($ticketPriority, 'OroCRM\\Bundle\\ZendeskBundle\\Entity\\TicketPriority'),
            self::PRIORITY_KEY
        );
    }

    /**
     * @param CaseStatus|string $caseStatus
     * @param Channel           $channel
     * @return TicketStatus|null
     */
    public function getTicketStatus($caseStatus, Channel $channel)
    {
        $name = $this->getTicketStatusName($caseStatus, $channel);
        return $name ? $this->entityManager->find('OroCRMZendeskBundle:TicketStatus', $name) : null;
    }

    /**
     * @param CaseStatus|string $caseStatus
     * @param Channel           $channel
     * @return null|string
     */
    public function getTicketStatusName($caseStatus, Channel $channel)
    {
        return $this->getCase2TicketValue(
            $this->getEntityName($caseStatus, 'OroCRM\\Bundle\\CaseBundle\\Entity\\CaseStatus'),
            self::STATUS_KEY
        );
    }

    /**
     * @param CasePriority|string $casePriority
     * @param Channel             $channel
     * @return TicketPriority|null
     */
    public function getTicketPriority($casePriority, Channel $channel)
    {
        $name = $this->getTicketPriorityName($casePriority, $channel);
        return $name ? $this->entityManager->find('OroCRMZendeskBundle:TicketPriority', $name) : null;
    }

    /**
     * @param CasePriority|string $casePriority
     * @param Channel             $channel
     * @return null|string
     */
    public function getTicketPriorityName($casePriority, Channel $channel)
    {
        return $this->getCase2TicketValue(
            $this->getEntityName($casePriority, 'OroCRM\\Bundle\\CaseBundle\\Entity\\CasePriority'),
            self::PRIORITY_KEY
        );
    }

    /**
     * Search mapped value from ticket to case
     *
     * @param string $value
     * @param string $key
     * @return null|string
     */
    protected function getTicket2CaseValue($value, $key)
    {
        return $this->getMappedValue($this->map[$key], $value, self::TICKET_KEY, self::CASE_KEY);
    }

    /**
     * Search mapped value from case to ticket
     *
     * @param string $value
     * @param string $key
     * @return null|string
     */
    protected function getCase2TicketValue($value, $key)
    {
        return $this->getMappedValue($this->map[$key], $value, self::CASE_KEY, self::TICKET_KEY);
    }

    /**
     * Search for first occurrence in map
     *
     * @param array  $map
     * @param string $value
     * @param string $from
     * @param string $to
     * @return null|string
     */
    protected function getMappedValue($map, $value, $from, $to)
    {
        foreach ($map as $pair) {
            if ($pair[$from] == $value) {
                return $pair[$to];
            }
        }

        return null;
    }

    /**
     * @param object|string $stringOrObject
     * @param string $entityClass
     * @return string|null
     */
    protected function getEntityName($stringOrObject, $entityClass)
    {
        $result = null;
        if ($stringOrObject instanceof $entityClass) {
            $result = $stringOrObject->getName();
        } elseif (is_string($stringOrObject)) {
            $result = $stringOrObject;
        } else {
            $result = null;
        }

        return $result;
    }
}
