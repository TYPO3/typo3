<?php
namespace TYPO3\CMS\Core\Build;

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
 * This file is defined in UnitTests.xml and called by phpunit
 * before instantiating the test suites, it must also be included
 * with phpunit parameter --bootstrap if executing single test case classes.
 *
 * For easy access to the PHPUnit and VFS framework, it is recommended to install the phpunit TYPO3 Extension
 * It does not need to be activated, nor a cli user needs to be present.
 * But it is also possible to use other installations of PHPUnit and VFS
 *
 *  * Call whole unit test suite, example:
 * - cd /var/www/t3master/foo  # Document root of TYPO3 CMS instance (location of index.php)
 * - typo3conf/ext/phpunit/Composer/vendor/bin/phpunit -c typo3/sysext/core/Build/UnitTests.xml
 *
 * Call single test case, example:
 * - cd /var/www/t3master/foo  # Document root of TYPO3 CMS instance (location of index.php)
 * - typo3conf/ext/phpunit/Composer/vendor/bin/phpunit \
 *     --bootstrap typo3/sysext/core/Build/UnitTestsBootstrap.php \
 *     typo3/sysext/core/Tests/Uinit/DataHandling/DataHandlerTest.php
 */

/**
 * Be nice and give a hint if someone is executing the tests with cli dispatch
 */
if (defined('TYPO3_MODE')) {
	array_shift($_SERVER['argv']);
	echo 'Please run the unit tests using the following command:' . chr(10);
	echo sprintf(
		'typo3conf/ext/phpunit/Composer/vendor/bin/phpunit %s',
		implode(' ', $_SERVER['argv'])
	) . chr(10);
	echo chr(10);
	exit(1);
}

/**
 * Find out web path by environment variable or current working directory
 */
if (getenv('TYPO3_PATH_WEB')) {
	$webRoot = getenv('TYPO3_PATH_WEB') . '/';
} else {
	$webRoot = getcwd() . '/';
}
$webRoot = strtr($webRoot, '\\', '/');

/**
 * Define basic TYPO3 constants
 */
define('PATH_site', $webRoot);
define('TYPO3_MODE', 'BE');
define('TYPO3_cliMode', TRUE);

unset($webRoot);

/**
 * We need to fake the current script to be the cli dispatcher to satisfy some GeneralUtility::getIndpEnv tests
 * @todo properly mock these tests
 */
define('PATH_thisScript', PATH_site . 'typo3/cli_dispatch.phpsh');
$_SERVER['SCRIPT_NAME'] = PATH_thisScript;

putenv('TYPO3_CONTEXT=Testing');

createDirectory(PATH_site . 'uploads');
createDirectory(PATH_site . 'typo3temp');
createDirectory(PATH_site . 'typo3conf/ext');

require PATH_site . '/typo3/sysext/core/Classes/Core/Bootstrap.php';

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->baseSetup()
	->initializeClassLoader();

$configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager();
$GLOBALS['TYPO3_CONF_VARS'] = $configurationManager->getDefaultConfiguration();

// Avoid failing tests that rely on HTTP_HOST retrieval
// @todo Check if we could do better mocking in these tests
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->disableCoreAndClassesCache()
	->initializeCachingFramework()
	->initializeClassLoaderCaches()
	->initializePackageManagement(\TYPO3\CMS\Core\Package\UnitTestPackageManager::class);

/**
 * Creates directory (recursively if required).
 *
 * @param  string $directory path of the directory to be created
 * @return void
 * @throws \RuntimeException
 */
function createDirectory($directory) {
	if (is_dir($directory)) {
		return;
	}

	if (!mkdir($directory, 0777, TRUE)) {
		throw new \RuntimeException(
			'Directory "' . $directory . '" could not be created',
			1404038665
		);
	}
}
