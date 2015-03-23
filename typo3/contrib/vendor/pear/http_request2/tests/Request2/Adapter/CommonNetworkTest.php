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

/** Class representing a HTTP request */
require_once 'HTTP/Request2.php';
/** Class for building multipart/form-data request body */
require_once 'HTTP/Request2/MultipartBody.php';

class SlowpokeBody extends HTTP_Request2_MultipartBody
{
    protected $doSleep;

    public function rewind()
    {
        $this->doSleep = true;
        parent::rewind();
    }

    public function read($length)
    {
        if ($this->doSleep) {
            sleep(3);
            $this->doSleep = false;
        }
        return parent::read($length);
    }
}

class HeaderObserver implements SplObserver
{
    public $headers;

    public function update(SplSubject $subject)
    {
        $event = $subject->getLastEvent();

        // force a timeout when writing request body
        if ('sentHeaders' == $event['name']) {
            $this->headers = $event['data'];
        }
    }
}

/**
 * Tests for HTTP_Request2 package that require a working webserver
 *
 * The class contains some common tests that should be run for all Adapters,
 * it is extended by their unit tests.
 *
 * You need to properly set up this test suite, refer to NetworkConfig.php.dist
 */
abstract class HTTP_Request2_Adapter_CommonNetworkTest extends PHPUnit_Framework_TestCase
{
   /**
    * HTTP Request object
    * @var HTTP_Request2
    */
    protected $request;

   /**
    * Base URL for remote test files
    * @var string
    */
    protected $baseUrl;

   /**
    * Configuration for HTTP Request object
    * @var array
    */
    protected $config = array();

    protected function setUp()
    {
        if (!defined('HTTP_REQUEST2_TESTS_BASE_URL') || !HTTP_REQUEST2_TESTS_BASE_URL) {
            $this->markTestSkipped('Base URL is not configured');

        } else {
            $this->baseUrl = rtrim(HTTP_REQUEST2_TESTS_BASE_URL, '/') . '/';
            $name = strtolower(preg_replace('/^test/i', '', $this->getName())) . '.php';

            $this->request = new HTTP_Request2(
                $this->baseUrl . $name, HTTP_Request2::METHOD_GET, $this->config
            );
        }
    }

   /**
    * Tests possibility to send GET parameters
    *
    * NB: Currently there are problems with Net_URL2::setQueryVariables(), thus
    * array structure is simple: http://pear.php.net/bugs/bug.php?id=18267
    */
    public function testGetParameters()
    {
        $data = array(
            'bar' => array(
                'key' => 'value'
            ),
            'foo' => 'some value',
            'numbered' => array('first', 'second')
        );

        $this->request->getUrl()->setQueryVariables($data);
        $response = $this->request->send();
        $this->assertEquals(serialize($data), $response->getBody());
    }

    public function testPostParameters()
    {
        $data = array(
            'bar' => array(
                'key' => 'some other value'
            ),
            'baz' => array(
                'key1' => array(
                    'key2' => 'yet another value'
                )
            ),
            'foo' => 'some value',
            'indexed' => array('first', 'second')
        );

        $this->request->setMethod(HTTP_Request2::METHOD_POST)
                      ->addPostParameter($data);

        $response = $this->request->send();
        $this->assertEquals(serialize($data), $response->getBody());
    }

    public function testUploads()
    {
        $this->request->setMethod(HTTP_Request2::METHOD_POST)
                      ->addUpload('foo', dirname(dirname(dirname(__FILE__))) . '/_files/empty.gif', 'picture.gif', 'image/gif')
                      ->addUpload('bar', array(
                                    array(dirname(dirname(dirname(__FILE__))) . '/_files/empty.gif', null, 'image/gif'),
                                    array(dirname(dirname(dirname(__FILE__))) . '/_files/plaintext.txt', 'secret.txt', 'text/x-whatever')
                                  ));

        $response = $this->request->send();
        $this->assertContains("foo picture.gif image/gif 43", $response->getBody());
        $this->assertContains("bar[0] empty.gif image/gif 43", $response->getBody());
        $this->assertContains("bar[1] secret.txt text/x-whatever 15", $response->getBody());
    }

