<?php
/**
 * Net_URL2, a class representing a URL as per RFC 3986.
 *
 * PHP version 5
 *
 * @category Networking
 * @package  Net_URL2
 * @author   Some Pear Developers <pear@php.net>
 * @license  https://spdx.org/licenses/BSD-3-Clause BSD-3-Clause
 * @link     https://tools.ietf.org/html/rfc3986
 */

require_once 'PHPUnit/Autoload.php';

chdir(dirname(__FILE__) .  '/../');

require_once 'Net/URL2Test.php';
require_once 'Net/URL2.php';

/**
 * Test class for Net_URL2.
 *
 * @category Networking
 * @package  Net_URL2
 * @author   Some Pear Developers <pear@php.net>
 * @license  https://spdx.org/licenses/BSD-3-Clause BSD-3-Clause
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/Net_URL2
 */
class Net_URL2_AllTests
{
    /**
     * main()
     *
     * @return void
     */
    public static function main()
    {

        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * suite()
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Net_URL2 tests');
        /** Add testsuites, if there is. */
        $suite->addTestSuite('Net_URL2Test');

        return $suite;
    }
}

Net_URL2_AllTests::main();
