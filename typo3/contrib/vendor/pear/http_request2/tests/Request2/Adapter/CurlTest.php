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

/** Tests for HTTP_Request2 package that require a working webserver */
require_once dirname(__FILE__) . '/CommonNetworkTest.php';

/** Adapter for HTTP_Request2 wrapping around cURL extension */

/**
 * Unit test for Curl Adapter of HTTP_Request2
 */
class HTTP_Request2_Adapter_CurlTest extends HTTP_Request2_Adapter_CommonNetworkTest
{
   /**
    * Configuration for HTTP Request object
    * @var array
    */
    protected $config = array(
        'adapter' => 'HTTP_Request2_Adapter_Curl'
    );

   /**
    * Checks whether redirect support in cURL is disabled by safe_mode or open_basedir
    * @return bool
    */
    protected function isRedirectSupportDisabled()
    {
        return ini_get('safe_mode') || ini_get('open_basedir');
    }

    public function testRedirectsDefault()
    {
        if ($this->isRedirectSupportDisabled()) {
            $this->markTestSkipped('Redirect support in cURL is disabled by safe_mode or open_basedir setting');
        } else {
            parent::testRedirectsDefault();
        }
    }

    public function testRedirectsStrict()
    {
        if ($this->isRedirectSupportDisabled()) {
            $this->markTestSkipped('Redirect support in cURL is disabled by safe_mode or open_basedir setting');
        } else {
            parent::testRedirectsStrict();
        }
    }

    public function testRedirectsLimit()
    {
        if ($this->isRedirectSupportDisabled()) {
            $this->markTestSkipped('Redirect support in cURL is disabled by safe_mode or open_basedir setting');
        } else {
            parent::testRedirectsLimit();
        }
    }

    public function testRedirectsRelative()
    {
        if ($this->isRedirectSupportDisabled()) {
            $this->markTestSkipped('Redirect support in cURL is disabled by safe_mode or open_basedir setting');
        } else {
            parent::testRedirectsRelative();
        }
    }

    public function testRedirectsNonHTTP()
    {
        if ($this->isRedirectSupportDisabled()) {
            $this->markTestSkipped('Redirect support in cURL is disabled by safe_mode or open_basedir setting');
        } else {
            parent::testRedirectsNonHTTP();
        }
    }

    public function testCookieJarAndRedirect()
    {
        if ($this->isRedirectSupportDisabled()) {
            $this->markTestSkipped('Redirect support in cURL is disabled by safe_mode or open_basedir setting');
        } else {
            parent::testCookieJarAndRedirect();
        }
    }

    public function testBug17450()
    {
        if (!$this->isRedirectSupportDisabled()) {
            $this->markTestSkipped('Neither safe_mode nor open_basedir is enabled');
        }

        $this->request->setUrl($this->baseUrl . 'redirects.php')
                      ->setConfig(array('follow_redirects' => true));

        try {
            $this->request->send();
            $this->fail('Expected HTTP_Request2_Exception was not thrown');

        } catch (HTTP_Request2_LogicException $e) {
            $this->assertEquals(HTTP_Request2_Exception::MISCONFIGURATION, $e->getCode());
        }
    }
}
?>