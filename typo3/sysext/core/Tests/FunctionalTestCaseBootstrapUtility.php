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
 *  * # cd /var/www/t3master/foo  # Document root of CMS instance, fileadmin/ directory and frontend index.php are here
  * #  ./typo3conf/ext/phpunit/Composer/vendor/bin/phpunit -c typo3/sysext/core/Build/FunctionalTests.xml # Call functional tests

 */
class FunctionalTestCaseBootstrapUtility {

	/**
	 * @var string Identifier calculated from test case class
	 */
	static protected $identifier;

	/**
	 * @var string Absolute path to test instance document root
	 */
	static protected $instancePath;

	/**
	 * @var string Name of test database
	 */
	static protected $databaseName;

	/**
	 * Set up creates a test database and fills with data.
	 *
	 * This method should be called with parent::setUp() in your test cases!
	 *
	 * @param string $testCaseClassName Name of test case class
	 * @param array $coreExtensionsToLoad Array of core extensions to load
	 * @param array $testExtensionsToLoad Array of test extensions to load
	 * @return void
	 */
	public function setUp(
		$testCaseClassName,
		array $coreExtensionsToLoad,
		array $testExtensionsToLoad
	) {
		$this->setUpIdentifier($testCaseClassName);
		$this->setUpInstancePath();
		$this->removeOldInstanceIfExists();
		$this->setUpInstanceDirectories();
		$this->setUpInstanceCoreLinks();
		$this->linkTestExtensionsToInstance($testExtensionsToLoad);
		$this->setUpLocalConfiguration($coreExtensionsToLoad, $testExtensionsToLoad);
		$this->setUpBasicTypo3Bootstrap();
		$this->setUpTestDatabase();
		$this->createDatabaseStructure();
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(TRUE);
	}

	/**
	 * Tear down.
	 *
	 * This method should be called with parent::setUp() in your test cases!
	 *
	 * @throws Exception
	 * @return void
	 */
	public function tearDown() {
		if (empty(static::$identifier)) {
			throw new Exception(
				'Test identifier not set. Is parent::setUp() called in setUp()?',
				1376739702
			);
		}
		$this->tearDownTestDatabase();
		$this->removeInstance();
	}

	/**
	 * Calculate a "unique" identifier for the test database and the
	 * instance patch based on the given test case class name.
	 *
	 * As a result, the database name will be identical between different
	 * test runs, but different between each test case.
	 */
	protected function setUpIdentifier($testCaseClassName) {
		// 7 characters of sha1 should be enough for a unique identification
		static::$identifier = substr(sha1($testCaseClassName), 0, 7);
	}

	/**
	 * Calculates path to TYPO3 CMS test installation for this test case.
	 *
	 * @return void
	 */
	protected function setUpInstancePath() {
		static::$instancePath = ORIGINAL_ROOT . 'typo3temp/functional-' . static::$identifier;
	}

	/**
	 * Remove test instance folder structure in setUp() if it exists.
	 * This may happen if a functional test before threw a fatal.
	 *
	 * @return void
	 */
	protected function removeOldInstanceIfExists() {
		if (is_dir(static::$instancePath)) {
			$this->removeInstance();
		}
	}

	/**
	 * Create folder structure of test instance.
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function setUpInstanceDirectories() {
		$foldersToCreate = array(
			'',
			'/fileadmin',
			'/typo3temp',
			'/typo3conf',
			'/typo3conf/ext',
			'/uploads'
		);
		foreach ($foldersToCreate as $folder) {
			$success = mkdir(static::$instancePath . $folder);
			if (!$success) {
				throw new Exception(
					'Creating directory failed: ' . static::$instancePath . $folder,
					1376657189
				);
			}
		}
	}

	/**
	 * Link TYPO3 CMS core from "parent" instance.
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function setUpInstanceCoreLinks() {
		$linksToSet = array(
			ORIGINAL_ROOT . 'typo3' => static::$instancePath . '/typo3',
			ORIGINAL_ROOT . 'index.php' => static::$instancePath . '/index.php'
		);
		foreach ($linksToSet as $from => $to) {
			$success = symlink($from,  $to);
			if (!$success) {
				throw new Exception(
					'Creating link failed: from ' . $from . ' to: ' . $to,
					1376657199
				);
			}
		}
	}

	/**
	 * Link test extensions to the typo3conf/ext folder of the instance.
	 *
	 * @param array $extensionPaths Contains paths to extensions relative to document root
	 * @throws Exception
	 * @return void
	 */
	protected function linkTestExtensionsToInstance(array $extensionPaths) {
		foreach ($extensionPaths as $extensionPath) {
			if (!is_dir($extensionPath)) {
				throw new Exception(
					'Test extension path ' . $extensionPath . ' not found',
					1376745645
				);
			}
			$absoluteExtensionPath = ORIGINAL_ROOT . $extensionPath;
			$destinationPath = static::$instancePath . '/typo3conf/ext/'. basename($absoluteExtensionPath);
			$success = symlink($absoluteExtensionPath, $destinationPath);
			if (!$success) {
				throw new Exception(
					'Can not link extension folder: ' . $absoluteExtensionPath . ' to ' . $destinationPath,
					1376657142
				);
			}
		}
	}