    public function testRawPostData()
    {
        $data = 'Nothing to see here, move along';

        $this->request->setMethod(HTTP_Request2::METHOD_POST)
                      ->setBody($data);
        $response = $this->request->send();
        $this->assertEquals($data, $response->getBody());
    }

    public function testCookies()
    {
        $cookies = array(
            'CUSTOMER'    => 'WILE_E_COYOTE',
            'PART_NUMBER' => 'ROCKET_LAUNCHER_0001'
        );

        foreach ($cookies as $k => $v) {
            $this->request->addCookie($k, $v);
        }
        $response = $this->request->send();
        $this->assertEquals(serialize($cookies), $response->getBody());
    }

    public function testTimeout()
    {
        $this->request->setConfig('timeout', 2);
        try {
            $this->request->send();
            $this->fail('Expected HTTP_Request2_Exception was not thrown');
        } catch (HTTP_Request2_MessageException $e) {
            $this->assertEquals(HTTP_Request2_Exception::TIMEOUT, $e->getCode());
        }
    }

    public function testTimeoutInRequest()
    {
        $this->request->setConfig('timeout', 2)
                      ->setUrl($this->baseUrl . 'postparameters.php')
                      ->setBody(new SlowpokeBody(array('foo' => 'some value'), array()));
        try {
            $this->request->send();
            $this->fail('Expected HTTP_Request2_MessageException was not thrown');
        } catch (HTTP_Request2_MessageException $e) {
            $this->assertEquals(HTTP_Request2_Exception::TIMEOUT, $e->getCode());
        }
    }

    public function testBasicAuth()
    {
        $this->request->getUrl()->setQueryVariables(array(
            'user' => 'luser',
            'pass' => 'qwerty'
        ));
        $wrong = clone $this->request;

        $this->request->setAuth('luser', 'qwerty');
        $response = $this->request->send();
        $this->assertEquals(200, $response->getStatus());

        $wrong->setAuth('luser', 'password');
        $response = $wrong->send();
        $this->assertEquals(401, $response->getStatus());
    }

    public function testDigestAuth()
    {
        $this->request->getUrl()->setQueryVariables(array(
            'user' => 'luser',
            'pass' => 'qwerty'
        ));
        $wrong = clone $this->request;

        $this->request->setAuth('luser', 'qwerty', HTTP_Request2::AUTH_DIGEST);
        $response = $this->request->send();
        $this->assertEquals(200, $response->getStatus());

        $wrong->setAuth('luser', 'password', HTTP_Request2::AUTH_DIGEST);
        $response = $wrong->send();
        $this->assertEquals(401, $response->getStatus());
    }

    public function testRedirectsDefault()
    {
        $this->request->setUrl($this->baseUrl . 'redirects.php')
                      ->setConfig(array('follow_redirects' => true, 'strict_redirects' => false))
                      ->setMethod(HTTP_Request2::METHOD_POST)
                      ->addPostParameter('foo', 'foo value');

        $response = $this->request->send();
        $this->assertContains('Method=GET', $response->getBody());
        $this->assertNotContains('foo', $response->getBody());
        $this->assertEquals($this->baseUrl . 'redirects.php?redirects=0', $response->getEffectiveUrl());
    }

    public function testRedirectsStrict()
    {
        $this->request->setUrl($this->baseUrl . 'redirects.php')
                      ->setConfig(array('follow_redirects' => true, 'strict_redirects' => true))
                      ->setMethod(HTTP_Request2::METHOD_POST)
                      ->addPostParameter('foo', 'foo value');

        $response = $this->request->send();
        $this->assertContains('Method=POST', $response->getBody());
        $this->assertContains('foo', $response->getBody());
    }

