<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This file is defined in FuntionalTests.xml and called by phpunit
 * before instantiating the test suites, it must also be included
 * with phpunit parameter --bootstrap if executing single test case classes.
 */

/**
 * Require classes the functional test classes extend from or use for further bootstrap.
 * Only files required for "new TestCaseClass" are required here and a general exception
 * that is thrown by setUp() code.
 */
require_once(__DIR__ . '/../Tests/BaseTestCase.php');
require_once(__DIR__ . '/../Tests/FunctionalTestCase.php');
require_once(__DIR__ . '/../Tests/FunctionalTestCaseBootstrapUtility.php');
require_once(__DIR__ . '/../Tests/Exception.php');

/**
 * Define a constant to specify the document root since this calculation would
 * be way more complicated if done within the test class.
 *
 * It is required that phpunit binary is called from the document root of the instance,
 * or TYPO3_PATH_WEB environement variable needs to be set,
 * otherwise path calculation, of the created TYPO3 CMS instance will fail.
 */
if (getenv('TYPO3_PATH_WEB')) {
	$webRoot = getenv('TYPO3_PATH_WEB') . '/';
} elseif (isset($_SERVER['PWD'])) {
	$webRoot = $_SERVER['PWD'] . '/';
} else {
	$webRoot = getcwd() . '/';
}
$webRoot = strtr($webRoot, '\\', '/');

if (!defined('ORIGINAL_ROOT')) {
	define('ORIGINAL_ROOT', $webRoot);
}

unset($webRoot);