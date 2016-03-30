<?php
/*
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
 * Run whole core unit test suite, example:
 * - cd /var/www/t3master/foo  # Document root of TYPO3 CMS instance (location of index.php)
 * - typo3/../bin/phpunit -c typo3/sysext/core/Build/UnitTests.xml
 *
 * Run single test case, example:
 * - cd /var/www/t3master/foo  # Document root of TYPO3 CMS instance (location of index.php)
 * - typo3/../bin/phpunit -c typo3/sysext/core/Build/UnitTests.xml
 *     typo3/sysext/core/Tests/Unit/DataHandling/DataHandlerTest.php
 */
call_user_func(function () {
    $testbase = new \TYPO3\CMS\Core\Tests\Testbase();
    $testbase->enableDisplayErrors();
    $testbase->defineBaseConstants();
    $testbase->defineSitePath();
    $testbase->defineTypo3ModeBe();
    $testbase->setTypo3TestingContext();
    $testbase->createDirectory(PATH_site . 'uploads');
    $testbase->createDirectory(PATH_site . 'typo3temp/var/tests');
    $testbase->createDirectory(PATH_site . 'typo3temp/var/transient');
    $testbase->createDirectory(PATH_site . 'typo3conf/ext');

    // Retrieve an instance of class loader and inject to core bootstrap
    $classLoaderFilepath = __DIR__ . '/../../../../vendor/autoload.php';
    if (!file_exists($classLoaderFilepath)) {
        die('ClassLoader can\'t be loaded. Please check your path or set an environment variable \'TYPO3_PATH_WEB\' to your root path.');
    }
    $classLoader = require $classLoaderFilepath;
    \TYPO3\CMS\Core\Core\Bootstrap::getInstance()
        ->initializeClassLoader($classLoader)
        ->setRequestType(TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI)
        ->baseSetup();

    // Initialize default TYPO3_CONF_VARS
    $configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager();
    $GLOBALS['TYPO3_CONF_VARS'] = $configurationManager->getDefaultConfiguration();
    // Avoid failing tests that rely on HTTP_HOST retrieval
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';

    \TYPO3\CMS\Core\Core\Bootstrap::getInstance()
        ->disableCoreCache()
        ->initializeCachingFramework()
        // Set all packages to active
        ->initializePackageManagement(\TYPO3\CMS\Core\Package\UnitTestPackageManager::class)
        // Find all class names
        ->ensureClassLoadingInformationExists();
});
