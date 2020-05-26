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

namespace TYPO3\CMS\Extensionmanager\Utility;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;
use TYPO3\CMS\Core\Package\Event\AfterPackageDeactivationEvent;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionDatabaseContentHasBeenImportedEvent;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionFilesHaveBeenImportedEvent;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionSiteFilesHaveBeenImportedEvent;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionStaticDatabaseContentHasBeenImportedEvent;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Utility\ImportExportUtility;

/**
 * Extension Manager Install Utility
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class InstallUtility implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
     */
    protected $fileHandlingUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
     */
    protected $listUtility;

    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     */
    protected $packageManager;

    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected $cacheManager;

    /**
     * @var \TYPO3\CMS\Core\Registry
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var BootService
     */
    protected $bootService;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
     */
    public function injectFileHandlingUtility(FileHandlingUtility $fileHandlingUtility)
    {
        $this->fileHandlingUtility = $fileHandlingUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
     */
    public function injectListUtility(ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    /**
     * @param \TYPO3\CMS\Core\Package\PackageManager $packageManager
     */
    public function injectPackageManager(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @param \TYPO3\CMS\Core\Cache\CacheManager $cacheManager
     */
    public function injectCacheManager(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param \TYPO3\CMS\Core\Registry $registry
     */
    public function injectRegistry(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param  BootService $bootService
     */
    public function injectBootService(BootService $bootService)
    {
        $this->bootService = $bootService;
    }

    /**
     * Helper function to install an extension
     * also processes db updates and clears the cache if the extension asks for it
     *
     * @param string ...$extensionKeys
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

        // Load a new container as reloadCaches will load ext_localconf
        $container = $this->bootService->getContainer(false);
        $backup = $this->bootService->makeCurrent($container);

        $this->reloadCaches();
        $this->updateDatabase();

        foreach ($extensionKeys as $extensionKey) {
            $this->processExtensionSetup($extensionKey);
            $container->get(EventDispatcherInterface::class)->dispatch(new AfterPackageActivationEvent($extensionKey, 'typo3-cms-extension', $this));
        }

        // Reset to the original container instance
        $this->bootService->makeCurrent(null, $backup);
    }

    /**
     * @param string $extensionKey
     */
    public function processExtensionSetup(string $extensionKey): void
    {
        $packagePath = $this->packageManager->getPackage($extensionKey)->getPackagePath();
        $this->importInitialFiles($packagePath, $extensionKey);
        $this->importStaticSqlFile($extensionKey, $packagePath);
        $import = $this->importT3DFile($extensionKey, $packagePath);
        $this->importSiteConfiguration($extensionKey, $packagePath, $import);
    }

    /**
     * Helper function to uninstall an extension
     *
     * @param string $extensionKey
     * @throws ExtensionManagerException
     */
    public function uninstall($extensionKey)
    {
        $dependentExtensions = $this->findInstalledExtensionsThatDependOnExtension((string)$extensionKey);
        if (!empty($dependentExtensions)) {
            throw new ExtensionManagerException(
                LocalizationUtility::translate(
                    'extensionList.uninstall.dependencyError',
                    'extensionmanager',
                    [$extensionKey, implode(',', $dependentExtensions)]
                ) ?? '',
                1342554622
            );
        }
        $this->unloadExtension($extensionKey);
    }

    /**
     * Find installed extensions which depend on the given extension.
     * This is used at extension uninstall to stop the process if an installed
     * extension depends on the extension to be uninstalled.
     *
     * @param string $extensionKey
     * @return array
     */
    protected function findInstalledExtensionsThatDependOnExtension(string $extensionKey): array
    {
        $availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $dependentExtensions = [];
        foreach ($availableAndInstalledExtensions as $availableAndInstalledExtensionKey => $availableAndInstalledExtension) {
            if (isset($availableAndInstalledExtension['installed']) && $availableAndInstalledExtension['installed'] === true) {
                if (is_array($availableAndInstalledExtension['constraints'] ?? false) && is_array($availableAndInstalledExtension['constraints']['depends']) && array_key_exists($extensionKey, $availableAndInstalledExtension['constraints']['depends'])) {
                    $dependentExtensions[] = $availableAndInstalledExtensionKey;
                }
            }
        }
        return $dependentExtensions;
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
        $this->eventDispatcher->dispatch(new AfterPackageDeactivationEvent($extensionKey, 'typo3-cms-extension', $this));
        $this->cacheManager->flushCachesInGroup('system');
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
     * @return array
     * @throws ExtensionManagerException
     * @internal
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
     * Reload Cache files and Typo3LoadedExtensions
     */
    public function reloadCaches()
    {
        $this->reloadOpcache();
        ExtensionManagementUtility::loadExtLocalconf(false);
        Bootstrap::loadBaseTca(false);
        Bootstrap::loadExtTables(false);
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
     * @internal
     */
    public function updateDatabase()
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $schemaMigrator = GeneralUtility::makeInstance(SchemaMigrator::class);
        $sqlStatements = [];
        $sqlStatements[] = $sqlReader->getTablesDefinitionString();
        $sqlStatements = $sqlReader->getCreateTableStatementArray(implode(LF . LF, array_filter($sqlStatements)));
        $updateStatements = $schemaMigrator->getUpdateSuggestions($sqlStatements);

        $updateStatements = array_merge_recursive(...array_values($updateStatements));
        $selectedStatements = [];
        foreach (['add', 'change', 'create_table', 'change_table'] as $action) {
            if (empty($updateStatements[$action])) {
                continue;
            }

            $statements = array_combine(array_keys($updateStatements[$action]), array_fill(0, count($updateStatements[$action]), true));
            $statements = is_array($statements) ? $statements : [];
            $selectedStatements = array_merge(
                $selectedStatements,
                $statements
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
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $extensionConfiguration->synchronizeExtConfTemplateWithLocalConfiguration($extensionKey);
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
        $absolutePath = $this->enrichExtensionWithDetails($extension)['packagePath'];
        if ($this->isValidExtensionPath($absolutePath)) {
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
     * Uses the export import extension to import a T3D or XML file to PID 0
     * Execution state is saved in the this->registry, so it only happens once
     *
     * @param string $extensionKey
     * @param string $packagePath
     * @return Import|null
     */
    protected function importT3DFile($extensionKey, $packagePath): ?Import
    {
        $extensionSiteRelPath = PathUtility::stripPathSitePrefix($packagePath);
        $registryKeysToCheck = [
            $extensionSiteRelPath . 'Initialisation/data.t3d',
            $extensionSiteRelPath . 'Initialisation/dataImported',
        ];
        foreach ($registryKeysToCheck as $registryKeyToCheck) {
            if ($this->registry->get('extensionDataImport', $registryKeyToCheck)) {
                // Data was imported before => early return
                return null;
            }
        }
        $importFileToUse = null;
        $possibleImportFiles = [
            $packagePath . 'Initialisation/data.t3d',
            $packagePath . 'Initialisation/data.xml',
        ];
        foreach ($possibleImportFiles as $possibleImportFile) {
            if (!file_exists($possibleImportFile)) {
                continue;
            }
            $importFileToUse = $possibleImportFile;
        }
        if ($importFileToUse !== null) {
            $importExportUtility = GeneralUtility::makeInstance(ImportExportUtility::class);
            try {
                $importResult = $importExportUtility->importT3DFile($importFileToUse, 0);
                $this->registry->set('extensionDataImport', $extensionSiteRelPath . 'Initialisation/dataImported', 1);
                $this->eventDispatcher->dispatch(new AfterExtensionDatabaseContentHasBeenImportedEvent($extensionKey, $importFileToUse, $importResult, $this));
                return $importExportUtility->getImport();
            } catch (\ErrorException $e) {
                $this->logger->warning($e->getMessage(), ['exception' => $e]);
            }
        }
        return null;
    }

    /**
     * Imports a static tables SQL File (ext_tables_static+adt)
     * Execution state is saved in the this->registry, so it only happens once
     *
     * @param string $extensionKey
     * @param string $packagePath
     */
    protected function importStaticSqlFile(string $extensionKey, $packagePath)
    {
        $extTablesStaticSqlFile = $packagePath . 'ext_tables_static+adt.sql';
        $extTablesStaticSqlRelFile = PathUtility::stripPathSitePrefix($extTablesStaticSqlFile);
        if (!$this->registry->get('extensionDataImport', $extTablesStaticSqlRelFile)) {
            $shortFileHash = '';
            if (file_exists($extTablesStaticSqlFile)) {
                $extTablesStaticSqlContent = (string)file_get_contents($extTablesStaticSqlFile);
                $shortFileHash = md5($extTablesStaticSqlContent);
                $this->importStaticSql($extTablesStaticSqlContent);
            }
            $this->registry->set('extensionDataImport', $extTablesStaticSqlRelFile, $shortFileHash);
            $this->eventDispatcher->dispatch(new AfterExtensionStaticDatabaseContentHasBeenImportedEvent($extensionKey, $extTablesStaticSqlFile, $this));
        }
    }

    /**
     * Imports files from Initialisation/Files to fileadmin
     * via lowlevel copy directory method
     *
     * @param string $packagePath absolute path to extension dir
     * @param string $extensionKey
     */
    protected function importInitialFiles($packagePath, $extensionKey)
    {
        $importFolder = $packagePath . 'Initialisation/Files';
        $importRelFolder = PathUtility::stripPathSitePrefix($importFolder);
        if (!$this->registry->get('extensionDataImport', $importRelFolder)) {
            if (file_exists($importFolder)) {
                $destinationAbsolutePath = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . $extensionKey);
                if (!file_exists($destinationAbsolutePath) &&
                    GeneralUtility::isAllowedAbsPath($destinationAbsolutePath)
                ) {
                    GeneralUtility::mkdir($destinationAbsolutePath);
                }
                GeneralUtility::copyDirectory($importFolder, $destinationAbsolutePath);
                $this->registry->set('extensionDataImport', $importRelFolder, 1);
                $this->eventDispatcher->dispatch(new AfterExtensionFilesHaveBeenImportedEvent($extensionKey, $destinationAbsolutePath, $this));
            }
        }
    }

    /**
     * @param string $extensionKey
     * @param string $packagePath
     * @param Import|null $import
     */
    protected function importSiteConfiguration(string $extensionKey, string $packagePath, Import $import = null): void
    {
        $importAbsFolder = $packagePath . 'Initialisation/Site';
        $destinationFolder = Environment::getConfigPath() . '/sites';

        if (!is_dir($importAbsFolder)) {
            return;
        }

        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $existingSites = $siteConfiguration->resolveAllExistingSites(false);

        GeneralUtility::mkdir($destinationFolder);
        $finder = GeneralUtility::makeInstance(Finder::class);
        $finder->directories()->in($importAbsFolder);
        if ($finder->hasResults()) {
            foreach ($finder as $siteConfigDirectory) {
                $siteIdentifier = $siteConfigDirectory->getBasename();
                if (isset($existingSites[$siteIdentifier])) {
                    $this->logger->warning('Skipped importing site configuration from {key} due to existing site identifier {site}', [
                        'key' => $extensionKey,
                        'site' => $siteIdentifier,
                    ]);
                    continue;
                }
                $targetDir = $destinationFolder . '/' . $siteIdentifier;
                if (!$this->registry->get('siteConfigImport', $siteIdentifier) && !is_dir($targetDir)) {
                    GeneralUtility::mkdir($targetDir);
                    GeneralUtility::copyDirectory($siteConfigDirectory->getPathname(), $targetDir);
                    $this->registry->set('siteConfigImport', $siteIdentifier, 1);
                }
            }
        }

        /** @var Site[] $newSites */
        $newSites = array_diff_key($siteConfiguration->resolveAllExistingSites(false), $existingSites);
        $importedPages = [];
        if ($import instanceof Import) {
            $importedPages = $import->getImportMapId()['pages'] ?? null;
        }

        $newSiteIdentifierList = [];
        foreach ($newSites as $newSite) {
            $exportedPageId = $newSite->getRootPageId();
            $siteIdentifier = $newSite->getIdentifier();
            $newSiteIdentifierList[] = $siteIdentifier;
            $importedPageId = $importedPages[$exportedPageId] ?? null;
            if ($importedPageId === null) {
                $this->logger->warning('Imported site configuration with identifier {site} could not be mapped to imported page id', [
                    'site' => $siteIdentifier,
                ]);
                continue;
            }
            $configuration = $siteConfiguration->load($siteIdentifier);
            $configuration['rootPageId'] = $importedPageId;
            try {
                $siteConfiguration->write($siteIdentifier, $configuration);
            } catch (SiteConfigurationWriteException $e) {
                $this->logger->warning(
                    sprintf(
                        'Imported site configuration with identifier %s could not be written: %s',
                        $newSite->getIdentifier(),
                        $e->getMessage()
                    )
                );
                continue;
            }
        }
        $this->eventDispatcher->dispatch(new AfterExtensionSiteFilesHaveBeenImportedEvent($extensionKey, $newSiteIdentifierList));
    }

    /**
     * Is the given path a valid path for extension installation
     *
     * @param string $path the absolute (!) path in question
     * @return bool
     */
    protected function isValidExtensionPath($path): bool
    {
        $allowedPaths = Extension::returnAllowedInstallPaths();
        foreach ($allowedPaths as $allowedPath) {
            if (str_starts_with($path, $allowedPath)) {
                return true;
            }
        }
        return false;
    }
}