    public function testRedirectsLimit()
    {
        $this->request->setUrl($this->baseUrl . 'redirects.php?redirects=4')
                      ->setConfig(array('follow_redirects' => true, 'max_redirects' => 2));

        try {
            $this->request->send();
            $this->fail('Expected HTTP_Request2_Exception was not thrown');
        } catch (HTTP_Request2_MessageException $e) {
            $this->assertEquals(HTTP_Request2_Exception::TOO_MANY_REDIRECTS, $e->getCode());
        }
    }

    public function testRedirectsRelative()
    {
        $this->request->setUrl($this->baseUrl . 'redirects.php?special=relative')
                      ->setConfig(array('follow_redirects' => true));

        $response = $this->request->send();
        $this->assertContains('did relative', $response->getBody());
    }

    public function testRedirectsNonHTTP()
    {
        $this->request->setUrl($this->baseUrl . 'redirects.php?special=ftp')
                      ->setConfig(array('follow_redirects' => true));

        try {
            $this->request->send();
            $this->fail('Expected HTTP_Request2_Exception was not thrown');
        } catch (HTTP_Request2_MessageException $e) {
            $this->assertEquals(HTTP_Request2_Exception::NON_HTTP_REDIRECT, $e->getCode());
        }
    }

    public function testCookieJar()
    {
        $this->request->setUrl($this->baseUrl . 'setcookie.php?name=cookie_name&value=cookie_value');
        $req2 = clone $this->request;

        $this->request->setCookieJar()->send();
        $jar = $this->request->getCookieJar();
        $jar->store(
            array('name' => 'foo', 'value' => 'bar'),
            $this->request->getUrl()
        );

        $response = $req2->setUrl($this->baseUrl . 'cookies.php')->setCookieJar($jar)->send();
        $this->assertEquals(
            serialize(array('cookie_name' => 'cookie_value', 'foo' => 'bar')),
            $response->getBody()
        );
    }

    public function testCookieJarAndRedirect()
    {
        $this->request->setUrl($this->baseUrl . 'redirects.php?special=cookie')
                      ->setConfig('follow_redirects', true)
                      ->setCookieJar();

        $response = $this->request->send();
        $this->assertEquals(serialize(array('cookie_on_redirect' => 'success')), $response->getBody());
    }

    /**
     * @link http://pear.php.net/bugs/bug.php?id=20125
     */
    public function testChunkedRequest()
    {
        $data = array(
            'long'      => str_repeat('a', 1000),
            'very_long' => str_repeat('b', 2000)
        );

        $this->request->setMethod(HTTP_Request2::METHOD_POST)
                      ->setUrl($this->baseUrl . 'postparameters.php')
                      ->setConfig('buffer_size', 512)
                      ->setHeader('Transfer-Encoding', 'chunked')
                      ->addPostParameter($data);

        $response = $this->request->send();
        $this->assertEquals(serialize($data), $response->getBody());
    }

    /**
     * @link http://pear.php.net/bugs/bug.php?id=19233
     * @link http://pear.php.net/bugs/bug.php?id=15937
     */
    public function testPreventExpectHeader()
    {
        $fp       = fopen(dirname(dirname(dirname(__FILE__))) . '/_files/bug_15305', 'rb');
        $observer = new HeaderObserver();
        $body     = new HTTP_Request2_MultipartBody(
            array(),
            array(
                'upload' => array(
                    'fp'       => $fp,
                    'filename' => 'bug_15305',
                    'type'     => 'application/octet-stream',
                    'size'     => 16338
                )
            )
        );

        $this->request->setMethod(HTTP_Request2::METHOD_POST)
                      ->setUrl($this->baseUrl . 'uploads.php')
                      ->setHeader('Expect', '')
                      ->setBody($body)
                      ->attach($observer);

        $response = $this->request->send();
        $this->assertNotContains('Expect:', $observer->headers);
        $this->assertContains('upload bug_15305 application/octet-stream 16338', $response->getBody());
    }
}
?>