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

/** Include PHPUnit dependencies based on version */
require_once 'PHPUnit/Runner/Version.php';

// If running from SVN checkout, update include_path
if ('@' . 'package_version@' == '@package_version@') {
    $classPath   = realpath(dirname(dirname(__FILE__)));
    $includePath = array_map('realpath', explode(PATH_SEPARATOR, get_include_path()));
    if (0 !== ($key = array_search($classPath, $includePath))) {
        if (false !== $key) {
            unset($includePath[$key]);
        }
        set_include_path($classPath . PATH_SEPARATOR . implode(PATH_SEPARATOR, $includePath));
    }
}

$phpunitVersion = PHPUnit_Runner_Version::id();
if ($phpunitVersion == '@' . 'package_version@' || !version_compare($phpunitVersion, '3.8', '<=')) {
    echo "This version of PHPUnit is not supported.";
    exit(1);
} elseif (version_compare($phpunitVersion, '3.5.0', '>=')) {
    require_once 'PHPUnit/Autoload.php';
} else {
    require_once 'PHPUnit/Framework.php';
}

if (!defined('HTTP_REQUEST2_TESTS_BASE_URL')
    && is_readable(dirname(__FILE__) . '/NetworkConfig.php')
) {
    require_once dirname(__FILE__) . '/NetworkConfig.php';
}
?>