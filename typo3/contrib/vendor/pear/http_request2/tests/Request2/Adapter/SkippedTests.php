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
 * Shows a skipped test if networked tests are not configured
 */
class HTTP_Request2_Adapter_Skip_SocketTest extends PHPUnit_Framework_TestCase
{
    public function testSocketAdapter()
    {
        $this->markTestSkipped('Socket Adapter tests need base URL configured.');
    }
}

/**
 * Shows a skipped test if proxy is not configured
 */
class HTTP_Request2_Adapter_Skip_SocketProxyTest extends PHPUnit_Framework_TestCase
{
    public function testSocketAdapterWithProxy()
    {
        $this->markTestSkipped('Socket Adapter proxy tests need base URL and proxy configured');
    }
}

/**
 * Shows a skipped test if networked tests are not configured or cURL extension is unavailable
 */
class HTTP_Request2_Adapter_Skip_CurlTest extends PHPUnit_Framework_TestCase
{
    public function testCurlAdapter()
    {
        $this->markTestSkipped('Curl Adapter tests need base URL configured and curl extension available');
    }
}
?>