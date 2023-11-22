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
use TYPO3\CMS\Core\Configuration\Extension\ExtLocalconfFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\Tca\TcaFactory;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;
use TYPO3\CMS\Core\Package\Event\AfterPackageDeactivationEvent;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
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
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class InstallUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private LanguageService $languageService;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly FileHandlingUtility $fileHandlingUtility,
        private readonly ListUtility $listUtility,
        private readonly PackageManager $packageManager,
        private readonly CacheManager $cacheManager,
        private readonly Registry $registry,
        private readonly BootService $bootService,
        private readonly OpcodeCacheService $opcodeCacheService,
        private readonly SqlReader $sqlReader,
        private readonly SchemaMigrator $schemaMigrator,
        private readonly ExtensionConfiguration $extensionConfiguration,
        LanguageServiceFactory $languageServiceFactory,
    ) {
        $this->languageService = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }

    /**
     * Helper function to install an extension and processes db updates.
     */
    public function install(string ...$extensionKeys): void
    {
        foreach ($extensionKeys as $extensionKey) {
            $this->packageManager->activatePackage($extensionKey);
            $this->extensionConfiguration->synchronizeExtConfTemplateWithLocalConfiguration($extensionKey);
        }
        $this->cacheManager->flushCaches();
        // Load a new container as we are reloading ext_localconf.php files
        $container = $this->bootService->getContainer(false);
        $backup = $this->bootService->makeCurrent($container);
        // Reload cache files and Typo3LoadedExtensions
        $this->opcodeCacheService->clearAllActive();
        $container->get(ExtLocalconfFactory::class)->loadUncached();
        $tcaFactory = $container->get(TcaFactory::class);
        $GLOBALS['TCA'] = $tcaFactory->create();
        Bootstrap::loadExtTables(false);
        $this->updateDatabase();
        foreach ($extensionKeys as $extensionKey) {
            $this->processExtensionSetup($extensionKey);
            $container->get(EventDispatcherInterface::class)->dispatch(new AfterPackageActivationEvent($extensionKey, 'typo3-cms-extension', $this));
        }
        // Reset to the original container instance
        $this->bootService->makeCurrent(null, $backup);
    }

    public function processExtensionSetup(string $extensionKey): void
    {
        $packagePath = $this->packageManager->getPackage($extensionKey)->getPackagePath();
        $this->importInitialFiles($packagePath, $extensionKey);
        $this->importStaticSqlFile($extensionKey, $packagePath);
        $import = $this->importT3DFile($extensionKey, $packagePath);
        $this->importSiteConfiguration($extensionKey, $packagePath, $import);
    }

    /**
     * Helper function to uninstall an extension.
     *
     * @throws ExtensionManagerException
     */
    public function uninstall(string $extensionKey): void
    {
        $dependentExtensions = $this->findInstalledExtensionsThatDependOnExtension($extensionKey);
        if (!empty($dependentExtensions)) {
            throw new ExtensionManagerException(
                sprintf(
                    $this->languageService->sL(
                        'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.uninstall.dependencyError'
                    ),
                    $extensionKey,
                    implode(', ', $dependentExtensions)
                ),
                1342554622
            );
        }
        $this->packageManager->deactivatePackage($extensionKey);
        $this->eventDispatcher->dispatch(new AfterPackageDeactivationEvent($extensionKey, 'typo3-cms-extension', $this));
        $this->cacheManager->flushCachesInGroup('system');
    }

    /**
     * Reset and reload the available extensions.
     */
    public function reloadAvailableExtensions(): void
    {
        $this->listUtility->reloadAvailableExtensions();
    }

    /**
     * Checks if an extension is available in the system.
     */
    public function isAvailable(string $extensionKey): bool
    {
        return $this->packageManager->isPackageAvailable($extensionKey);
    }

    /**
     * Reloads the package information, if the package is already registered.
     *
     * @throws InvalidPackageStateException if the package isn't available
     */
    public function reloadPackageInformation(string $extensionKey): void
    {
        if ($this->packageManager->isPackageAvailable($extensionKey)) {
            $this->opcodeCacheService->clearAllActive();
            $this->packageManager->reloadPackageInformation($extensionKey);
        }
    }

    /**
     * Fetch additional information for an extension key.
     *
     * @throws ExtensionManagerException
     */
    public function enrichExtensionWithDetails(string $extensionKey, bool $loadTerInformation = true): array
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
     * Executes all safe database statements.
     * Tables and fields are created and altered. Nothing gets deleted or renamed here.
     */
    public function updateDatabase(): void
    {
        $sqlStatements = [];
        $sqlStatements[] = $this->sqlReader->getTablesDefinitionString();
        $sqlStatements = $this->sqlReader->getCreateTableStatementArray(implode(LF . LF, array_filter($sqlStatements)));
        $updateStatements = $this->schemaMigrator->getUpdateSuggestions($sqlStatements);
        $updateStatements = array_merge_recursive(...array_values($updateStatements));
        $selectedStatements = [];
        foreach (['add', 'change', 'create_table', 'change_table'] as $action) {
            if (empty($updateStatements[$action])) {
                continue;
            }
            $statements = array_combine(array_keys($updateStatements[$action]), array_fill(0, count($updateStatements[$action]), true));
            $selectedStatements = array_merge(
                $selectedStatements,
                $statements
            );
        }
        $this->schemaMigrator->migrate($sqlStatements, $selectedStatements);
    }

    /**
     * Removing an extension deletes the directory.
     */
    public function removeExtension(string $extension): void
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
     * Find installed extensions which depend on the given extension.
     * Used by extension uninstall to stop the process if an installed
     * extension depends on the extension to be uninstalled.
     */
    protected function findInstalledExtensionsThatDependOnExtension(string $extensionKey): array
    {
        $availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $dependentExtensions = [];
        foreach ($availableAndInstalledExtensions as $availableAndInstalledExtensionKey => $availableAndInstalledExtension) {
            if (isset($availableAndInstalledExtension['installed']) && $availableAndInstalledExtension['installed'] === true) {
                if (is_array($availableAndInstalledExtension['constraints'] ?? false)
                    && is_array($availableAndInstalledExtension['constraints']['depends'])
                    && array_key_exists($extensionKey, $availableAndInstalledExtension['constraints']['depends'])
                ) {
                    $dependentExtensions[] = $availableAndInstalledExtensionKey;
                }
            }
        }
        return $dependentExtensions;
    }

    /**
     * @throws ExtensionManagerException
     */
    protected function getExtensionArray(string $extensionKey): array
    {
        $availableExtensions = $this->listUtility->getAvailableExtensions();
        if (isset($availableExtensions[$extensionKey])) {
            return $availableExtensions[$extensionKey];
        }
        throw new ExtensionManagerException('Extension ' . $extensionKey . ' is not available', 1342864081);
    }

    /**
     * Uses the export import extension to import a T3D or XML file to PID 0.
     * Execution state is saved in Registry, so it only happens once.
     */
    protected function importT3DFile(string $extensionKey, string $packagePath): ?Import
    {
        $extensionSiteRelPath = PathUtility::stripPathSitePrefix($packagePath);
        $registryKeysToCheck = [
            $extensionSiteRelPath . 'Initialisation/data.t3d',
            $extensionSiteRelPath . 'Initialisation/dataImported',
        ];
        foreach ($registryKeysToCheck as $registryKeyToCheck) {
            if ($this->registry->get('extensionDataImport', $registryKeyToCheck)) {
                // Data was imported before -> early return
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
            // @todo: Not using service injection here since ext:impexp may not be loaded?
            //        Needs more investigation. This dependency is unfortunate in general.
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
     * Import a static tables SQL File "ext_tables_static+adt.sql".
     * Execution state is saved in Registry, so it only happens once when file is unchanged.
     */
    protected function importStaticSqlFile(string $extensionKey, string $packagePath): void
    {
        $extTablesStaticSqlFile = $packagePath . 'ext_tables_static+adt.sql';
        $extTablesStaticSqlRelFile = PathUtility::stripPathSitePrefix($extTablesStaticSqlFile);
        if (!$this->registry->get('extensionDataImport', $extTablesStaticSqlRelFile)) {
            $shortFileHash = '';
            if (file_exists($extTablesStaticSqlFile)) {
                $extTablesStaticSqlContent = (string)file_get_contents($extTablesStaticSqlFile);
                $shortFileHash = md5($extTablesStaticSqlContent);
                $statements = $this->sqlReader->getStatementArray($extTablesStaticSqlContent);
                $this->schemaMigrator->importStaticData($statements, true);
            }
            $this->registry->set('extensionDataImport', $extTablesStaticSqlRelFile, $shortFileHash);
            $this->eventDispatcher->dispatch(new AfterExtensionStaticDatabaseContentHasBeenImportedEvent($extensionKey, $extTablesStaticSqlFile, $this));
        }
    }

    /**
     * Imports files from Initialisation/Files to fileadmin
     * as low level copy directory method.
     *
     * @param string $packagePath Absolute path to extension dir
     */
    protected function importInitialFiles(string $packagePath, string $extensionKey): void
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
        // @todo: Get rid of symfony finder here: We should use low level tools
        //        here to locate such files.
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
        $newSites = array_diff_key($siteConfiguration->resolveAllExistingSites(false), $existingSites);
        $importedPages = $import?->getImportMapId()['pages'] ?? [];
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
     * @param string $path Absolute (!) path in question
     */
    protected function isValidExtensionPath(string $path): bool
    {
        $allowedPaths = Extension::returnInstallPaths();
        foreach ($allowedPaths as $allowedPath) {
            if (str_starts_with($path, $allowedPath)) {
                return true;
            }
        }
        return false;
    }
}
