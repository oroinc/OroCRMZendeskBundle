<?php

namespace Unit\Model;

use Guzzle\Http\Exception\RequestException;

use OroCRM\Bundle\ZendeskBundle\Model\RestClient;

class RestClientTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSettings()
    {
        $settings = array(
            'email' => 'test@mail.com',
            'api_token' => uniqid(),
            'sub_domain' => 'domain'
        );
        $client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $restClient = $this->getClient($client, $settings);
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

        $client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getRequest();

        $client->expects($this->once())
            ->method('createRequest')
            ->with('get', $expected, null, null, $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($client, $settings);
        $restClient->get($expected, array('test' => 'test'));
    }

    public function testGetUseActionAndConstructUrl()
    {
        $action = 'users.json';

        $subDomain = 'oro_test';
        $settings = array(
            'email' => 'test@mail.com',
            'sub_domain' => $subDomain,
            'api_token' => uniqid()
        );
        $paramName = 'test_name';
        $paramValue = 'test_value';
        $params = array($paramName => $paramValue);
        $expected = "https://{$subDomain}.zendesk.com/api/v2/{$action}?{$paramName}={$paramValue}";
        $expectedBody = array('test json' => array('test param' => 'test value'));
        $expectedAuth = array('auth' => array('test@mail.com/token', $settings['api_token']));

        $client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getRequest($expectedBody);
        $client->expects($this->once())
            ->method('createRequest')
            ->with('get', $expected, null, null, $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($client, $settings);
        $actualBody = $restClient->get($action, $params);
        $this->assertEquals($expectedBody, $actualBody);
    }

    public function testPost()
    {
        $action = 'users.json';

        $subDomain = 'oro_test';
        $settings = array(
            'email' => 'test@mail.com',
            'sub_domain' => $subDomain,
            'api_token' => uniqid()
        );
        $expectedData = array('user' => array('name' => 'Roger Smith'));
        $expectedUrl = "https://{$subDomain}.zendesk.com/api/v2/{$action}?";
        $expectedBody = array('test json' => array('test param' => 'test value'));
        $expectedAuth = array('auth' => array('test@mail.com/token', $settings['api_token']));
        $expectedHeaders = array('Content-Type' => 'application/json');

        $client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getRequest($expectedBody);
        $client->expects($this->once())
            ->method('createRequest')
            ->with('post', $expectedUrl, $expectedHeaders, json_encode($expectedData), $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($client, $settings);
        $actualBody = $restClient->post($action, $expectedData);
        $this->assertEquals($expectedBody, $actualBody);
    }

    public function testPut()
    {
        $action = 'users.json';

        $subDomain = 'oro_test';
        $settings = array(
            'email' => 'test@mail.com',
            'sub_domain' => $subDomain,
            'api_token' => uniqid()
        );
        $expectedData = array('user' => array('name' => 'Roger Smith'));
        $expectedUrl = "https://{$subDomain}.zendesk.com/api/v2/{$action}?";
        $expectedBody = array('test json' => array('test param' => 'test value'));
        $expectedAuth = array('auth' => array('test@mail.com/token', $settings['api_token']));
        $expectedHeaders = array('Content-Type' => 'application/json');

        $client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getRequest($expectedBody);
        $client->expects($this->once())
            ->method('createRequest')
            ->with('put', $expectedUrl, $expectedHeaders, json_encode($expectedData), $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($client, $settings);
        $actualBody = $restClient->put($action, $expectedData);
        $this->assertEquals($expectedBody, $actualBody);
    }

    public function testDelete()
    {
        $action = 'users.json';

        $subDomain = 'oro_test';
        $settings = array(
            'email' => 'test@mail.com',
            'sub_domain' => $subDomain,
            'api_token' => uniqid()
        );
        $expectedUrl = "https://{$subDomain}.zendesk.com/api/v2/{$action}?";
        $expectedBody = array('test json' => array('test param' => 'test value'));
        $expectedAuth = array('auth' => array('test@mail.com/token', $settings['api_token']));

        $client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getRequest($expectedBody);
        $client->expects($this->once())
            ->method('createRequest')
            ->with('delete', $expectedUrl, null, null, $expectedAuth)
            ->will($this->returnValue($request));
        $restClient = $this->getClient($client, $settings);
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
            'sub_domain' => 'test_sub_domain',
            'api_token' => uniqid()
        );
        $client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $request->expects($this->once())
            ->method('Send')
            ->will($this->throwException(new RequestException('test message', 42)));
        $client->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($request));
        $restClient = $this->getClient($client, $settings);
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
            'sub_domain' => 'test_sub_domain',
            'api_token' => uniqid()
        );
        $client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getRequest(array(), 401);
        $request->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue('https://foo.zendesk.com/api/v2/users.json'));
        $request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'));

        $client->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($request));
        $restClient = $this->getClient($client, $settings);
        $restClient->get('https://foo.zendesk.com');
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $client
     * @param array $settings
     * @return RestClient
     */
    public function getClient($client, $settings)
    {
        return new RestClient($client, $settings);
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
