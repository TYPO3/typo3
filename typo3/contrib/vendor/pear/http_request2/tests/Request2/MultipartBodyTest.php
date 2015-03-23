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
require_once dirname(dirname(__FILE__)) . '/TestHelper.php';

/**
 * Class representing a HTTP request
 */
require_once 'HTTP/Request2.php';

/**
 * Unit test for HTTP_Request2_MultipartBody class
 */
class HTTP_Request2_MultipartBodyTest extends PHPUnit_Framework_TestCase
{
    public function testUploadSimple()
    {
        $req = new HTTP_Request2(null, HTTP_Request2::METHOD_POST);
        $body = $req->addPostParameter('foo', 'I am a parameter')
                    ->addUpload('upload', dirname(dirname(__FILE__)) . '/_files/plaintext.txt')
                    ->getBody();

        $this->assertTrue($body instanceof HTTP_Request2_MultipartBody);
        $asString = $body->__toString();
        $boundary = $body->getBoundary();
        $this->assertEquals($body->getLength(), strlen($asString));
        $this->assertContains('This is a test.', $asString);
        $this->assertContains('I am a parameter', $asString);
        $this->assertRegexp("!--{$boundary}--\r\n$!", $asString);
    }

   /**
    *
    * @expectedException HTTP_Request2_LogicException
    */
    public function testRequest16863()
    {
        $req  = new HTTP_Request2(null, HTTP_Request2::METHOD_POST);
        $fp   = fopen(dirname(dirname(__FILE__)) . '/_files/plaintext.txt', 'rb');
        $body = $req->addUpload('upload', $fp)
                    ->getBody();

        $asString = $body->__toString();
        $this->assertContains('name="upload"; filename="anonymous.blob"', $asString);
        $this->assertContains('This is a test.', $asString);

        $req->addUpload('bad_upload', fopen('php://input', 'rb'));
    }

    public function testStreaming()
    {
        $req = new HTTP_Request2(null, HTTP_Request2::METHOD_POST);
        $body = $req->addPostParameter('foo', 'I am a parameter')
                    ->addUpload('upload', dirname(dirname(__FILE__)) . '/_files/plaintext.txt')
                    ->getBody();
        $asString = '';
        while ($part = $body->read(10)) {
            $asString .= $part;
        }
        $this->assertEquals($body->getLength(), strlen($asString));
        $this->assertContains('This is a test.', $asString);
        $this->assertContains('I am a parameter', $asString);
    }

    public function testUploadArray()
    {
        $req = new HTTP_Request2(null, HTTP_Request2::METHOD_POST);
        $body = $req->addUpload('upload', array(
                                    array(dirname(dirname(__FILE__)) . '/_files/plaintext.txt', 'bio.txt', 'text/plain'),
                                    array(fopen(dirname(dirname(__FILE__)) . '/_files/empty.gif', 'rb'), 'photo.gif', 'image/gif')
                                ))
                    ->getBody();
        $asString = $body->__toString();
        $this->assertContains(file_get_contents(dirname(dirname(__FILE__)) . '/_files/empty.gif'), $asString);
        $this->assertContains('name="upload[0]"; filename="bio.txt"', $asString);
        $this->assertContains('name="upload[1]"; filename="photo.gif"', $asString);

        $body2 = $req->setConfig(array('use_brackets' => false))->getBody();
        $asString = $body2->__toString();
        $this->assertContains('name="upload"; filename="bio.txt"', $asString);
        $this->assertContains('name="upload"; filename="photo.gif"', $asString);
    }
}
?>