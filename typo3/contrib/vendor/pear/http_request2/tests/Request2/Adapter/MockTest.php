<?php
/**
 * Unit tests for HTTP_Request2 package
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.github.com/pear/HTTP_Request2/trunk/docs/LICENSE
 *
 * @category  HTTP
 * @package   HTTP_Request2
 * @author    Alexey Borzov <avb@php.net>
 * @copyright 2008-2014 Alexey Borzov <avb@php.net>
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      http://pear.php.net/package/HTTP_Request2
 */

/** Sets up includes */
require_once dirname(dirname(dirname(__FILE__))) . '/TestHelper.php';

/**
 * Class representing a HTTP request
 */
require_once 'HTTP/Request2.php';

/**
 * Mock adapter intended for testing
 */
require_once 'HTTP/Request2/Adapter/Mock.php';

/**
 * Unit test for HTTP_Request2_Response class
 */
class HTTP_Request2_Adapter_MockTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultResponse()
    {
        $req = new HTTP_Request2('http://www.example.com/', HTTP_Request2::METHOD_GET,
                                 array('adapter' => 'mock'));
        $response = $req->send();
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals(0, count($response->getHeader()));
        $this->assertEquals('', $response->getBody());
    }

    public function testResponseFromString()
    {
        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(
            "HTTP/1.1 200 OK\r\n" .
            "Content-Type: text/plain; charset=iso-8859-1\r\n" .
            "\r\n" .
            "This is a string"
        );
        $req = new HTTP_Request2('http://www.example.com/');
        $req->setAdapter($mock);

        $response = $req->send();
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(1, count($response->getHeader()));
        $this->assertEquals('This is a string', $response->getBody());
    }

    public function testResponseFromFile()
    {
        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(fopen(dirname(dirname(dirname(__FILE__))) .
                           '/_files/response_headers', 'rb'));

        $req = new HTTP_Request2('http://www.example.com/');
        $req->setAdapter($mock);

        $response = $req->send();
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(7, count($response->getHeader()));
        $this->assertEquals('Nothing to see here, move along.', $response->getBody());
    }

    public function testResponsesQueue()
    {
        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(
            "HTTP/1.1 301 Over there\r\n" .
            "Location: http://www.example.com/newpage.html\r\n" .
            "\r\n" .
            "The document is over there"
        );
        $mock->addResponse(
            "HTTP/1.1 200 OK\r\n" .
            "Content-Type: text/plain; charset=iso-8859-1\r\n" .
            "\r\n" .
            "This is a string"
        );

        $req = new HTTP_Request2('http://www.example.com/');
        $req->setAdapter($mock);
        $this->assertEquals(301, $req->send()->getStatus());
        $this->assertEquals(200, $req->send()->getStatus());
        $this->assertEquals(400, $req->send()->getStatus());
    }

    /**
     * Returning URL-specific responses
     * @link http://pear.php.net/bugs/bug.php?id=19276
     */
    public function testRequest19276()
    {
        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(
            "HTTP/1.1 200 OK\r\n" .
            "Content-Type: text/plain; charset=iso-8859-1\r\n" .
            "\r\n" .
            "This is a response from example.org",
            'http://example.org/'
        );
        $mock->addResponse(
            "HTTP/1.1 200 OK\r\n" .
            "Content-Type: text/plain; charset=iso-8859-1\r\n" .
            "\r\n" .
            "This is a response from example.com",
            'http://example.com/'
        );

        $req1 = new HTTP_Request2('http://localhost/');
        $req1->setAdapter($mock);
        $this->assertEquals(400, $req1->send()->getStatus());

        $req2 = new HTTP_Request2('http://example.com/');
        $req2->setAdapter($mock);
        $this->assertContains('example.com', $req2->send()->getBody());

        $req3 = new HTTP_Request2('http://example.org');
        $req3->setAdapter($mock);
        $this->assertContains('example.org', $req3->send()->getBody());
    }

    public function testResponseException()
    {
        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(
            new HTTP_Request2_Exception('Shit happens')
        );
        $req = new HTTP_Request2('http://www.example.com/');
        $req->setAdapter($mock);
        try {
            $req->send();
        } catch (Exception $e) {
            $this->assertEquals('Shit happens', $e->getMessage());
            return;
        }
        $this->fail('Expected HTTP_Request2_Exception was not thrown');
    }
}
?>
