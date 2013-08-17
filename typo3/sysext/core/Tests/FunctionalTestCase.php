<?php
namespace TYPO3\CMS\Core\Tests;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Base test case class for functional tests, all TYPO3 CMS
 * functional tests should extend from this class!
 *
 * # cd /var/www/t3master/foo  # Document root of CMS instance, fileadmin/ directory and frontend index.php are here
 * #  ./typo3conf/ext/phpunit/Composer/vendor/bin/phpunit -c typo3/sysext/core/Build/FunctionalTests.xml # Call functional tests
 */
abstract class FunctionalTestCase extends BaseTestCase {

	/**
	 * Core extensions to load.
	 *
	 * If the test case needs additional core extensions as requirement,
	 * they can be noted here and will be added to LocalConfiguration
	 * extension list and ext_tables.sql of those extensions will be applied.
	 *
	 * Required core extensions like core, cms, extbase and so on are loaded
	 * automatically, so there is no need to add them here. See constant
	 * REQUIRED_EXTENSIONS for a list of automatically loaded extensions.
	 *
	 * @var array
	 */
	protected $coreExtensionsToLoad = array('foo');

	/**
	 * Array of test/fixture extensions paths that should be loaded for a test.
	 *
	 * Given path is expected to be relative to your document root, example:
	 *
	 * array(
	 *   'typo3conf/ext/someExtension/Tests/Functional/Fixtures/Extensions/testExtension',
	 *   'typo3conf/ext/baseExtension',
	 * );
	 *
	 * Extensions in this array are linked to the test instance, loaded
	 * and their ext_tables.sql will be applied.
	 *
	 * Example:
	 * - typo3conf/ext/fooExt/Tests/Functional/Utility/FooUtilityTest.php
	 * - typo3conf/ext/fooExt/Tests/Functional/Utility/Fixtures/Extensions/fooUtilityTestExtension/
	 * - typo3conf/ext/fooExt/Tests/Functional/Utility/Fixtures/Extensions/fooUtilityTestExtension/ext_localconf.php
	 *
	 * @var array
	 */
	protected $testExtensionsToLoad = array('typo3conf/ext/lw_enet_multiple_action_forms');

	/**
	 * Set up creates a test database and fills with data.
	 *
	 * This method should be called with parent::setUp() in your test cases!
	 *
	 * @return void
	 */
	public function setUp() {
		if (!defined('ORIGINAL_ROOT')) {
			$this->markTestSkipped('Functional tests must be called through phpunit on CLI');
		}
		$bootstrapUtility = new FunctionalTestCaseBootstrapUtility();
		$bootstrapUtility->setUp(get_class($this), $this->coreExtensionsToLoad, $this->testExtensionsToLoad);
	}

	/**
	 * Tear down.
	 *
	 * This method should be called with parent::setUp() in your test cases!
	 *
	 * @return void
	 */
	public function tearDown() {
		$bootstrapUtility = new FunctionalTestCaseBootstrapUtility();
		$bootstrapUtility->tearDown();
	}

	/**
	 * Returns absolute path to the Fixture folder if called with empty
	 * $relativeFixturePath, returns path to a fixture file otherwise.
	 *
	 * Fixtures are expected to be located in a sub-folder called "Fixtures"
	 * below the test case PHP file.
	 *
	 * @param string $relativeFixtureFile
	 * @return string Absolute path with trailing slash
	 */
	protected function getFixturePath($relativeFixtureFile = '') {
		$reflectionClass = new \ReflectionClass(get_class($this));
		$fileLocationOfTestClass = $reflectionClass->getFileName();
		$path = dirname($fileLocationOfTestClass) . '/Fixtures/' . $relativeFixtureFile;
		return $path;
	}
}
?>