	/**
	 * Create LocalConfiguration.php file in the test instance
	 *
	 * @param array $coreExtensionsToLoad Additional core extensions to load
	 * @param array $testExtensionPaths Paths to extensions relative to document root
	 * @throws Exception
	 * @return void
	 */
	protected function setUpLocalConfiguration(array $coreExtensionsToLoad, array $testExtensionPaths) {
		$originalConfigurationArray = require ORIGINAL_ROOT . 'typo3conf/LocalConfiguration.php';
		// Base of final LocalConfiguration is core factory configuration
		$finalConfigurationArray = require ORIGINAL_ROOT .'typo3/sysext/core/Configuration/FactoryConfiguration.php';

		$finalConfigurationArray['DB'] = $originalConfigurationArray['DB'];
		// Calculate and set new database name
		static::$databaseName = $originalConfigurationArray['DB']['database'] . '_test_' . static::$identifier;
		$finalConfigurationArray['DB']['database'] = static::$databaseName;

		// Determine list of additional extensions to load
		$extensionNamesOfTestExtensions = array();
		foreach ($testExtensionPaths as $path) {
			$extensionNamesOfTestExtensions[] = basename($path);
		}
		$extensionsToLoad = array_merge($coreExtensionsToLoad, $extensionNamesOfTestExtensions);
		$finalConfigurationArray['EXT']['extListArray'] = $extensionsToLoad;

		$result = $this->writeFile(
			static::$instancePath . '/typo3conf/LocalConfiguration.php',
			'<?php' . chr(10) .
			'return ' .
			$this->arrayExport(
				$finalConfigurationArray
			) .
			';' . chr(10) .
			'?>'
		);
		if (!$result) {
			throw new Exception('Can not write local configuration', 1376657277);
		}
	}

	/**
	 * Bootstrap basic TYPO3
	 *
	 * @return void
	 */
	protected function setUpBasicTypo3Bootstrap() {
		$_SERVER['PWD'] = static::$instancePath;
		$_SERVER['argv'][0] = 'index.php';

		define('TYPO3_MODE', 'BE');
		define('TYPO3_cliMode', TRUE);

		require static::$instancePath . '/typo3/sysext/core/Classes/Core/CliBootstrap.php';
		\TYPO3\CMS\Core\Core\CliBootstrap::checkEnvironmentOrDie();

		require static::$instancePath . '/typo3/sysext/core/Classes/Core/Bootstrap.php';
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
			->baseSetup('')
			->loadConfigurationAndInitialize(FALSE)
			->loadTypo3LoadedExtAndExtLocalconf(FALSE)
			->applyAdditionalConfigurationSettings();
	}

	/**
	 * Populate $GLOBALS['TYPO3_DB'] and create test database
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 * @return void
	 */
	protected function setUpTestDatabase() {
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeTypo3DbGlobal();
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
		$database = $GLOBALS['TYPO3_DB'];
		$database->sql_pconnect();
		// @TODO: Test if database exists already and drop if so (eg. because of a fatal)
		$createDatabaseResult = $database->admin_query('CREATE DATABASE `' . static::$databaseName . '`');
		if (!$createDatabaseResult) {
			throw new Exception(
				'Unable to create database with name ' . static::$databaseName . ' permission problem?',
				1376579070
			);
		}
		$database->setDatabaseName(static::$databaseName);
		$database->sql_select_db(static::$databaseName);
	}

	/**
	 * Create tables and import static rows
	 *
	 * @return void
	 */
	protected function createDatabaseStructure() {
		/** @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService $schemaMigrationService */
		$schemaMigrationService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		/** @var \TYPO3\CMS\Install\Service\SqlExpectedSchemaService $expectedSchemaService */
		$expectedSchemaService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlExpectedSchemaService');

		// Raw concatenated ext_tables.sql and friends string
		$expectedSchemaString = $expectedSchemaService->getTablesDefinitionString(TRUE);
		$statements = $schemaMigrationService->getStatementArray($expectedSchemaString, TRUE);
		list($_, $insertCount) = $schemaMigrationService->getCreateTables($statements, TRUE);

		$fieldDefinitionsFile = $schemaMigrationService->getFieldDefinitions_fileContent($expectedSchemaString);
		$fieldDefinitionsDatabase = $schemaMigrationService->getFieldDefinitions_database();
		$difference = $schemaMigrationService->getDatabaseExtra($fieldDefinitionsFile, $fieldDefinitionsDatabase);
		$updateStatements = $schemaMigrationService->getUpdateSuggestions($difference);

		$schemaMigrationService->performUpdateQueries($updateStatements['add'], $updateStatements['add']);
		$schemaMigrationService->performUpdateQueries($updateStatements['change'], $updateStatements['change']);
		$schemaMigrationService->performUpdateQueries($updateStatements['create_table'], $updateStatements['create_table']);

		foreach ($insertCount as $table => $count) {
			$insertStatements = $schemaMigrationService->getTableInsertStatements($statements, $table);
			foreach ($insertStatements as $insertQuery) {
				$insertQuery = rtrim($insertQuery, ';');
				/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
				$database = $GLOBALS['TYPO3_DB'];
				$database->admin_query($insertQuery);
			}
		}
	}

