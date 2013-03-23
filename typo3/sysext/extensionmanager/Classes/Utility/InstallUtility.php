<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <susanne.moog@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Extension Manager Install Utility
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class InstallUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	public $objectManager;

	/**
	 * @var \TYPO3\CMS\Install\Sql\SchemaMigrator
	 */
	public $installToolSqlParser;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility
	 */
	protected $dependencyUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
	 */
	protected $fileHandlingUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 */
	protected $listUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DatabaseUtility
	 */
	protected $databaseUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 */
	public $extensionRepository;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
	 * @return void
	 */
	public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $filehandlingUtility
	 * @return void
	 */
	public function injectFileHandlingUtility(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility) {
		$this->fileHandlingUtility = $fileHandlingUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility
	 * @return void
	 */
	public function injectDependencyUtility(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility) {
		$this->dependencyUtility = $dependencyUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\DatabaseUtility $databaseUtility
	 * @return void
	 */
	public function injectDatabaseUtility(\TYPO3\CMS\Extensionmanager\Utility\DatabaseUtility $databaseUtility) {
		$this->databaseUtility = $databaseUtility;
	}

	/**
	 * Inject emConfUtility
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
	 * @return void
	 */
	public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * __construct
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var $installToolSqlParser \TYPO3\CMS\Install\Sql\SchemaMigrator */
		$this->installToolSqlParser = $this->objectManager->get('TYPO3\\CMS\\Install\\Sql\\SchemaMigrator');
		$this->dependencyUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\DependencyUtility');
	}

	/**
	 * Helper function to install an extension
	 * also processes db updates and clears the cache if the extension asks for it
	 *
	 * @param string $extensionKey
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function install($extensionKey) {
		$extension = $this->enrichExtensionWithDetails($extensionKey);
		$this->processDatabaseUpdates($extension);
		$this->ensureConfiguredDirectoriesExist($extension);
		if ($extension['clearcacheonload']) {
			$GLOBALS['typo3CacheManager']->flushCaches();
		}
		if (!$this->isLoaded($extensionKey)) {
			$this->loadExtension($extensionKey);
		}
		$this->reloadCaches();
		$this->processCachingFrameworkUpdates();
		$this->saveDefaultConfiguration($extension['key']);
	}

	/**
	 * Helper function to uninstall an extension
	 *
	 * @param string $extensionKey
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function uninstall($extensionKey) {
		$dependentExtensions = $this->dependencyUtility->findInstalledExtensionsThatDependOnMe($extensionKey);
		if (is_array($dependentExtensions) && count($dependentExtensions) > 0) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Cannot deactivate extension ' . $extensionKey . ' - The extension(s) ' . implode(',', $dependentExtensions) . ' depend on it', 1342554622);
		} else {
			$this->unloadExtension($extensionKey);
		}
	}

	/**
	 * Wrapper function to check for loaded extensions
	 *
	 * @param string $extensionKey
	 * @return boolean TRUE if extension is loaded
	 */
	public function isLoaded($extensionKey) {
		return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey);
	}

	/**
	 * Wrapper function for loading extensions
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	protected function loadExtension($extensionKey) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtension($extensionKey);
	}

	/**
	 * Wrapper function for unloading extensions
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	protected function unloadExtension($extensionKey) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::unloadExtension($extensionKey);
	}

	/**
	 * Checks if an extension is available in the system
	 *
	 * @param $extensionKey
	 * @return boolean
	 */
	public function isAvailable($extensionKey) {
		$availableExtensions = $this->listUtility->getAvailableExtensions();
		return array_key_exists($extensionKey, $availableExtensions);
	}

	/**
	 * Fetch additional information for an extension key
	 *
	 * @param string $extensionKey
	 * @access private
	 * @return array
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 */
	public function enrichExtensionWithDetails($extensionKey) {
		$availableExtensions = $this->listUtility->getAvailableExtensions();
		if (isset($availableExtensions[$extensionKey])) {
			$extension = $availableExtensions[$extensionKey];
		} else {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Extension ' . $extensionKey . ' is not available', 1342864081);
		}
		$availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation(array($extensionKey => $extension));
		return $availableAndInstalledExtensions[$extensionKey];
	}

	/**
	 * Creates directories as requested in ext_emconf.php
	 *
	 * @param array $extension
	 */
	protected function ensureConfiguredDirectoriesExist(array $extension) {
		$this->fileHandlingUtility->ensureConfiguredDirectoriesExist($extension);
	}

	/**
	 * Gets the content of the ext_tables.sql and ext_tables_static+adt.sql files
	 * Additionally adds the table definitions for the cache tables
	 *
	 * @param array $extension
	 */
	public function processDatabaseUpdates(array $extension) {
		$extTablesSqlFile = PATH_site . $extension['siteRelPath'] . '/ext_tables.sql';
		$extTablesSqlContent = '';
		if (file_exists($extTablesSqlFile)) {
			$extTablesSqlContent .= \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($extTablesSqlFile);
		}
		if ($extTablesSqlContent !== '') {
			$this->updateDbWithExtTablesSql($extTablesSqlContent);
		}
		$extTablesStaticSqlFile = PATH_site . $extension['siteRelPath'] . '/ext_tables_static+adt.sql';
		if (file_exists($extTablesStaticSqlFile)) {
			$extTablesStaticSqlContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($extTablesStaticSqlFile);
			$this->importStaticSql($extTablesStaticSqlContent);
		}
	}

	/**
	 * Gets all registered caches and creates required caching framework tables.
	 *
	 * @return void
	 */
	protected function processCachingFrameworkUpdates() {
		$extTablesSqlContent = '';

		// @TODO: This should probably moved to TYPO3\CMS\Core\Cache\Cache->getDatabaseTableDefinitions ?!
		$GLOBALS['typo3CacheManager']->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
		$extTablesSqlContent .= \TYPO3\CMS\Core\Cache\Cache::getDatabaseTableDefinitions();

		if ($extTablesSqlContent !== '') {
			$this->updateDbWithExtTablesSql($extTablesSqlContent);
		}
	}

	/**
	 * Reload Cache files and Typo3LoadedExtensions
	 *
	 * @return void
	 */
	public function reloadCaches() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::removeCacheFiles();
		// Set new extlist / extlistArray for extension load changes at runtime
		/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		$localConfiguration = $configurationManager->getLocalConfiguration();
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray'] = $localConfiguration['EXT']['extListArray'];
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = implode(',', $GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray']);
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->reloadTypo3LoadedExtAndClassLoaderAndExtLocalconf();
	}

	/**
	 * Save default configuration of an extension
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	protected function saveDefaultConfiguration($extensionKey) {
		/** @var $configUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility */
		$configUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');
		$configUtility->saveDefaultConfiguration($extensionKey);
	}

	/**
	 * Update database / process db updates from ext_tables
	 *
	 * @param string $rawDefinitions The raw SQL statements from ext_tables.sql
	 * @return void
	 */
	public function updateDbWithExtTablesSql($rawDefinitions) {
		$fieldDefinitionsFromFile = $this->installToolSqlParser->getFieldDefinitions_fileContent($rawDefinitions);
		if (count($fieldDefinitionsFromFile)) {
			$fieldDefinitionsFromCurrentDatabase = $this->installToolSqlParser->getFieldDefinitions_database();
			$diff = $this->installToolSqlParser->getDatabaseExtra($fieldDefinitionsFromFile, $fieldDefinitionsFromCurrentDatabase);
			$updateStatements = $this->installToolSqlParser->getUpdateSuggestions($diff);
			foreach ((array) $updateStatements['add'] as $string) {
				$GLOBALS['TYPO3_DB']->admin_query($string);
			}
			foreach ((array) $updateStatements['change'] as $string) {
				$GLOBALS['TYPO3_DB']->admin_query($string);
			}
			foreach ((array) $updateStatements['create_table'] as $string) {
				$GLOBALS['TYPO3_DB']->admin_query($string);
			}
		}
	}

	/**
	 * Import static SQL data (normally used for ext_tables_static+adt.sql)
	 *
	 * @param string $rawDefinitions
	 * @return void
	 */
	public function importStaticSql($rawDefinitions) {
		$statements = $this->installToolSqlParser->getStatementarray($rawDefinitions, 1);
		list($statementsPerTable, $insertCount) = $this->installToolSqlParser->getCreateTables($statements, 1);
		// Traverse the tables
		foreach ($statementsPerTable as $table => $query) {
			$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS ' . $table);
			$GLOBALS['TYPO3_DB']->admin_query($query);
			if ($insertCount[$table]) {
				$insertStatements = $this->installToolSqlParser->getTableInsertStatements($statements, $table);
				foreach ($insertStatements as $statement) {
					$GLOBALS['TYPO3_DB']->admin_query($statement);
				}
			}
		}
	}

	/**
	 * Remove an extension (delete the directory)
	 *
	 * @param string $extension
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function removeExtension($extension) {
		$absolutePath = $this->fileHandlingUtility->getAbsoluteExtensionPath($extension);
		if ($this->fileHandlingUtility->isValidExtensionPath($absolutePath)) {
			$this->fileHandlingUtility->removeDirectory($absolutePath);
		} else {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('No valid extension path given.', 1342875724);
		}
	}

	/**
	 * Get the data dump for an extension
	 *
	 * @param string $extension
	 * @return array
	 */
	public function getExtensionSqlDataDump($extension) {
		$extension = $this->enrichExtensionWithDetails($extension);
		$filePrefix = PATH_site . $extension['siteRelPath'];
		$sqlData['extTables'] = $this->getSqlDataDumpForFile($filePrefix . '/ext_tables.sql');
		$sqlData['staticSql'] = $this->getSqlDataDumpForFile($filePrefix . '/ext_tables_static+adt.sql');
		return $sqlData;
	}

	/**
	 * Gets the sql data dump for a specific sql file (for example ext_tables.sql)
	 *
	 * @param string $sqlFile
	 * @return string
	 */
	protected function getSqlDataDumpForFile($sqlFile) {
		$sqlData = '';
		if (file_exists($sqlFile)) {
			$sqlContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sqlFile);
			$fieldDefinitions = $this->installToolSqlParser->getFieldDefinitions_fileContent($sqlContent);
			$sqlData = $this->databaseUtility->dumpStaticTables($fieldDefinitions);
		}
		return $sqlData;
	}

	/**
	 * Checks if an update for an extension is available
	 *
	 * @internal
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionData
	 * @return boolean
	 */
	public function isUpdateAvailable(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionData) {
		// Only check for update for TER extensions
		$version = $extensionData->getIntegerVersion();
		/** @var $highestTerVersionExtension \TYPO3\CMS\Extensionmanager\Domain\Model\Extension */
		$highestTerVersionExtension = $this->extensionRepository->findHighestAvailableVersion($extensionData->getExtensionKey());
		if ($highestTerVersionExtension instanceof \TYPO3\CMS\Extensionmanager\Domain\Model\Extension) {
			$highestVersion = $highestTerVersionExtension->getIntegerVersion();
			if ($highestVersion > $version) {
				return TRUE;
			}
		}
		return FALSE;
	}

}


?>