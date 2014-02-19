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
 * Utility class to set up and bootstrap TYPO3 CMS for functional tests
 */
class FunctionalTestCaseBootstrapUtility {

	/**
	 * @var string Identifier calculated from test case class
	 */
	protected $identifier;

	/**
	 * @var string Absolute path to test instance document root
	 */
	protected $instancePath;

	/**
	 * @var string Name of test database
	 */
	protected $databaseName;

	/**
	 * @var string Name of original database
	 */
	protected $originalDatabaseName;

	/**
	 * @var array These extensions are always loaded
	 */
	protected $defaultActivatedCoreExtensions = array(
		'core',
		'backend',
		'frontend',
		'cms',
		'lang',
		'sv',
		'extensionmanager',
		'recordlist',
		'extbase',
		'fluid',
		'cshmanual',
		'install',
		'saltedpasswords'
	);

	/**
	 * Set up creates a test instance and database.
	 *
	 * @param string $testCaseClassName Name of test case class
	 * @param array $coreExtensionsToLoad Array of core extensions to load
	 * @param array $testExtensionsToLoad Array of test extensions to load
	 * @param array $pathsToLinkInTestInstance Array of source => destination path pairs to be linked
	 * @return string Path to TYPO3 CMS test installation for this test case
	 */
	public function setUp(
		$testCaseClassName,
		array $coreExtensionsToLoad,
		array $testExtensionsToLoad,
		array $pathsToLinkInTestInstance
	) {
		$this->setUpIdentifier($testCaseClassName);
		$this->setUpInstancePath();
		$this->removeOldInstanceIfExists();
		$this->setUpInstanceDirectories();
		$this->setUpInstanceCoreLinks();
		$this->linkTestExtensionsToInstance($testExtensionsToLoad);
		$this->linkPathsInTestInstance($pathsToLinkInTestInstance);
		$this->setUpLocalConfiguration();
		$this->setUpPackageStates($coreExtensionsToLoad, $testExtensionsToLoad);
		$this->setUpBasicTypo3Bootstrap();
		$this->setUpTestDatabase();
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(TRUE);
		$this->createDatabaseStructure();

		return $this->instancePath;
	}

