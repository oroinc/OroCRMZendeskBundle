<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Reader;

use OroCRM\Bundle\ZendeskBundle\Model\RestClient;
use OroCRM\Bundle\ZendeskBundle\Model\RestIterator;

class TicketReader extends ZendeskAPIReader
{
    /**
     * @var RestClient
     */
    protected $client = null;

    /**
     * @var string
     */
    protected $commentsActionPattern = 'tickets/%s/comments.json';

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $data = parent::read();

        $this->initializeClient();

        if ($data && isset($data['id'])) {
            $data['comments'] = $this->readTicketComments($data['id']);
        }

        return $data;
    }

    /**
     * @param int $id Ticket data in array format
     * @return array
     */
    protected function readTicketComments($id)
    {
        $iterator = new RestIterator($this->client, sprintf($this->commentsActionPattern, $id));
        $iterator->setDataKeyName('comments');

        $result = array();

        foreach ($iterator as $value) {
            $result[] = $value;
        }

        return $result;
    }

    /**
     * init client if it is not exist
     */
    protected function initializeClient()
    {
        if ($this->client == null) {
            $this->client = $this->clientFactory->getRestClient();
        }
    }
}
