<?php
namespace TYPO3\CMS\Core\Tests;

/***************************************************************
 * Copyright notice
 *
 * (c) 2005-2013 Robert Lemke (robert@typo3.org)
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base test case for functional tests.
 *
 * Functional tests should extend this class. It provides methods to create
 * a new database with base data and methods to fiddle with test data.
 */
abstract class FunctionalTestCase extends BaseTestCase {

	/**
	 * @var string Name of test database - Private since test cases must not fiddle with this!
	 */
	private $testDatabaseName;

	/**
	 * Array of core extension names this test depends on
	 *
	 * @var array
	 */
	protected $requiredExtensions = array();

	/**
	 * Array of test/fixture extension names this test depends on
	 *
	 * @var array
	 */
	protected $requiredTestExtensions = array();

	/**
	 * Absolute path to the test installation root folder
	 *
	 * @var string
	 */
	private $testInstallationPath;

	/**
	 * Set up creates a test database and fills with data.
	 *
	 * This method should be called with parent::setUp() in your test cases!
	 *
	 * @return void
	 */
	public function setUp() {
		$this->calculateTestInstallationPath();
		$this->setUpTestInstallationFolderStructure();
		$this->copyMultipleTestExtensionsToExtFolder($this->requiredTestExtensions);
		$this->setUpLocalConfiguration();
		$this->setUpBasicTypo3Bootstrap();
		$this->setUpTestDatabaseConnection();
		$this->createDatabaseStructure();
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(TRUE);
	}

	/**
	 * Tear down.
	 *
	 * This method should be called with parent::setUp() in your test cases!
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 * @return void
	 */
	public function tearDown() {
		if (empty($this->testDatabaseName)) {
			throw new Exception(
				'Test database name not set. parent::setUp called?',
				1376579421
			);
		}
		$this->tearDownTestDatabase();
		$this->tearDownTestInstallationFolder();
	}

	/**
	 * Calculates path to the test TYPO3 installation
	 *
	 * @return void
	 */
	private function calculateTestInstallationPath() {
		// @TODO: same id for filesystem & database name
		$this->testInstallationPath = ORIGINAL_ROOT . '/typo3temp/'. uniqid('functional');
	}

	/**
	 * Calculates test database name based on original database name
	 *
	 * @param string $originalDatabaseName Name of original database
	 * @return void
	 */
	private function calculateTestDatabaseName($originalDatabaseName) {
		// @TODO: same id for filesystem & database name
		$this->testDatabaseName = uniqid(strtolower($originalDatabaseName . '_test_'));
	}

	/**
	 * Creates folder structure of the test installation and link TYPO3 core
	 *
	 * @throws Exception
	 * @return void
	 */
	private function setUpTestInstallationFolderStructure() {
		$neededFolders = array(
			'',
			'/fileadmin',
			'/typo3temp',
			'/typo3conf',
			'/typo3conf/ext',
			'/uploads'
		);
		foreach ($neededFolders as $folder) {
			$success = mkdir($this->testInstallationPath . $folder);
			if (!$success) {
				throw new Exception('Can not create directory: ' . $this->testInstallationPath . $folder, 1376657189);
			}
		}

		$neededLinks = array(
			'/typo3' => '/typo3',
			'/index.php' => '/index.php'
		);
		foreach ($neededLinks as $from => $to) {
			$success = symlink(ORIGINAL_ROOT . $from, $this->testInstallationPath . $to);
			if (!$success) {
				throw new Exception('Can not link file : ' . ORIGINAL_ROOT . $from . ' to: ' . $this->testInstallationPath . $to, 1376657199);
			}
		}
	}

	/**
	 * Create new $GLOBALS['TYPO3_DB'] on test database
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 * @return void
	 */
	private function setUpTestDatabaseConnection() {
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeTypo3DbGlobal();
		$GLOBALS['TYPO3_DB']->sql_pconnect();
		$createDatabaseResult = $GLOBALS['TYPO3_DB']->admin_query('CREATE DATABASE `' . $this->testDatabaseName . '`');
		if (!$createDatabaseResult) {
			throw new Exception(
				'Unable to create database with name ' . $this->testDatabaseName . ' permission problem?',
				1376579070
			);
		}
		$GLOBALS['TYPO3_DB']->setDatabaseName($this->testDatabaseName);
		$GLOBALS['TYPO3_DB']->sql_select_db($this->testDatabaseName);
	}