	/**
	 * Tear down destroys the instance and database.
	 *
	 * @throws Exception
	 * @return void
	 */
	public function tearDown() {
		if (empty($this->identifier)) {
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
		$this->identifier = substr(sha1($testCaseClassName), 0, 7);
	}

	/**
	 * Calculates path to TYPO3 CMS test installation for this test case.
	 *
	 * @return void
	 */
	protected function setUpInstancePath() {
		$this->instancePath = ORIGINAL_ROOT . 'typo3temp/functional-' . $this->identifier;
	}

	/**
	 * Remove test instance folder structure in setUp() if it exists.
	 * This may happen if a functional test before threw a fatal.
	 *
	 * @return void
	 */
	protected function removeOldInstanceIfExists() {
		if (is_dir($this->instancePath)) {
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
			$success = mkdir($this->instancePath . $folder);
			if (!$success) {
				throw new Exception(
					'Creating directory failed: ' . $this->instancePath . $folder,
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
			ORIGINAL_ROOT . 'typo3' => $this->instancePath . '/typo3',
			ORIGINAL_ROOT . 'index.php' => $this->instancePath . '/index.php'
		);
		foreach ($linksToSet as $from => $to) {
			$success = symlink($from, $to);
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
			$destinationPath = $this->instancePath . '/typo3conf/ext/'. basename($absoluteExtensionPath);
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
	 * Link paths inside the test instance, e.g. from a fixture fileadmin subfolder to the
	 * test instance fileadmin folder
	 *
	 * @param array $pathsToLinkInTestInstance Contains paths as array of source => destination in key => value pairs of folders relative to test instance root
	 * @throws \TYPO3\CMS\Core\Tests\Exception if a source path could not be found
	 * @throws \TYPO3\CMS\Core\Tests\Exception on failing creating the symlink
	 * @return void
	 * @see \TYPO3\CMS\Core\Tests\FunctionalTestCase::$pathsToLinkInTestInstance
	 */
	protected function linkPathsInTestInstance(array $pathsToLinkInTestInstance) {
		foreach ($pathsToLinkInTestInstance as $sourcePathToLinkInTestInstance => $destinationPathToLinkInTestInstance) {
			$sourcePath = $this->instancePath . '/' . ltrim($sourcePathToLinkInTestInstance, '/');
			if (!file_exists($sourcePath)) {
				throw new Exception(
					'Path ' . $sourcePath . ' not found',
					1376745645
				);
			}
			$destinationPath = $this->instancePath . '/' . ltrim($destinationPathToLinkInTestInstance, '/');
			$success = symlink($sourcePath, $destinationPath);
			if (!$success) {
				throw new Exception(
					'Can not link the path ' . $sourcePath . ' to ' . $destinationPath,
					1389969623
				);
			}
		}
	}

	/**
	 * Create LocalConfiguration.php file in the test instance
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function setUpLocalConfiguration() {
		$originalConfigurationArray = require ORIGINAL_ROOT . 'typo3conf/LocalConfiguration.php';
		// Base of final LocalConfiguration is core factory configuration
		$finalConfigurationArray = require ORIGINAL_ROOT .'typo3/sysext/core/Configuration/FactoryConfiguration.php';

		$finalConfigurationArray['DB'] = $originalConfigurationArray['DB'];
		// Calculate and set new database name
		$this->originalDatabaseName = $originalConfigurationArray['DB']['database'];
		$this->databaseName = $this->originalDatabaseName . '_ft' . $this->identifier;

		// Maximum database name length for mysql is 64 characters
		if (strlen($this->databaseName) > 64) {
			$maximumOriginalDatabaseName = 64 - strlen('_ft' . $this->identifier);
			throw new Exception(
				'The name of the database that is used for the functional test (' . $this->databaseName . ')' .
				' exceeds the maximum length of 64 character allowed by MySQL. You have to shorten your' .
				' original database name to ' . $maximumOriginalDatabaseName . ' characters',
				1377600104
			);
		}

		$finalConfigurationArray['DB']['database'] = $this->databaseName;

		$result = $this->writeFile(
			$this->instancePath . '/typo3conf/LocalConfiguration.php',
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
	 * Compile typo3conf/PackageStates.php containing default packages like core,
	 * a functional test specific list of additional core extensions, and a list of
	 * test extensions.
	 *
	 * @param array $coreExtensionsToLoad Additional core extensions to load
	 * @param array $testExtensionPaths Paths to extensions relative to document root
	 * @throws Exception
	 * @TODO Figure out what the intention of the upper arguments is
	 */
	protected function setUpPackageStates(array $coreExtensionsToLoad, array $testExtensionPaths) {
		$packageStates = array(
			'packages' => array(),
			'version' => 4,
		);

		// Register default list of extensions and set active
		foreach ($this->defaultActivatedCoreExtensions as $extensionName) {
			$packageStates['packages'][$extensionName] = array(
				'state' => 'active',
				'packagePath' => 'typo3/sysext/' . $extensionName . '/',
				'classesPath' => 'Classes/',
			);
		}

		// Register additional core extensions and set active
		foreach ($coreExtensionsToLoad as $extensionName) {
			if (isset($packageSates['packages'][$extensionName])) {
				throw new Exception(
					$extensionName . ' is already registered as default core extension to load, no need to load it explicitly',
					1390913893
				);
			}
			$packageStates['packages'][$extensionName] = array(
				'state' => 'active',
				'packagePath' => 'typo3/sysext/' . $extensionName . '/',
				'classesPath' => 'Classes/',
			);
		}

		// Activate test extensions that have been symlinked before
		foreach ($testExtensionPaths as $extensionPath) {
			if (isset($packageSates['packages'][$extensionName])) {
				throw new Exception(
					$extensionName . ' is already registered as extension to load, no need to load it explicitly',
					1390913894
				);
			}
			$extensionName = basename($extensionPath);
			$packageStates['packages'][$extensionName] = array(
				'state' => 'active',
				'packagePath' => 'typo3conf/ext/' . $extensionName . '/',
				'classesPath' => 'Classes/',
			);
		}

		$result = $this->writeFile(
			$this->instancePath . '/typo3conf/PackageStates.php',
			'<?php' . chr(10) .
			'return ' .
			$this->arrayExport(
				$packageStates
			) .
			';' . chr(10) .
			'?>'
		);
		if (!$result) {
			throw new Exception('Can not write PackageStates', 1381612729);
		}
	}

	/**
	 * Bootstrap basic TYPO3
	 *
	 * @return void
	 */
	protected function setUpBasicTypo3Bootstrap() {
		$_SERVER['PWD'] = $this->instancePath;
		$_SERVER['argv'][0] = 'index.php';

		define('TYPO3_MODE', 'BE');
		define('TYPO3_cliMode', TRUE);

		require_once $this->instancePath . '/typo3/sysext/core/Classes/Core/CliBootstrap.php';
		\TYPO3\CMS\Core\Core\CliBootstrap::checkEnvironmentOrDie();

		require_once $this->instancePath . '/typo3/sysext/core/Classes/Core/Bootstrap.php';
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
		if(!$database->sql_pconnect()) {
			throw new Exception(
				'TYPO3 Fatal Error: The current username, password or host was not accepted when the'
				. ' connection to the database was attempted to be established!',
				1377620117
			);
		}

		// Drop database in case a previous test had a fatal and did not clean up properly
		$database->admin_query('DROP DATABASE IF EXISTS `' . $this->databaseName . '`');
		$createDatabaseResult = $database->admin_query('CREATE DATABASE `' . $this->databaseName . '`');
		if (!$createDatabaseResult) {
			$user = $GLOBALS['TYPO3_CONF_VARS']['DB']['username'];
			$host = $GLOBALS['TYPO3_CONF_VARS']['DB']['host'];
			throw new Exception(
				'Unable to create database with name ' . $this->databaseName . '. This is probably a permission problem.'
				. ' For this instance this could be fixed executing'
				. ' "GRANT ALL ON `' . $this->originalDatabaseName . '_ft%`.* TO `' . $user . '`@`' . $host . '`;"',
				1376579070
			);
		}
		$database->setDatabaseName($this->databaseName);
		$database->sql_select_db($this->databaseName);
	}

	/**
	 * Create tables and import static rows
	 *
	 * @return void
	 */
	protected function createDatabaseStructure() {
		/** @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService $schemaMigrationService */
		$schemaMigrationService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var \TYPO3\CMS\Install\Service\SqlExpectedSchemaService $expectedSchemaService */
		$expectedSchemaService = $objectManager->get('TYPO3\\CMS\\Install\\Service\\SqlExpectedSchemaService');

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
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
		$database = $GLOBALS['TYPO3_DB'];
		$result = $database->admin_query('DROP DATABASE `' . $this->databaseName . '`');
		if (!$result) {
			throw new Exception(
				'Dropping test database ' . $this->databaseName . ' failed',
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
		$success = $this->rmdir($this->instancePath, TRUE);
		if (!$success) {
			throw new Exception(
				'Can not remove folder: ' . $this->instancePath,
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
