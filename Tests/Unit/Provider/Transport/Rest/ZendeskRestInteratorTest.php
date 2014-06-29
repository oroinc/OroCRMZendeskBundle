<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Provider\Transport\Rest;

use OroCRM\Bundle\ZendeskBundle\Provider\Transport\Rest\ZendeskRestIterator;

class ZendeskRestIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var string
     */
    protected $resource = 'users';

    /**
     * @var string
     */
    protected $dataKeyName = 'results';

    /**
     * @var array
     */
    protected $params = array('foo' => 'bar');

    /**
     * @var ZendeskRestIterator
     */
    protected $iterator;

    protected function setUp()
    {
        $this->client = $this->getMock('Oro\\Bundle\\IntegrationBundle\\Provider\\Rest\\Client\\RestClientInterface');
        $this->iterator = new ZendeskRestIterator($this->client, $this->resource, $this->dataKeyName, $this->params);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorForeach(array $clientExpectations, array $expectedItems)
    {
        foreach ($clientExpectations as $index => $data) {
            $stub = $this->client->expects($this->at($index))->method('getJSON');
            call_user_func_array(array($stub, 'with'), $data['request']);
            $stub->will($this->returnValue($data['response']));
        }

        $actualItems = array();

        foreach ($this->iterator as $key => $value) {
            $actualItems[$key] = $value;
        }

        $this->assertEquals($expectedItems, $actualItems);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorWhile(array $clientExpectations, array $expectedItems)
    {
        foreach ($clientExpectations as $index => $data) {
            $stub = $this->client->expects($this->at($index))->method('getJSON');
            call_user_func_array(array($stub, 'with'), $data['request']);
            $stub->will($this->returnValue($data['response']));
        }

        $actualItems = array();

        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        $this->assertEquals($expectedItems, $actualItems);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIterateTwice(array $clientExpectations, array $expectedItems)
    {
        $callIndex = 0;
        foreach ($clientExpectations as $data) {
            $stub = $this->client->expects($this->at($callIndex++))->method('getJSON');
            call_user_func_array(array($stub, 'with'), $data['request']);
            $stub->will($this->returnValue($data['response']));
        }

        foreach ($clientExpectations as $data) {
            $stub = $this->client->expects($this->at($callIndex++))->method('getJSON');
            call_user_func_array(array($stub, 'with'), $data['request']);
            $stub->will($this->returnValue($data['response']));
        }

        $actualItems = array();

        $this->iterator->rewind();
        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        $this->assertEquals($expectedItems, $actualItems);

        $actualItems = array();

        $this->iterator->rewind();
        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        $this->assertEquals($expectedItems, $actualItems);
    }

    public function iteratorDataProvider()
    {
        return array(
            'two pages, 7 records' => array(
                'clientExpectations' => array(
                    array(
                        'request' => array($this->resource, $this->params),
                        'response' => array(
                            'count' => 7,
                            'next_page' => 'http://test.zendesk.com/api/v2/' . $this->resource . '.json?page=2',
                            'previous_page' => null,
                            'results' => array(
                                array('id' => 1),
                                array('id' => 2),
                                array('id' => 3),
                                array('id' => 4),
                            )
                        ),
                    ),
                    array(
                        'request' => array('http://test.zendesk.com/api/v2/' . $this->resource . '.json?page=2'),
                        'response' => array(
                            'count' => 7,
                            'next_page' => null,
                            'previous_page' => 'http://test.zendesk.com/api/v2/' . $this->resource . '.json?page=1',
                            'results' => array(
                                array('id' => 5),
                                array('id' => 6),
                                array('id' => 7),
                            )
                        ),
                    ),
                ),
                'expectedItems' => array(
                    array('id' => 1),
                    array('id' => 2),
                    array('id' => 3),
                    array('id' => 4),
                    array('id' => 5),
                    array('id' => 6),
                    array('id' => 7),
                )
            ),
            'no total count' => array(
                'clientExpectations' => array(
                    array(
                        'request' => array($this->resource, $this->params),
                        'response' => array(
                            'next_page' => 'http://test.zendesk.com/api/v2/' . $this->resource . '.json?page=2',
                            'previous_page' => null,
                            'results' => array(
                                array('id' => 1),
                                array('id' => 2),
                                array('id' => 3),
                                array('id' => 4),
                            )
                        ),
                    ),
                    array(
                        'request' => array('http://test.zendesk.com/api/v2/' . $this->resource . '.json?page=2'),
                        'response' => array(
                            'next_page' => null,
                            'previous_page' => 'http://test.zendesk.com/api/v2/' . $this->resource . '.json?page=2',
                            'results' => array()
                        ),
                    )
                ),
                'expectedItems' => array(
                    array('id' => 1),
                    array('id' => 2),
                    array('id' => 3),
                    array('id' => 4),
                )
            ),
            'empty results' => array(
                'clientExpectations' => array(
                    array(
                        'request' => array($this->resource, $this->params),
                        'response' => array(
                            'next_page' => null,
                            'previous_page' => null,
                            'results' => array()
                        ),
                    )
                ),
                'expectedItems' => array()
            ),
            'empty response' => array(
                'clientExpectations' => array(
                    array(
                        'request' => array($this->resource, $this->params),
                        'response' => array(),
                    )
                ),
                'expectedItems' => array()
            ),
        );
    }

    /**
     * @dataProvider countDataProvider
     */
    public function testCount(array $response, $expectedCount)
    {
        $this->client->expects($this->once())
            ->method('getJSON')
            ->with($this->resource, $this->params)
            ->will($this->returnValue($response));

        $this->assertEquals($expectedCount, $this->iterator->count());
    }

    public function countDataProvider()
    {
        return array(
            'normal' => array(
                'response' => array(
                    'count' => 1777,
                    'next_page' => null,
                    'previous_page' => 'http://test.zendesk.com/api/v2/' . $this->resource . '.json?page=1',
                    'results' => array(
                        array('id' => 1),
                        array('id' => 2),
                        array('id' => 3),
                    )
                ),
                'expectedCount' => 1777
            ),
            'empty count' => array(
                'response' => array(
                    'next_page' => null,
                    'previous_page' => 'http://test.zendesk.com/api/v2/' . $this->resource . '.json?page=1',
                    'results' => array(
                        array('id' => 1),
                        array('id' => 2),
                        array('id' => 3),
                    )
                ),
                'expectedCount' => 3
            ),
            'empty response' => array(
                'response' => array(
                    'next_page' => null,
                    'previous_page' => 'http://test.zendesk.com/api/v2/' . $this->resource . '.json?page=1',
                    'results' => array()
                ),
                'expectedCount' => 0
            ),
        );
    }
}
