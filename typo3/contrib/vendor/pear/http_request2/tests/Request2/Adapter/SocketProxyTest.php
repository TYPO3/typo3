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

/**
 * Unit test for Socket Adapter of HTTP_Request2 working through proxy
 */
class HTTP_Request2_Adapter_SocketProxyTest extends HTTP_Request2_Adapter_CommonNetworkTest
{
   /**
    * Configuration for HTTP Request object
    * @var array
    */
    protected $config = array(
        'adapter' => 'HTTP_Request2_Adapter_Socket'
    );

    protected function setUp()
    {
        if (!defined('HTTP_REQUEST2_TESTS_PROXY_HOST') || !HTTP_REQUEST2_TESTS_PROXY_HOST) {
            $this->markTestSkipped('Proxy is not configured');

        } else {
            $this->config += array(
                'proxy_host'        => HTTP_REQUEST2_TESTS_PROXY_HOST,
                'proxy_port'        => HTTP_REQUEST2_TESTS_PROXY_PORT,
                'proxy_user'        => HTTP_REQUEST2_TESTS_PROXY_USER,
                'proxy_password'    => HTTP_REQUEST2_TESTS_PROXY_PASSWORD,
                'proxy_auth_scheme' => HTTP_REQUEST2_TESTS_PROXY_AUTH_SCHEME,
                'proxy_type'        => HTTP_REQUEST2_TESTS_PROXY_TYPE
            );
            parent::setUp();
        }
    }
}
?>