	/**
	 * Creates LocalConfiguration.php file in the test installation
	 *
	 * @return void
	 */
	private function setUpLocalConfiguration() {
		$localConfigurationFile = $this->testInstallationPath . '/typo3conf/LocalConfiguration.php';
		$originalConfigurationArray = require ORIGINAL_ROOT . '/typo3conf/LocalConfiguration.php';
		$localConfigurationArray = require ORIGINAL_ROOT .'/typo3/sysext/core/Configuration/FactoryConfiguration.php';


		$additionalConfiguration = array('DB' => $originalConfigurationArray['DB']);
		$this->calculateTestDatabaseName($additionalConfiguration['DB']['database']);
		$additionalConfiguration['DB']['database'] = $this->testDatabaseName;
		$localConfigurationArray['DB'] = $additionalConfiguration['DB'];

		$extensions = array_merge($this->requiredExtensions, $this->requiredTestExtensions);
		$localConfigurationArray['EXT']['extListArray'] = $extensions;

		$result = $this->writeFile(
			$localConfigurationFile,
			'<?php' . chr(10) .
			'return ' .
			$this->arrayExport(
				$localConfigurationArray
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
	private function setUpBasicTypo3Bootstrap() {
		$_SERVER['PWD'] = $this->testInstallationPath;
		$_SERVER['argv'][0] = 'index.php';

		define('TYPO3_MODE', 'BE');
		define('TYPO3_cliMode', TRUE);

		require $this->testInstallationPath . '/typo3/sysext/core/Classes/Core/CliBootstrap.php';
		\TYPO3\CMS\Core\Core\CliBootstrap::checkEnvironmentOrDie();

		require $this->testInstallationPath . '/typo3/sysext/core/Classes/Core/Bootstrap.php';
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
			->baseSetup('')
			->loadConfigurationAndInitialize(FALSE)
			->loadTypo3LoadedExtAndExtLocalconf(FALSE)
			->applyAdditionalConfigurationSettings();
	}

	/**
	 * Drop the test database.
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 * @return void
	 */
	private function tearDownTestDatabase() {
		$result = $GLOBALS['TYPO3_DB']->admin_query('DROP DATABASE `' . $this->testDatabaseName . '`');
		if (!$result) {
			throw new Exception(
				'Dropping test database ' . $this->testDatabaseName . ' failed',
				1376583188
			);
		}
	}

	/**
	 * Removes test installation folder
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 * @return void
	 */
	private function tearDownTestInstallationFolder() {
		$success = $this->rmdir($this->testInstallationPath, TRUE);
		if (!$success) {
			throw new Exception('Can not remove folder: ' . $this->testInstallationPath, 1376657210);
		}
	}

	/**
	 * Create tables and import static rows
	 *
	 * @return void
	 */
	private function createDatabaseStructure() {
		/** @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService $schemaMigrationService */
		$schemaMigrationService = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		/** @var \TYPO3\CMS\Install\Service\SqlExpectedSchemaService $expectedSchemaService */
		$expectedSchemaService = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlExpectedSchemaService');

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
				$GLOBALS['TYPO3_DB']->admin_query($insertQuery);
			}
		}
	}

	/**
	 * Copy all needed test extensions to the typo3conf/ext folder of the test installation
	 *
	 * @param array $extensionNames array containing extension names (name should be the same as a folder name)
	 * @return void
	 */
	private function copyMultipleTestExtensionsToExtFolder(array $extensionNames) {
		foreach ($extensionNames as $extensionName) {
			$extensionPath = $this->getFixtureExtensionPath($extensionName);
			$this->copyTestExtensionToExtFolder($extensionPath);
		}
	}

	/**
	 * Copy single single test extension to the typo3conf/ext folder of the test installation
	 *
	 * @param string $sourceFolderPath absolute path to extension
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 * @return void
	 */
	private function copyTestExtensionToExtFolder($sourceFolderPath) {
		if (!stristr(PHP_OS, 'darwin') && stristr(PHP_OS, 'win')) {
			// Windows
			$sourceFolderPath = rtrim($sourceFolderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			$files = GeneralUtility::getAllFilesAndFoldersInPath(array(), $sourceFolderPath, '', TRUE);
			$files = GeneralUtility::removePrefixPathFromList($files, $sourceFolderPath);

			foreach ($files as $fileName) {
				$destinationPath = $this->testInstallationPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext'. DIRECTORY_SEPARATOR . $fileName;
				$success = copy($sourceFolderPath . $fileName, $destinationPath);
				if (!$success) {
					throw new Exception('Can not copy file: ' . $fileName . ' to ' . $destinationPath, 1376657187);
				}
			}
		} else {
			//linux
			$destinationPath = $this->testInstallationPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext'. DIRECTORY_SEPARATOR. basename($sourceFolderPath);
			$success = symlink($sourceFolderPath, $destinationPath);
			if (!$success) {
				throw new Exception('Can not link folder: ' . $sourceFolderPath . ' to ' . $destinationPath, 1376657187);
			}
		}
	}

	/**
	 * Returns absolute path to the fixture
	 * if called with empty $relativeFixturePath, returns path to the base folder for fixtures
	 *
	 * @param string $relativeFixturePath
	 * @return string absolute path with trailing slash
	 * @TODO: Figure out if this is useful
	 */
	protected function getFixturePath($relativeFixturePath = '') {
		$relativeFixturePath = !empty($relativeFixturePath) ? $relativeFixturePath . DIRECTORY_SEPARATOR : '';
		$path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . $relativeFixturePath;
		return $path;
	}

	/**
	 * Returns absolute path to the fixture extension
	 * if called with empty name, returns path to the base folder for test extensions
	 *
	 * @param string $name
	 * @return string absolute path with trailing slash
	 * @TODO: Figure out if this is useful
	 */
	protected function getFixtureExtensionPath($name = '') {
		$name = !empty($name) ? $name . DIRECTORY_SEPARATOR : '';
		$path = $this->getFixturePath() . 'extensions' . DIRECTORY_SEPARATOR . $name;
		return $path;
	}

	/**
	 * METHODS COPIED FROM GeneralUtility
	 */

	/**
	 * COPIED FROM GeneralUtility
	 *
	 * Wrapper function for rmdir, allowing recursive deletion of folders and files
	 *
	 * @param string $path Absolute path to folder, see PHP rmdir() function. Removes trailing slash internally.
	 * @param boolean $removeNonEmpty Allow deletion of non-empty directories
	 * @return boolean TRUE if @rmdir went well!
	 */
	private function rmdir($path, $removeNonEmpty = FALSE) {
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
	 * Writes $content to the file $file
	 *
	 * @param string $file Filepath to write to
	 * @param string $content Content to write
	 * @return boolean TRUE if the file was successfully opened and written to.
	 */
	private function writeFile($file, $content) {
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
	 * METHODS COPIED FROM ArrayUtility
	 */

	/**
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
	private function arrayExport(array $array = array(), $level = 0) {
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