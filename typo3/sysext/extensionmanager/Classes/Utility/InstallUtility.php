<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Impexp\Utility\ImportExportUtility;

/**
 * Extension Manager Install Utility
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class InstallUtility implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    public $objectManager;

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
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     */
    public $extensionRepository;

    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     */
    protected $packageManager;

    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected $cacheManager;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var \TYPO3\CMS\Core\Registry
     */
    protected $registry;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility
     */
    public function injectDependencyUtility(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility)
    {
        $this->dependencyUtility = $dependencyUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
     */
    public function injectFileHandlingUtility(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility)
    {
        $this->fileHandlingUtility = $fileHandlingUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
     */
    public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @param \TYPO3\CMS\Core\Package\PackageManager $packageManager
     */
    public function injectPackageManager(\TYPO3\CMS\Core\Package\PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @param \TYPO3\CMS\Core\Cache\CacheManager $cacheManager
     */
    public function injectCacheManager(\TYPO3\CMS\Core\Cache\CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * @param \TYPO3\CMS\Core\Registry $registry
     */
    public function injectRegistry(\TYPO3\CMS\Core\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Helper function to install an extension
     * also processes db updates and clears the cache if the extension asks for it
     *
     * @param array $extensionKeys
     * @throws ExtensionManagerException
     */
    public function install(...$extensionKeys)
    {
        $flushCaches = false;
        foreach ($extensionKeys as $extensionKey) {
            $this->loadExtension($extensionKey);
            $extension = $this->enrichExtensionWithDetails($extensionKey, false);
            $this->saveDefaultConfiguration($extensionKey);
            if (!empty($extension['clearcacheonload']) || !empty($extension['clearCacheOnLoad'])) {
                $flushCaches = true;
            }
        }

        if ($flushCaches) {
            $this->cacheManager->flushCaches();
        } else {
            $this->cacheManager->flushCachesInGroup('system');
        }
        $this->reloadCaches();
        $this->updateDatabase($extensionKeys);

        foreach ($extensionKeys as $extensionKey) {
            $this->processExtensionSetup($extensionKey);
            $this->emitAfterExtensionInstallSignal($extensionKey);
        }
    }

    /**
     * @param string $extensionKey
     */
    public function processExtensionSetup($extensionKey)
    {
        $extension = $this->enrichExtensionWithDetails($extensionKey, false);
        $this->ensureConfiguredDirectoriesExist($extension);
        $this->importInitialFiles($extension['siteRelPath'] ?? '', $extensionKey);
        $this->importStaticSqlFile($extension['siteRelPath']);
        $this->importT3DFile($extension['siteRelPath']);
    }

    /**
     * Helper function to uninstall an extension
     *
     * @param string $extensionKey
     * @throws ExtensionManagerException
     */
    public function uninstall($extensionKey)
    {
        $dependentExtensions = $this->dependencyUtility->findInstalledExtensionsThatDependOnMe($extensionKey);
        if (is_array($dependentExtensions) && !empty($dependentExtensions)) {
            throw new ExtensionManagerException(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'extensionList.uninstall.dependencyError',
                    'extensionmanager',
                    [$extensionKey, implode(',', $dependentExtensions)]
                ),
                1342554622
            );
        }
        $this->unloadExtension($extensionKey);
    }

    /**
     * Wrapper function to check for loaded extensions
     *
     * @param string $extensionKey
     * @return bool TRUE if extension is loaded
     */
    public function isLoaded($extensionKey)
    {
        return $this->packageManager->isPackageActive($extensionKey);
    }

    /**
     * Reset and reload the available extensions
     */
    public function reloadAvailableExtensions()
    {
        $this->listUtility->reloadAvailableExtensions();
    }

    /**
     * Wrapper function for loading extensions
     *
     * @param string $extensionKey
     */
    protected function loadExtension($extensionKey)
    {
        $this->packageManager->activatePackage($extensionKey);
    }

    /**
     * Wrapper function for unloading extensions
     *
     * @param string $extensionKey
     */
    protected function unloadExtension($extensionKey)
    {
        $this->packageManager->deactivatePackage($extensionKey);
        $this->emitAfterExtensionUninstallSignal($extensionKey);
        $this->cacheManager->flushCachesInGroup('system');
    }

    /**
     * Emits a signal after an extension has been installed
     *
     * @param string $extensionKey
     */
    protected function emitAfterExtensionInstallSignal($extensionKey)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterExtensionInstall', [$extensionKey, $this]);
    }

    /**
     * Emits a signal after an extension has been uninstalled
     *
     * @param string $extensionKey
     */
    protected function emitAfterExtensionUninstallSignal($extensionKey)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterExtensionUninstall', [$extensionKey, $this]);
    }

    /**
     * Checks if an extension is available in the system
     *
     * @param string $extensionKey
     * @return bool
     */
    public function isAvailable($extensionKey)
    {
        return $this->packageManager->isPackageAvailable($extensionKey);
    }

    /**
     * Reloads the package information, if the package is already registered
     *
     * @param string $extensionKey
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException if the package isn't available
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException if an invalid package key was passed
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException if an invalid package path was passed
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackageManifestException if no extension configuration file could be found
     */
    public function reloadPackageInformation($extensionKey)
    {
        if ($this->packageManager->isPackageAvailable($extensionKey)) {
            $this->reloadOpcache();
            $this->packageManager->reloadPackageInformation($extensionKey);
        }
    }

    /**
     * Fetch additional information for an extension key
     *
     * @param string $extensionKey
     * @param bool $loadTerInformation
     * @internal
     * @return array
     * @throws ExtensionManagerException
     */
    public function enrichExtensionWithDetails($extensionKey, $loadTerInformation = true)
    {
        $extension = $this->getExtensionArray($extensionKey);
        if (!$loadTerInformation) {
            $availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfInformation([$extensionKey => $extension]);
        } else {
            $availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation([$extensionKey => $extension]);
        }

        if (!isset($availableAndInstalledExtensions[$extensionKey])) {
            throw new ExtensionManagerException(
                'Please check your uploaded extension "' . $extensionKey . '". The configuration file "ext_emconf.php" seems to be invalid.',
                1391432222
            );
        }

        return $availableAndInstalledExtensions[$extensionKey];
    }

    /**
     * @param string $extensionKey
     * @return array
     * @throws ExtensionManagerException
     */
    protected function getExtensionArray($extensionKey)
    {
        $availableExtensions = $this->listUtility->getAvailableExtensions();
        if (isset($availableExtensions[$extensionKey])) {
            return $availableExtensions[$extensionKey];
        }
        throw new ExtensionManagerException('Extension ' . $extensionKey . ' is not available', 1342864081);
    }

    /**
     * Creates directories as requested in ext_emconf.php
     *
     * @param array $extension
     */
    protected function ensureConfiguredDirectoriesExist(array $extension)
    {
        $this->fileHandlingUtility->ensureConfiguredDirectoriesExist($extension);
    }

    /**
     * Gets the content of the ext_tables.sql and ext_tables_static+adt.sql files
     * Additionally adds the table definitions for the cache tables
     *
     * @param array $extension
     * @deprecated since TYPO3 v9, will be removed with TYPO3v10
     */
    public function processDatabaseUpdates(array $extension)
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $extTablesSqlFile = Environment::getPublicPath() . '/' . $extension['siteRelPath'] . 'ext_tables.sql';
        $extTablesSqlContent = '';
        if (file_exists($extTablesSqlFile)) {
            $extTablesSqlContent .= file_get_contents($extTablesSqlFile);
        }
        if ($extTablesSqlContent !== '') {
            try {
                $this->updateDbWithExtTablesSql($extTablesSqlContent);
            } catch (\TYPO3\CMS\Core\Database\Schema\Exception\StatementException $e) {
                throw new ExtensionManagerException(
                    $e->getMessage(),
                    1476340371
                );
            }
        }

        $this->importStaticSqlFile($extension['siteRelPath']);
        $this->importT3DFile($extension['siteRelPath']);
    }

    /**
     * Emits a signal to manipulate the tables definitions
     *
     * @param string $extensionKey
     * @throws ExtensionManagerException
     * @return mixed
     * @deprecated since TYPO3 v9, will be removed with TYPO3v10
     * @see \TYPO3\CMS\Core\Database\Schema\SqlReader::emitTablesDefinitionIsBeingBuiltSignal
     */
    protected function emitTablesDefinitionIsBeingBuiltSignal($extensionKey)
    {
        $signalReturn = $this->signalSlotDispatcher->dispatch(__CLASS__, 'tablesDefinitionIsBeingBuilt', [[], $extensionKey]);
        // This is important to support old associated returns
        $signalReturn = array_values($signalReturn);
        $sqlString = $signalReturn[0];
        if (!is_array($sqlString)) {
            throw new ExtensionManagerException(
                sprintf(
                    'The signal %s of class %s returned a value of type %s, but array was expected.',
                    'tablesDefinitionIsBeingBuilt',
                    __CLASS__,
                    gettype($sqlString)
                ),
                1382360258
            );
        }
        if (!empty($sqlString)) {
            trigger_error(
                sprintf(
                    'The signal %s of class %s is deprecated and will be removed in TYPO3 v10.0.',
                    'tablesDefinitionIsBeingBuilt',
                    __CLASS__
                ),
                E_USER_DEPRECATED
            );
        }
        return $sqlString;
    }

    /**
     * Reload Cache files and Typo3LoadedExtensions
     */
    public function reloadCaches()
    {
        $this->reloadOpcache();
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtLocalconf(false);
        \TYPO3\CMS\Core\Core\Bootstrap::loadBaseTca(false);
        \TYPO3\CMS\Core\Core\Bootstrap::loadExtTables(false);
    }

    /**
     * Reloads PHP opcache
     */
    protected function reloadOpcache()
    {
        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();
    }

    /**
     * Executes all safe database statements.
     * Tables and fields are created and altered. Nothing gets deleted or renamed here.
     *
     * @param array $extensionKeys
     */
    protected function updateDatabase(array $extensionKeys)
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $schemaMigrator = GeneralUtility::makeInstance(SchemaMigrator::class);
        $sqlStatements = [];
        $sqlStatements[] = $sqlReader->getTablesDefinitionString();
        foreach ($extensionKeys as $extensionKey) {
            $sqlStatements += $this->emitTablesDefinitionIsBeingBuiltSignal($extensionKey);
        }
        $sqlStatements = $sqlReader->getCreateTableStatementArray(implode(LF . LF, array_filter($sqlStatements)));
        $updateStatements = $schemaMigrator->getUpdateSuggestions($sqlStatements);

        $updateStatements = array_merge_recursive(...array_values($updateStatements));
        $selectedStatements = [];
        foreach (['add', 'change', 'create_table', 'change_table'] as $action) {
            if (empty($updateStatements[$action])) {
                continue;
            }
            $selectedStatements = array_merge(
                $selectedStatements,
                array_combine(array_keys($updateStatements[$action]), array_fill(0, count($updateStatements[$action]), true))
            );
        }

        $schemaMigrator->migrate($sqlStatements, $selectedStatements);
    }

    /**
     * Save default configuration of an extension
     *
     * @param string $extensionKey
     */
    protected function saveDefaultConfiguration($extensionKey)
    {
        $extensionConfiguration = $this->objectManager->get(ExtensionConfiguration::class);
        $extensionConfiguration->synchronizeExtConfTemplateWithLocalConfiguration($extensionKey);
    }

    /**
     * Update database / process db updates from ext_tables
     *
     * @param string $rawDefinitions The raw SQL statements from ext_tables.sql
     * @deprecated since TYPO3 v9, will be removed with TYPO3v10
     */
    public function updateDbWithExtTablesSql($rawDefinitions)
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $statements = $sqlReader->getCreateTableStatementArray($rawDefinitions);
        if (count($statements) !== 0) {
            $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
            $schemaMigrationService->install($statements);
        }
    }

    /**
     * Import static SQL data (normally used for ext_tables_static+adt.sql)
     *
     * @param string $rawDefinitions
     */
    public function importStaticSql($rawDefinitions)
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $statements = $sqlReader->getStatementArray($rawDefinitions);

        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
        $schemaMigrationService->importStaticData($statements, true);
    }

    /**
     * Remove an extension (delete the directory)
     *
     * @param string $extension
     * @throws ExtensionManagerException
     */
    public function removeExtension($extension)
    {
        $absolutePath = $this->fileHandlingUtility->getAbsoluteExtensionPath($extension);
        if ($this->fileHandlingUtility->isValidExtensionPath($absolutePath)) {
            if ($this->packageManager->isPackageAvailable($extension)) {
                // Package manager deletes the extension and removes the entry from PackageStates.php
                $this->packageManager->deletePackage($extension);
            } else {
                // The extension is not listed in PackageStates.php, we can safely remove it
                $this->fileHandlingUtility->removeDirectory($absolutePath);
            }
        } else {
            throw new ExtensionManagerException('No valid extension path given.', 1342875724);
        }
    }

    /**
     * Checks if an update for an extension is available which also resolves dependencies.
     *
     * @internal
     * @param Extension $extensionData
     * @return bool
     */
    public function isUpdateAvailable(Extension $extensionData)
    {
        return (bool)$this->getUpdateableVersion($extensionData);
    }

    /**
     * Returns the updateable version for an extension which also resolves dependencies.
     *
     * @internal
     * @param Extension $extensionData
     * @return bool|Extension FALSE if no update available otherwise latest possible update
     */
    public function getUpdateableVersion(Extension $extensionData)
    {
        // Only check for update for TER extensions
        $version = $extensionData->getIntegerVersion();

        /** @var $extensionUpdates[] \TYPO3\CMS\Extensionmanager\Domain\Model\Extension */
        $extensionUpdates = $this->extensionRepository->findByVersionRangeAndExtensionKeyOrderedByVersion(
            $extensionData->getExtensionKey(),
            $version,
            0,
            false
        );
        if ($extensionUpdates->count() > 0) {
            foreach ($extensionUpdates as $extensionUpdate) {
                try {
                    $this->dependencyUtility->checkDependencies($extensionUpdate);
                    if (!$this->dependencyUtility->hasDependencyErrors()) {
                        return $extensionUpdate;
                    }
                } catch (ExtensionManagerException $e) {
                }
            }
        }
        return false;
    }

    /**
     * Uses the export import extension to import a T3D or XML file to PID 0
     * Execution state is saved in the this->registry, so it only happens once
     *
     * @param string $extensionSiteRelPath
     */
    protected function importT3DFile($extensionSiteRelPath)
    {
        $registryKeysToCheck = [
            $extensionSiteRelPath . 'Initialisation/data.t3d',
            $extensionSiteRelPath . 'Initialisation/dataImported',
        ];
        foreach ($registryKeysToCheck as $registryKeyToCheck) {
            if ($this->registry->get('extensionDataImport', $registryKeyToCheck)) {
                // Data was imported before => early return
                return;
            }
        }
        $importFileToUse = null;
        $possibleImportFiles = [
            $extensionSiteRelPath . 'Initialisation/data.t3d',
            $extensionSiteRelPath . 'Initialisation/data.xml'
        ];
        foreach ($possibleImportFiles as $possibleImportFile) {
            if (!file_exists(Environment::getPublicPath() . '/' . $possibleImportFile)) {
                continue;
            }
            $importFileToUse = $possibleImportFile;
        }
        if ($importFileToUse !== null) {
            /** @var ImportExportUtility $importExportUtility */
            $importExportUtility = $this->objectManager->get(ImportExportUtility::class);
            try {
                $importResult = $importExportUtility->importT3DFile(Environment::getPublicPath() . '/' . $importFileToUse, 0);
                $this->registry->set('extensionDataImport', $extensionSiteRelPath . 'Initialisation/dataImported', 1);
                $this->emitAfterExtensionT3DImportSignal($importFileToUse, $importResult);
            } catch (\ErrorException $e) {
                $logger = $this->objectManager->get(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
                $logger->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, $e->getMessage());
            }
        }
    }

    /**
     * Emits a signal after an t3d file was imported
     *
     * @param string $importFileToUse
     * @param int $importResult
     */
    protected function emitAfterExtensionT3DImportSignal($importFileToUse, $importResult)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterExtensionT3DImport', [$importFileToUse, $importResult, $this]);
    }

    /**
     * Imports a static tables SQL File (ext_tables_static+adt)
     * Execution state is saved in the this->registry, so it only happens once
     *
     * @param string $extensionSiteRelPath
     */
    protected function importStaticSqlFile($extensionSiteRelPath)
    {
        $extTablesStaticSqlRelFile = $extensionSiteRelPath . 'ext_tables_static+adt.sql';
        if (!$this->registry->get('extensionDataImport', $extTablesStaticSqlRelFile)) {
            $extTablesStaticSqlFile = Environment::getPublicPath() . '/' . $extTablesStaticSqlRelFile;
            $shortFileHash = '';
            if (file_exists($extTablesStaticSqlFile)) {
                $extTablesStaticSqlContent = file_get_contents($extTablesStaticSqlFile);
                $shortFileHash = md5($extTablesStaticSqlContent);
                $this->importStaticSql($extTablesStaticSqlContent);
            }
            $this->registry->set('extensionDataImport', $extTablesStaticSqlRelFile, $shortFileHash);
            $this->emitAfterExtensionStaticSqlImportSignal($extTablesStaticSqlRelFile);
        }
    }

    /**
     * Emits a signal after a static sql file was imported
     *
     * @param string $extTablesStaticSqlRelFile
     */
    protected function emitAfterExtensionStaticSqlImportSignal($extTablesStaticSqlRelFile)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterExtensionStaticSqlImport', [$extTablesStaticSqlRelFile, $this]);
    }

    /**
     * Imports files from Initialisation/Files to fileadmin
     * via lowlevel copy directory method
     *
     * @param string $extensionSiteRelPath relative path to extension dir
     * @param string $extensionKey
     */
    protected function importInitialFiles($extensionSiteRelPath, $extensionKey)
    {
        $importRelFolder = $extensionSiteRelPath . 'Initialisation/Files';
        if (!$this->registry->get('extensionDataImport', $importRelFolder)) {
            $importFolder = Environment::getPublicPath() . '/' . $importRelFolder;
            if (file_exists($importFolder)) {
                $destinationRelPath = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . $extensionKey;
                $destinationAbsolutePath = Environment::getPublicPath() . '/' . $destinationRelPath;
                if (!file_exists($destinationAbsolutePath) &&
                    GeneralUtility::isAllowedAbsPath($destinationAbsolutePath)
                ) {
                    GeneralUtility::mkdir($destinationAbsolutePath);
                }
                GeneralUtility::copyDirectory($importRelFolder, $destinationRelPath);
                $this->registry->set('extensionDataImport', $importRelFolder, 1);
                $this->emitAfterExtensionFileImportSignal($destinationAbsolutePath);
            }
        }
    }

    /**
     * Emits a signal after extension files were imported
     *
     * @param string $destinationAbsolutePath
     */
    protected function emitAfterExtensionFileImportSignal($destinationAbsolutePath)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterExtensionFileImport', [$destinationAbsolutePath, $this]);
    }
}