	/**
	 * Drop test database.
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 * @return void
	 */
	protected function tearDownTestDatabase() {
		$result = $GLOBALS['TYPO3_DB']->admin_query('DROP DATABASE `' . static::$databaseName . '`');
		if (!$result) {
			throw new Exception(
				'Dropping test database ' . static::$databaseName . ' failed',
				1376583188
			);
		}
	}

	/**
	 * Removes instance directories and files
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 * @return void
	 */
	protected function removeInstance() {
		$success = $this->rmdir(static::$instancePath, TRUE);
		if (!$success) {
			throw new Exception(
				'Can not remove folder: ' . static::$instancePath,
				1376657210
			);
		}
	}

	/**
	 * COPIED FROM GeneralUtility
	 *
	 * Wrapper function for rmdir, allowing recursive deletion of folders and files
	 *
	 * @param string $path Absolute path to folder, see PHP rmdir() function. Removes trailing slash internally.
	 * @param boolean $removeNonEmpty Allow deletion of non-empty directories
	 * @return boolean TRUE if @rmdir went well!
	 */
	protected function rmdir($path, $removeNonEmpty = FALSE) {
		$OK = FALSE;
		// Remove trailing slash
		$path = preg_replace('|/$|', '', $path);
		if (file_exists($path)) {
			$OK = TRUE;
			if (!is_link($path) && is_dir($path)) {
				if ($removeNonEmpty == TRUE && ($handle = opendir($path))) {
					while ($OK && FALSE !== ($file = readdir($handle))) {
						if ($file == '.' || $file == '..') {
							continue;
						}
						$OK = $this->rmdir($path . '/' . $file, $removeNonEmpty);
					}
					closedir($handle);
				}
				if ($OK) {
					$OK = @rmdir($path);
				}
			} else {
				// If $path is a file, simply remove it
				$OK = unlink($path);
			}
			clearstatcache();
		} elseif (is_link($path)) {
			$OK = unlink($path);
			clearstatcache();
		}
		return $OK;
	}

	/**
	 * COPIED FROM GeneralUtility
	 *
	 * Writes $content to the file $file
	 *
	 * @param string $file Filepath to write to
	 * @param string $content Content to write
	 * @return boolean TRUE if the file was successfully opened and written to.
	 */
	protected function writeFile($file, $content) {
		if ($fd = fopen($file, 'wb')) {
			$res = fwrite($fd, $content);
			fclose($fd);
			if ($res === FALSE) {
				return FALSE;
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * COPIED FROM ArrayUtility
	 *
	 * Exports an array as string.
	 * Similar to var_export(), but representation follows the TYPO3 core CGL.
	 *
	 * See unit tests for detailed examples
	 *
	 * @param array $array Array to export
	 * @param integer $level Internal level used for recursion, do *not* set from outside!
	 * @return string String representation of array
	 * @throws \RuntimeException
	 */
	protected function arrayExport(array $array = array(), $level = 0) {
		$lines = 'array(' . chr(10);
		$level++;
		$writeKeyIndex = FALSE;
		$expectedKeyIndex = 0;
		foreach ($array as $key => $value) {
			if ($key === $expectedKeyIndex) {
				$expectedKeyIndex++;
			} else {
				// Found a non integer or non consecutive key, so we can break here
				$writeKeyIndex = TRUE;
				break;
			}
		}
		foreach ($array as $key => $value) {
			// Indention
			$lines .= str_repeat(chr(9), $level);
			if ($writeKeyIndex) {
				// Numeric / string keys
				$lines .= is_int($key) ? $key . ' => ' : '\'' . $key . '\' => ';
			}
			if (is_array($value)) {
				if (count($value) > 0) {
					$lines .= $this->arrayExport($value, $level);
				} else {
					$lines .= 'array(),' . chr(10);
				}
			} elseif (is_int($value) || is_float($value)) {
				$lines .= $value . ',' . chr(10);
			} elseif (is_null($value)) {
				$lines .= 'NULL' . ',' . chr(10);
			} elseif (is_bool($value)) {
				$lines .= $value ? 'TRUE' : 'FALSE';
				$lines .= ',' . chr(10);
			} elseif (is_string($value)) {
				// Quote \ to \\
				$stringContent = str_replace('\\', '\\\\', $value);
				// Quote ' to \'
				$stringContent = str_replace('\'', '\\\'', $stringContent);
				$lines .= '\'' . $stringContent . '\'' . ',' . chr(10);
			} else {
				throw new \RuntimeException('Objects are not supported', 1342294986);
			}
		}
		$lines .= str_repeat(chr(9), ($level - 1)) . ')' . ($level - 1 == 0 ? '' : ',' . chr(10));
		return $lines;
	}
}
?>