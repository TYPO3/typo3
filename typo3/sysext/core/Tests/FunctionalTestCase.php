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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

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
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection Database object to test database
	 */
	private $databaseConnection = NULL;

	/**
	 * Array of core extension names this test depends on
	 *
	 * @var array
	 */
	protected $requiredExtensions = array('cms');

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
	protected $testInstallationPath = '';

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

        //TODO: create local configuration file
		//TODO: call basic bootstrap
		$this->setUpTestDatabaseConnection();
//		$this->createDatabaseStructure();

		//TODO: rest of the bootstrap (e.g. analyze ext_localconf)
	}

	/**
	 * Tear down.
	 *
	 * This method should be called with parent::setUp() in your test cases!
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
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
	 */
	private function calculateTestInstallationPath() {
		$this->testInstallationPath = ORIGINAL_ROOT . '/typo3temp/'. md5(__CLASS__);
	}

	/**
	 * Creates folder structure of the test installation and link TYPO3 core
	 */
	protected function setUpTestInstallationFolderStructure() {
		$neededFolders = array(
			'',
			'/fileadmin',
			'/typo3temp',
			'/typo3conf',
			'/typo3conf/ext',
			'/uploads'
		);
		foreach($neededFolders as $folder) {
			$success = mkdir($this->testInstallationPath . $folder);
			if(!$success) {
				throw new Exception('Can not create directory: '. $this->testInstallationPath . $folder, 1376657189);
			}
		}

		$neededLinks  = array(
			'/typo3' => '/typo3',
			'/index.php' => '/index.php'
		);
		foreach($neededLinks as $from => $to) {
			$success = symlink(ORIGINAL_ROOT . $from, $this->testInstallationPath . $to);
			if(!$success) {
				throw new Exception('Can not link file : '. ORIGINAL_ROOT . $from . ' to: ' .$this->testInstallationPath . $to, 1376657199);
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
		$this->testDatabaseName = uniqid(strtolower(TYPO3_db . '_test_'));
		unset($GLOBALS['TYPO3_DB']);
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeTypo3DbGlobal();
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
		$this->databaseConnection->sql_pconnect();
		$createDatabaseResult = $this->databaseConnection->admin_query('CREATE DATABASE `' . $this->testDatabaseName . '`');
		if (!$createDatabaseResult) {
			throw new Exception(
				'Unable to create database with name ' . $this->testDatabaseName . ' permission problem?',
				1376579070
			);
		}
		$this->databaseConnection->setDatabaseName($this->testDatabaseName);
		$this->databaseConnection->sql_select_db($this->testDatabaseName);
	}

	/**
	 * Drop the test database.
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 * @return void
	 */
	private function tearDownTestDatabase() {
		$result = $this->databaseConnection->admin_query('DROP DATABASE `' . $this->testDatabaseName . '`');
		if (!$result) {
			throw new Exception(
				'Dropping test database failed',
				1376583188
			);
		}
	}

	/**
	 * Removes test installation folder
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 */
	private function tearDownTestInstallationFolder() {
		$success = $this->rmdir($this->testInstallationPath, TRUE);
		if(!$success) {
			throw new Exception('Can not remove folder: '. $this->testInstallationPath, 1376657210);
		}
	}

	/**
	 * Sets the TYPO3 database instance to a test database.
	 *
	 * Note: This function does not back up the currently TYPO3 database instance.
	 *
	 * @param string $databaseName
	 *        the name of the test database to use; if none is provided, the
	 *        name of the current TYPO3 database plus a suffix "_test" is used
	 *
	 * @return DatabaseConnection the test database
	 */
	protected function useTestDatabase($databaseName = NULL) {
		/** @var $db DatabaseConnection */
		$db = $GLOBALS['TYPO3_DB'];

		if ($db->sql_select_db($databaseName ? $databaseName : $this->testDatabaseName) !== TRUE) {
			$this->markTestSkipped('This test is skipped because the test database is not available.');
		}

		return $db;
	}

	/**
	 * Imports the ext_tables.sql statements from the given extensions.
	 *
	 * @param array $extensions
	 *        keys of the extensions to import, may be empty
	 * @param boolean $importDependencies
	 *        whether to import dependency extensions on which the given extensions
	 *        depend as well
	 * @param array &$skipDependencies
	 *        keys of the extensions to skip, may be empty, will be modified
	 *
	 * @return void
	 */
	protected function importExtensions(
		array $extensions, $importDependencies = FALSE, array &$skipDependencies = array()
	) {
		$this->useTestDatabase();

		foreach ($extensions as $extensionName) {
			if (!ExtensionManagementUtility::isLoaded($extensionName)) {
				$this->markTestSkipped(
					'This test is skipped because the extension ' . $extensionName .
						' which was marked for import is not loaded on your system!'
				);
			} elseif (in_array($extensionName, $skipDependencies)) {
				continue;
			}

			$skipDependencies = array_merge($skipDependencies, array($extensionName));

			if ($importDependencies) {
				$dependencies = $this->findDependencies($extensionName);
				if (is_array($dependencies)) {
					$this->importExtensions($dependencies, TRUE, $skipDependencies);
				}
			}

			$this->importExtension($extensionName);
		}

		// TODO: The hook should be replaced by real clean up and rebuild the whole
		// "TYPO3_CONF_VARS" in order to have a clean testing environment.
		// hook to load additional files
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['phpunit']['importExtensions_additionalDatabaseFiles'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['phpunit']['importExtensions_additionalDatabaseFiles'] as $file) {
				$sqlFilename = GeneralUtility::getFileAbsFileName($file);
				$fileContent = GeneralUtility::getUrl($sqlFilename);

				$this->importDatabaseDefinitions($fileContent);
			}
		}
	}

	/**
	 * Gets the names of all tables in the database with the given name.
	 *
	 * @param string $databaseName
	 *        the name of the database from which to retrieve the table names,
	 *        if none is provided, the name of the current TYPO3 database plus a
	 *        suffix "_test" is used
	 *
	 * @return array<string>
	 *        the names of all tables in the database $databaseName, might be empty
	 */
	protected function getDatabaseTables($databaseName = NULL) {
		$db = $this->useTestDatabase($databaseName);

		$tableNames = array();

		$res = $db->sql_query('show tables');
		while ($row = $db->sql_fetch_row($res)) {
			$tableNames[] = $row[0];
		}

		return $tableNames;
	}

	/**
	 * Imports the ext_tables.sql file of the extension with the given name
	 * into the test database.
	 *
	 * @param string $extensionName
	 *        the name of the installed extension to import, must not be empty
	 *
	 * @return void
	 */
	private function importExtension($extensionName) {
		$sqlFilename = GeneralUtility::getFileAbsFileName(ExtensionManagementUtility::extPath($extensionName) . 'ext_tables.sql');
		$fileContent = GeneralUtility::getUrl($sqlFilename);

		$this->importDatabaseDefinitions($fileContent);
	}

//	/**
//	 * Imports the data from the stddb tables.sql file.
//	 *
//	 * Example/intended usage:
//	 *
//	 * <pre>
//	 * public function setUp() {
//	 *   $this->createTestDatabase();
//	 *   $db = $this->useTestDatabase();
//	 *   $this->importStdDB();
//	 *   $this->importExtensions(array('cms', 'static_info_tables', 'templavoila'));
//	 * }
//	 * </pre>
//	 *
//	 * @return void
//	 */
//	protected function createDatabaseStructure() {
//		//TODO: FIXME!!
//		$sqlFilename = GeneralUtility::getFileAbsFileName(PATH_t3lib . 'stddb/tables.sql');
//		$fileContent = GeneralUtility::getUrl($sqlFilename);
//
//		$this->importDatabaseDefinitions($fileContent);
//
//		if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 4006000) {
//			// make sure missing caching framework tables don't get into the way
//			$cacheTables = \TYPO3\CMS\Core\Cache\Cache::getDatabaseTableDefinitions();
//			$this->importDatabaseDefinitions($cacheTables);
//		}
//	}

	/**
	 * Create tables and import static rows
	 *
	 * @return void
	 */
	protected function createDatabaseStructure() {
		// Will load ext_localconf and ext_tables. This is pretty safe here since we are
		// in first install (database empty), so it is very likely that no extension is loaded
		// that could trigger a fatal at this point.
		$this->loadExtLocalconfDatabaseAndExtTables();

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
				$this->testDatabaseConnection->admin_query($insertQuery);
			}
		}

	}

	/**
	 * Some actions like the database analyzer and the upgrade wizards need additional
	 * bootstrap actions performed.
	 *
	 * Those actions can potentially fatal if some old extension is loaded that triggers
	 * a fatal in ext_localconf or ext_tables code! Use only if really needed.
	 *
	 * @return void
	 */
	protected function loadExtLocalconfDatabaseAndExtTables() {
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
			->loadTypo3LoadedExtAndExtLocalconf(FALSE)
//			->applyAdditionalConfigurationSettings()
//			->initializeTypo3DbGlobal()
			->loadExtensionTables(FALSE);
	}

	/**
	 * Imports the SQL definitions from a (ext_)tables.sql file.
	 *
	 * @param string $definitionContent
	 *        the SQL to import, must not be empty
	 *
	 * @return void
	 */
	private function importDatabaseDefinitions($definitionContent) {
		if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 4006000) {
			/* @var $install \TYPO3\CMS\Install\Service\SqlSchemaMigrationService */
			$install = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		} else {
			/* @var $install \TYPO3\CMS\Install\Service\SqlSchemaMigrationService */
			$install = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		}

		$fieldDefinitionsFile = $install->getFieldDefinitions_fileContent($definitionContent);
		if (empty($fieldDefinitionsFile)) {
			return;
		}

		// find statements to query
		$fieldDefinitionsDatabase = $install->getFieldDefinitions_fileContent($this->getTestDatabaseSchema());
		$diff = $install->getDatabaseExtra($fieldDefinitionsFile, $fieldDefinitionsDatabase);
		$updateStatements = $install->getUpdateSuggestions($diff);

		$updateTypes = array('add', 'change', 'create_table');

		foreach ($updateTypes as $updateType) {
			if (array_key_exists($updateType, $updateStatements)) {
				foreach ((array) $updateStatements[$updateType] as $string) {
					$GLOBALS['TYPO3_DB']->admin_query($string);
				}
			}
		}
	}

	/**
	 * Returns an SQL dump of the test database.
	 *
	 * @return string SQL dump of the test databse, might be empty
	 */
	private function getTestDatabaseSchema() {
		$db = $this->useTestDatabase();
		$tables = $this->getDatabaseTables();

		// finds create statement for every table
		$linefeed = chr(10);

		$schema = '';
		$db->sql_query('SET SQL_QUOTE_SHOW_CREATE = 0');
		foreach ($tables as $tableName) {
			$res = $db->sql_query('show create table ' . $tableName);
			$row = $db->sql_fetch_row($res);

			// modifies statement to be accepted by TYPO3
			$createStatement = preg_replace('/ENGINE.*$/', '', $row[1]);
			$createStatement = preg_replace(
				'/(CREATE TABLE.*\()/', $linefeed . '\\1' . $linefeed, $createStatement
			);
			$createStatement = preg_replace('/\) $/', $linefeed . ')', $createStatement);

			$schema .= $createStatement . ';';
		}

		return $schema;
	}

	/**
	 * Finds all direct dependencies of the extension with the key $extKey.
	 *
	 * @param string $extKey the key of an installed extension, must not be empty
	 *
	 * @return array<string>|NULL
	 *         the keys of all extensions on which the given extension depends,
	 *         will be NULL if the dependencies could not be determined
	 */
	private function findDependencies($extKey) {
		$path = GeneralUtility::getFileAbsFileName(ExtensionManagementUtility::extPath($extKey) . 'ext_emconf.php');
		$_EXTKEY = $extKey;
		include($path);

		$dependencies = $GLOBALS['EM_CONF'][$_EXTKEY]['constraints']['depends'];
		if (!is_array($dependencies)) {
			return NULL;
		}

		// remove php and typo3 extension (not real extensions)
		if (isset($dependencies['php'])) {
			unset($dependencies['php']);
		}
		if (isset($dependencies['typo3'])) {
			unset($dependencies['typo3']);
		}

		return array_keys($dependencies);
	}

	/**
	 * Imports a data set into the test database,
	 *
	 * @param string $path
	 *        the absolute path to the XML file containing the data set to load
	 *
	 * @return void
	 */
	protected function importDataSet($path) {
		$xml = simplexml_load_file($path);
		$db = $this->useTestDatabase();
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
			$db->exec_INSERTquery($tableName, $insertArray);

			if (isset($table['id'])) {
				$elementId = (string) $table['id'];
				$foreignKeys[$tableName][$elementId] = $db->sql_insert_id();
			}
		}
	}

	/**
	 * Copy all needed test extensions to the typo3conf/ext folder of the test installation
	 *
	 * @param array $extensionNames array containing extension names (name should be the same as a folder name)
	 */
	protected function copyMultipleTestExtensionsToExtFolder(array $extensionNames) {
		foreach($extensionNames as $extensionName) {
			$extensionPath = $this->getFixtureExtensionPath($extensionName);
			$this->copyTestExtensionToExtFolder($extensionPath);
		}
	}

	/**
	 * Copy single single test extension to the typo3conf/ext folder of the test installation
	 *
	 * @param string $sourceFolderPath absolute path to extension
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 */
	protected function copyTestExtensionToExtFolder($sourceFolderPath) {
		if (!stristr(PHP_OS, 'darwin') && stristr(PHP_OS, 'win')) {
			//windows
			$sourceFolderPath = rtrim($sourceFolderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			$files = GeneralUtility::getAllFilesAndFoldersInPath(array(), $sourceFolderPath, '', TRUE);
			$files = GeneralUtility::removePrefixPathFromList($files, $sourceFolderPath);

			foreach ($files as $fileName) {
				$destinationPath = $this->testInstallationPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext'. DIRECTORY_SEPARATOR . $fileName;
				$success = copy($sourceFolderPath . $fileName, $destinationPath);
				if(!$success) {
					throw new Exception('Can not copy file: '. $fileName . ' to '. $destinationPath, 1376657187);
				}
			}
		} else {
			//linux
			$destinationPath = $this->testInstallationPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext'. basename($sourceFolderPath);
			$success = symlink($sourceFolderPath, $destinationPath);
			if(!$success) {
				throw new Exception('Can not link folder: '. $sourceFolderPath . ' to '. $destinationPath, 1376657187);
			}
		}
	}


	/**
	 * Returns absolute path to the fixture
	 * if called with empty $relativeFixturePath, returns path to the base folder for fixtures
	 *
	 * @param string $relativeFixturePath
	 * @return string absolute path with trailing slash
	 */
	protected function getFixturePath($relativeFixturePath = '') {
		$relativeFixturePath = !empty($relativeFixturePath) ? $relativeFixturePath . DIRECTORY_SEPARATOR : '';
		$path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR .  $relativeFixturePath;
		return $path;
	}

	/**
	 * Returns absolute path to the fixture extension
	 * if called with empty name, returns path to the base folder for test extensions
	 *
	 * @param string $name
	 * @return string absolute path with trailing slash
	 */
	protected function getFixtureExtensionPath($name = '') {
		$name = !empty($name) ? $name . DIRECTORY_SEPARATOR : '';
		$path = $this->getFixturePath() .'extensions'. DIRECTORY_SEPARATOR . $name;
		return $path;
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
						$OK = self::rmdir($path . '/' . $file, $removeNonEmpty);
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
}
?>