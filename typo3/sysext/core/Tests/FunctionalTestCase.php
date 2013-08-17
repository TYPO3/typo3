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
	protected $coreExtensionsToLoad = array();

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
	protected $testExtensionsToLoad = array();

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
	 * Imports a data set represented as XML into the test database,
	 *
	 * @param string $path Absolute path to the XML file containing the data set to load
	 * @return void
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 */
	protected function importDataSet($path) {
		if (!is_file($path)) {
			throw new Exception(
				'Fixture file ' . $path . ' not found',
				1376746261
			);
		}

		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
		$database = $GLOBALS['TYPO3_DB'];

		$xml = simplexml_load_file($path);
		$foreignKeys = array();

		/** @var $table \SimpleXMLElement */
		foreach ($xml->children() as $table) {
			$insertArray = array();

			/** @var $column \SimpleXMLElement */
			foreach ($table->children() as $column) {
				$columnName = $column->getName();
				$columnValue = NULL;

				if (isset($column['ref'])) {
					list($tableName, $elementId) = explode('#', $column['ref']);
					$columnValue = $foreignKeys[$tableName][$elementId];
				} elseif (isset($column['is-NULL']) && ($column['is-NULL'] === 'yes')) {
					$columnValue = NULL;
				} else {
					$columnValue = $table->$columnName;
				}

				$insertArray[$columnName] = $columnValue;
			}

			$tableName = $table->getName();
			$database->exec_INSERTquery($tableName, $insertArray);

			if (isset($table['id'])) {
				$elementId = (string) $table['id'];
				$foreignKeys[$tableName][$elementId] = $database->sql_insert_id();
			}
		}
	}
}
?>