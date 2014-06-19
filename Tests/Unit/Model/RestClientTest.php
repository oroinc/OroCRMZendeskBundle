<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Unit\Model;

use Guzzle\Http\Exception\RequestException;

use OroCRM\Bundle\ZendeskBundle\Model\RestClient;

class RestClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    protected function setUp()
    {
        $this->client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetSettings()
    {
        $settings = array(
            'email' => 'test@mail.com',
            'api_token' => uniqid(),
            'sub_domain' => 'domain'
        );

        $restClient = $this->getClient($settings);
        $this->assertEquals($settings, $restClient->getSettings());
    }

    public function testGetUseActionLikeUrlIfItIsUrl()
    {
        $expected = 'https://foo.zendesk.com/api/v2/search.json?query="type:Group%20hello"&page=2';

        $settings = array(
            'email' => 'test@mail.com',
            'api_token' => uniqid()
        );
        $expectedAuth = array('auth' => array('test@mail.com/token', $settings['api_token']));

        $request = $this->getRequest();

        $this->client->expects($this->once())
            ->method('createRequest')
            ->with('get', $expected, null, null, $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($settings);
        $restClient->get($expected, array('test' => 'test'));
    }

    public function testGetUseActionAndConstructUrl()
    {
        $action = 'users.json';

        $settings = array(
            'email' => 'test@mail.com',
            'url' => 'https://foo.zendesk.com',
            'api_token' => uniqid()
        );
        $paramName = 'test_name';
        $paramValue = 'test_value';
        $params = array($paramName => $paramValue);
        $expected = "https://foo.zendesk.com/api/v2/{$action}?{$paramName}={$paramValue}";
        $expectedBody = array('test json' => array('test param' => 'test value'));
        $expectedAuth = array('auth' => array('test@mail.com/token', $settings['api_token']));

        $request = $this->getRequest($expectedBody);
        $this->client->expects($this->once())
            ->method('createRequest')
            ->with('get', $expected, null, null, $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($settings);
        $actualBody = $restClient->get($action, $params);
        $this->assertEquals($expectedBody, $actualBody);
    }

    public function testPost()
    {
        $action = 'users.json';
        $settings = array(
            'email' => 'test@mail.com',
            'url' => 'https://foo.zendesk.com',
            'api_token' => uniqid()
        );
        $expectedData = array('user' => array('name' => 'Roger Smith'));
        $expectedUrl = "https://foo.zendesk.com/api/v2/{$action}";
        $expectedBody = array('test json' => array('test param' => 'test value'));
        $expectedAuth = array('auth' => array('test@mail.com/token', $settings['api_token']));
        $expectedHeaders = array('Content-Type' => 'application/json');

        $request = $this->getRequest($expectedBody);
        $this->client->expects($this->once())
            ->method('createRequest')
            ->with('post', $expectedUrl, $expectedHeaders, json_encode($expectedData), $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($settings);
        $actualBody = $restClient->post($action, $expectedData);
        $this->assertEquals($expectedBody, $actualBody);
    }

    public function testPut()
    {
        $action = 'users.json';

        $settings = array(
            'email' => 'test@mail.com',
            'url' => 'https://foo.zendesk.com',
            'api_token' => uniqid()
        );
        $expectedData = array('user' => array('name' => 'Roger Smith'));
        $expectedUrl = "https://foo.zendesk.com/api/v2/{$action}";
        $expectedBody = array('test json' => array('test param' => 'test value'));
        $expectedAuth = array('auth' => array('test@mail.com/token', $settings['api_token']));
        $expectedHeaders = array('Content-Type' => 'application/json');

        $request = $this->getRequest($expectedBody);
        $this->client->expects($this->once())
            ->method('createRequest')
            ->with('put', $expectedUrl, $expectedHeaders, json_encode($expectedData), $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($settings);
        $actualBody = $restClient->put($action, $expectedData);
        $this->assertEquals($expectedBody, $actualBody);
    }

    public function testDelete()
    {
        $action = 'users.json';

        $settings = array(
            'email' => 'test@mail.com',
            'url' => 'https://foo.zendesk.com',
            'api_token' => uniqid()
        );
        $expectedUrl = "https://foo.zendesk.com/api/v2/{$action}";
        $expectedBody = array('test json' => array('test param' => 'test value'));
        $expectedAuth = array('auth' => array('test@mail.com/token', $settings['api_token']));

        $request = $this->getRequest($expectedBody);
        $this->client->expects($this->once())
            ->method('createRequest')
            ->with('delete', $expectedUrl, null, null, $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($settings);
        $actualBody = $restClient->delete($action);
        $this->assertEquals($expectedBody, $actualBody);
    }

    /**
     * @expectedException \OroCRM\Bundle\ZendeskBundle\Exception\RestException
     * @expectedExceptionMessage test message
     */
    public function testPerformRequestThrowAnExceptionIfSentThrowGuzzleException()
    {
        $settings = array(
            'email' => 'test@mail.com',
            'url' => 'https://foo.zendesk.com',
            'api_token' => uniqid()
        );

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $request->expects($this->once())
            ->method('Send')
            ->will($this->throwException(new RequestException('test message', 42)));
        $this->client->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($request));
        $restClient = $this->getClient($settings);
        $restClient->get('https://foo.zendesk.com');
    }

    /**
     * @expectedException \OroCRM\Bundle\ZendeskBundle\Exception\RestException
     * @expectedExceptionMessage Zendesk API request error
     * [error] Unexpected response status
     * [url] https://foo.zendesk.com/api/v2/users.json
     * [method] GET
     * [status code] 401
     */
    public function testPerformRequestThrowAnExceptionIfStatusCodeNot200()
    {
        $settings = array(
            'email' => 'test@mail.com',
            'url' => 'https://foo.zendesk.com',
            'api_token' => uniqid()
        );

        $request = $this->getRequest(array(), 401);
        $request->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue('https://foo.zendesk.com/api/v2/users.json'));
        $request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'));

        $this->client->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($request));
        $restClient = $this->getClient($settings);
        $restClient->get('https://foo.zendesk.com');
    }

    /**
     * @param array $settings
     * @return RestClient
     */
    public function getClient($settings)
    {
        return new RestClient($this->client, $settings);
    }

    /**
     * @param array $expectedBody
     * @param int   $statusCode
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequest($expectedBody = array(), $statusCode = 200)
    {
        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->will($this->returnValue($statusCode));
        $response->expects($this->any())
            ->method('getBody')
            ->with(true)
            ->will($this->returnValue(json_encode($expectedBody)));
        $request->expects($this->once())
            ->method('Send')
            ->will($this->returnValue($response));

        return $request;
    }
}
