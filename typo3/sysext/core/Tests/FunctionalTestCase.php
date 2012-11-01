<?php
namespace TYPO3\CMS\Core\Tests;

/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2012 Michael Klapper <michael.klapper@aoemedia.de>
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
 * Base test case for functional tests.
 *
 * This class currently only inherits the base test case. However, it is recommended
 * to extend this class for unit test cases instead of the base test case because if,
 * at some point, specific behavior needs to be implemented for unit tests, your test cases
 * will profit from it automatically.
 *
 * @author Michael Klapper <michael.klapper@aoemedia.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
abstract class FunctionalTestCase extends UnitTestCase {
	/**
	 * Name of the database used for testing
	 *
	 * @var string
	 */
	private $databaseName;

	/**
	 * Gets the name of the database used for testing.
	 *
	 * @return string
	 */
	protected function getDatabaseName() {
		if (!isset($this->databaseName)) {
			$this->databaseName = strtolower(TYPO3_db . '_test');
		}

		return $this->databaseName;
	}

	/**
	 * Selects the TYPO3 database (again).
	 *
	 * If you have selected any non-TYPO3 in your unit tests, you need to
	 * call this function in tearDown() in order to avoid problems with the
	 * following unit tests and the TYPO3 back-end.
	 *
	 * @return void
	 */
	protected function switchToTypo3Database() {
		$this->getDatabaseConnection()->sql_select_db(TYPO3_db);
	}

	/**
	 * Accesses the TYPO3 database instance and uses it to fetch the list of
	 * available databases. Then this function creates a test database (if none
	 * has been set up yet).
	 *
	 * @return boolean FALSE if something went wrong
	 */
	protected function createDatabase() {
		$success = TRUE;

		$this->dropDatabase();

		if (!$this->hasDatabase()) {
			$result = $this->getDatabaseConnection()->admin_query(
				'CREATE DATABASE ' . $this->getDatabaseName()
			);

			if ($result === FALSE) {
				$success = FALSE;
			}
		}

		return $success;
	}

	/**
	 * Drops all tables in the test database.
	 *
	 * @return void
	 */
	protected function cleanDatabase() {
		if (!$this->hasDatabase()) {
			return;
		}

		$this->getDatabaseConnection()->sql_select_db($this->getDatabaseName());

		foreach ($this->getDatabaseTables() as $databaseTableName) {
			$this->getDatabaseConnection()->admin_query(
				'DROP TABLE ' . $databaseTableName
			);
		}
	}

	/**
	 * Drops the test database.
	 *
	 * @return boolean FALSE if database could not be dropped
	 */
	protected function dropDatabase() {
		if (!$this->hasDatabase()) {
			return TRUE;
		}

		$this->getDatabaseConnection()->sql_select_db($this->getDatabaseName());

		$result = $this->getDatabaseConnection()->admin_query(
			'DROP DATABASE ' . $this->getDatabaseName()
		);

		return ($result !== FALSE);
	}

	/**
	 * Sets the TYPO3 database instance to a test database.
	 *
	 * Note: This function does not back up the currenty TYPO3 database instance.
	 *
	 * @param string $databaseName
	 *        the name of the test database to use; if none is provided, the
	 *        name of the current TYPO3 database plus a suffix "_test" is used
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection the test database
	 */
	protected function useTestDatabase($databaseName = NULL) {
		$result = $this->getDatabaseConnection()->sql_select_db(
			$databaseName ? $databaseName : $this->getDatabaseName()
		);

		if ($result !== TRUE) {
			$this->markTestSkipped('This test is skipped because the test database is not available.');
		}

		return $this->getDatabaseConnection();
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
	protected function importExtensions(array $extensions, $importDependencies = FALSE, array &$skipDependencies = array()) {
		$this->useTestDatabase();

		foreach ($extensions as $extensionName) {
			if (!\TYPO3\CMS\Core\Extension\ExtensionManager::isLoaded($extensionName)) {
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
				$sqlFilename = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file);
				$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sqlFilename);

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
		$databaseConnection = $this->useTestDatabase($databaseName);
		$tables = $databaseConnection->admin_get_tables();
		$tableNames = array_keys($tables);

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
		$sqlFilename = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
			\TYPO3\CMS\Core\Extension\ExtensionManager::extPath($extensionName) . 'ext_tables.sql'
		);
		$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sqlFilename);

		$this->importDatabaseDefinitions($fileContent);
	}

	/**
	 * Imports the data from the stddb tables.sql file.
	 *
	 * Example/intended usage:
	 *
	 * <pre>
	 * public function setUp() {
	 *   $this->createDatabase();
	 *   $db = $this->useTestDatabase();
	 *   $this->importStdDB();
	 *   $this->importExtensions(array('cms', 'static_info_tables', 'templavoila'));
	 * }
	 * </pre>
	 *
	 * @return void
	 */
	protected function importStdDb() {
		$sqlFilename = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
			PATH_t3lib . 'stddb/tables.sql'
		);
		$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sqlFilename);

		$this->importDatabaseDefinitions($fileContent);

		if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 4006000) {
			// make sure missing caching framework tables don't get into the way
			$cacheTables = \TYPO3\CMS\Core\Cache\Cache::getDatabaseTableDefinitions();
			$this->importDatabaseDefinitions($cacheTables);
		}
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
		/* @var $install \TYPO3\CMS\Install\Sql\SchemaMigrator */
		$install = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_install_Sql');
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
	 * @return string SQL dump of the test database, might be empty
	 */
	private function getTestDatabaseSchema() {
		$databaseConnection = $this->useTestDatabase();
		$tables = $this->getDatabaseTables();

		// finds create statement for every table
		$linefeed = chr(10);

		$schema = '';
		$databaseConnection->sql_query('SET SQL_QUOTE_SHOW_CREATE = 0');

		foreach ($tables as $tableName) {
			$res = $databaseConnection->sql_query('SHOW CREATE TABLE ' . $tableName);
			$row = $databaseConnection->sql_fetch_row($res);

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
		$path = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
			\TYPO3\CMS\Core\Extension\ExtensionManager::extPath($extKey) . 'ext_emconf.php'
		);

		$EM_CONF = array();
		$_EXTKEY = $extKey;
		include($path);

		$dependencies = $EM_CONF[$_EXTKEY]['constraints']['depends'];
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

		/** @var $table SimpleXMLElement */
		foreach ($xml->children() as $table) {
			$insertArray = array();

			/** @var $column SimpleXMLElement */
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
	 * Determines whether the test database exists.
	 *
	 * @return boolean
	 */
	protected function hasDatabase() {
		$databaseNames = $this->getDatabaseConnection()->admin_get_dbs();
		return (in_array($this->getDatabaseName(), $databaseNames));
	}

	/**
	 * Creates a new database record.
	 *
	 * @param string $tableName
	 * @param array $record
	 * @return integer|NULL
	 */
	protected function createRecord($tableName, array $record) {
		$identifier = NULL;

		$result = $this->getDatabaseConnection()->exec_INSERTquery(
			$tableName,
			$record
		);

		if (!empty($result)) {
			$identifier = (int) $this->getDatabaseConnection()->sql_insert_id();
		}

		return $identifier;
	}

	/**
	 * Gets the database connection object.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
?>