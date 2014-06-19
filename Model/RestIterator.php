<?php

namespace OroCRM\Bundle\ZendeskBundle\Model;


class RestIterator implements \Iterator
{
    /**
     * @var RestClientInterface
     */
    protected $client;

    /**
     * @var String
     */
    protected $firstPageAction;

    /**
     * @var String
     */
    protected $firstPageParams;

    /**
     * @var bool
     */
    protected $firstPageLoaded = false;

    /**
     * @var String
     */
    protected $nextPageUrl;

    /**
     * Page data
     *
     * @var array
     */
    private $pageData;

    /**
     * Results of page data
     *
     * @var array
     */
    private $rows;

    /**
     * Total count of items in response
     *
     * @var int
     */
    private $totalCount = null;

    /**
     * Offset of current item in current page
     *
     * @var int
     */
    private $offset = -1;

    /**
     * A position of a current item within the current page
     *
     * @var int
     */
    private $position = -1;

    /**
     * Current item, populated from request response
     *
     * @var mixed
     */
    private $current = null;

    /**
     * @param RestClientInterface $client
     * @param string $action
     * @param array $params
     */
    public function __construct(RestClientInterface $client, $action, array $params = array())
    {
        $this->client = $client;
        $this->firstPageAction = $action;
        $this->firstPageParams = $params;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        $this->offset++;

        if (!isset($this->rows[$this->offset]) && !$this->loadNextPage()) {
            $this->current = null;
        } else {
            $this->current  = $this->rows[$this->offset];
        }
        $this->position++;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        if (!$this->firstPageLoaded) {
            $this->rewind();
        }

        return null !== $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->firstPageLoaded  = false;
        $this->nextPageUrl      = null;
        $this->totalCount       = null;
        $this->offset           = -1;
        $this->position         = -1;
        $this->current          = null;
        $this->rows             = null;
        $this->pageData         = null;

        $this->next();
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        if (!$this->firstPageLoaded) {
            $this->rewind();
        }

        return $this->totalCount;
    }

    /**
     * Attempts to load next page
     *
     * @return bool If page loaded successfully
     */
    protected function loadNextPage()
    {
        if (!$this->firstPageLoaded) {
            $this->pageData = $this->client->get($this->firstPageAction, $this->firstPageParams);
            $this->firstPageLoaded = true;
        } elseif ($this->nextPageUrl) {
            $this->pageData = $this->client->get($this->nextPageUrl);
        } else {
            $this->rows = null;
            $this->pageData = null;
            return false;
        }

        if (isset($this->pageData['results'])) {
            $this->rows = (array)$this->pageData['results'];
        } else {
            $this->rows = null;
        }

        if (isset($this->pageData['count'])) {
            $this->totalCount = (int)$this->pageData['count'];
        } elseif (!$this->totalCount) {
            $this->totalCount = count($this->rows);
        }

        if (isset($this->pageData['next_page'])) {
            $this->nextPageUrl = (string)$this->pageData['next_page'];
        } else {
            $this->nextPageUrl = null;
        }

        $this->offset = 0;

        return count($this->rows) > 0 && $this->totalCount;
    }
}
