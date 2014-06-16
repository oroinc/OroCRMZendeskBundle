<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;

class Mapper
{
    const STATUS_BRANCH = 'status_map';
    const PRIORITY_BRANCH = 'priority_map';

    const CASE_VALUE_KEY = 'case';
    const TICKET_VALUE_KEY = 'ticket';

    /**
     * @var array
     */
    protected $map;

    /**
     * @param $map
     */
    public function __construct(array $map)
    {
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
     * @param $ticketStatus
     * @return null|string
     */
    public function getCaseStatus($ticketStatus)
    {
        $statusMap = $this->map[static::STATUS_BRANCH];
        return $this->getMappedValue($statusMap, $ticketStatus, static::TICKET_VALUE_KEY, static::CASE_VALUE_KEY);
    }

    /**
     * @param $ticketPriority
     * @return null|string
     */
    public function getCasePriority($ticketPriority)
    {
        $priorityMap = $this->map[static::PRIORITY_BRANCH];
        return $this->getMappedValue($priorityMap, $ticketPriority, static::TICKET_VALUE_KEY, static::CASE_VALUE_KEY);
    }

    /**
     * @param $caseStatus
     * @return null|string
     */
    public function getTicketStatus($caseStatus)
    {
        $statusMap = $this->map[static::STATUS_BRANCH];
        return $this->getMappedValue($statusMap, $caseStatus, static::CASE_VALUE_KEY, static::TICKET_VALUE_KEY);
    }

    /**
     * @param $casePriority
     * @return null|string
     */
    public function getTicketPriority($casePriority)
    {
        $priorityMap = $this->map[static::PRIORITY_BRANCH];
        return $this->getMappedValue($priorityMap, $casePriority, static::CASE_VALUE_KEY, static::TICKET_VALUE_KEY);
    }

    /**
     * Search for first occur in map
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
}
