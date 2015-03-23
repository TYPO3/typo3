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

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Request2_AllTests::main');
}

require_once dirname(__FILE__) . '/CookieJarTest.php';
require_once dirname(__FILE__) . '/MultipartBodyTest.php';
require_once dirname(__FILE__) . '/ResponseTest.php';
require_once dirname(__FILE__) . '/Adapter/AllTests.php';

class Request2_AllTests
{
    public static function main()
    {
        if (!function_exists('phpunit_autoload')) {
            require_once 'PHPUnit/TextUI/TestRunner.php';
        }
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('HTTP_Request2 package - Request2');

        $suite->addTestSuite('HTTP_Request2_CookieJarTest');
        $suite->addTestSuite('HTTP_Request2_MultipartBodyTest');
        $suite->addTestSuite('HTTP_Request2_ResponseTest');
        $suite->addTest(Request2_Adapter_AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Request2_AllTests::main') {
    Request2_AllTests::main();
}
?>