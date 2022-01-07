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

namespace TYPO3\CMS\Core\Package;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Cache\PackageCacheEntry;
use TYPO3\CMS\Core\Package\Cache\PackageCacheInterface;
use TYPO3\CMS\Core\Package\Event\PackagesMayHaveChangedEvent;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageManifestException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException;
use TYPO3\CMS\Core\Package\Exception\PackageManagerCacheUnavailableException;
use TYPO3\CMS\Core\Package\Exception\PackageStatesFileNotWritableException;
use TYPO3\CMS\Core\Package\Exception\ProtectedPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackagePathException;
use TYPO3\CMS\Core\Package\MetaData\PackageConstraint;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * The default TYPO3 Package Manager
 */
class PackageManager implements SingletonInterface
{
    /**
     * @var DependencyOrderingService
     */
    protected $dependencyOrderingService;

    /**
     * @var PackageCacheInterface
     */
    protected $packageCache;

    /**
     * @var array
     */
    protected $packagesBasePaths = [];

    /**
     * @var array
     */
    protected $packageAliasMap = [];

    /**
     * Absolute path leading to the various package directories
     * @var string
     */
    protected $packagesBasePath;

    /**
     * Array of available packages, indexed by package key
     * @var PackageInterface[]
     */
    protected $packages = [];

    /**
     * @var bool
     */
    protected $availablePackagesScanned = false;

    /**
     * A map between ComposerName and PackageKey, only available when scanAvailablePackages is run
     * @var array
     */
    protected $composerNameToPackageKeyMap = [];

    /**
     * List of active packages as package key => package object
     * @var array
     */
    protected $activePackages = [];

    /**
     * @var string
     */
    protected $packageStatesPathAndFilename;

    /**
     * Package states configuration as stored in the PackageStates.php file
     * @var array
     */
    protected $packageStatesConfiguration = [];

    /**
     * The regex to match paths with EXT:{package/key}
     *
     * @var ?string
     */
    protected ?string $packagePathMatchRegex;

    /**
     * @param DependencyOrderingService $dependencyOrderingService
     * @param string|null $packagesBasePath
     * @param string|null $packageStatesPathAndFilename
     */
    public function __construct(DependencyOrderingService $dependencyOrderingService, string $packageStatesPathAndFilename = null, string $packagesBasePath = null)
    {
        $this->packagesBasePath = $packagesBasePath ?? Environment::getPublicPath() . '/';
        $this->packageStatesPathAndFilename = $packageStatesPathAndFilename ?? Environment::getLegacyConfigPath() . '/PackageStates.php';
        $this->dependencyOrderingService = $dependencyOrderingService;
    }

    /**
     * @param PackageCacheInterface $packageCache
     * @internal
     */
    public function setPackageCache(PackageCacheInterface $packageCache)
    {
        $this->packageCache = $packageCache;
    }

    /**
     * Initializes the package manager
     * @internal
     */
    public function initialize()
    {
        try {
            $this->loadPackageManagerStatesFromCache();
        } catch (PackageManagerCacheUnavailableException $exception) {
            $this->loadPackageStates();
            $this->initializePackageObjects();
            $this->saveToPackageCache();
        }
    }

    /**
     * @internal
     * @return string|null
     */
    public function getCacheIdentifier()
    {
        try {
            return $this->packageCache->getIdentifier();
        } catch (PackageManagerCacheUnavailableException $e) {
            return null;
        }
    }

    /**
     * Saves the current state of all relevant information in the package cache
     */
    protected function saveToPackageCache(): void
    {
        // Build cache entry
        $cacheEntry = PackageCacheEntry::fromPackageData(
            $this->packageStatesConfiguration,
            $this->packageAliasMap,
            $this->composerNameToPackageKeyMap,
            $this->packages,
        );
        $this->packageCache->store($cacheEntry);
    }

    /**
     * Attempts to load the package manager states from cache
     *
     * @throws Exception\PackageManagerCacheUnavailableException
     */
    protected function loadPackageManagerStatesFromCache()
    {
        $cacheEntry = $this->packageCache->fetch();
        $this->packageStatesConfiguration = $cacheEntry->getConfiguration();
        $this->packageAliasMap = $cacheEntry->getAliasMap();
        $this->composerNameToPackageKeyMap = $cacheEntry->getComposerNameMap();
        $this->packages = $cacheEntry->getPackages();
    }

    /**
     * Loads the states of available packages from the PackageStates.php file.
     * The result is stored in $this->packageStatesConfiguration.
     *
     * @throws Exception\PackageStatesUnavailableException
     */
    protected function loadPackageStates()
    {
        $this->packageStatesConfiguration = (@include $this->packageStatesPathAndFilename) ?: [];
        PackageCacheEntry::ensureValidPackageConfiguration($this->packageStatesConfiguration);
        $this->registerPackagesFromConfiguration($this->packageStatesConfiguration['packages'], false);
    }

    /**
     * Initializes activePackages property
     *
     * Saves PackageStates.php if list of required extensions has changed.
     */
    protected function initializePackageObjects()
    {
        $requiredPackages = [];
        $activePackages = [];
        foreach ($this->packages as $packageKey => $package) {
            if ($package->isProtected()) {
                $requiredPackages[$packageKey] = $package;
            }
            if (isset($this->packageStatesConfiguration['packages'][$packageKey])) {
                $activePackages[$packageKey] = $package;
            }
        }
        $previousActivePackages = $activePackages;
        $activePackages = array_merge($requiredPackages, $activePackages);

        if ($activePackages != $previousActivePackages) {
            foreach ($requiredPackages as $requiredPackageKey => $package) {
                $this->registerActivePackage($package);
            }
            $this->sortAndSavePackageStates();
        }
    }

    /**
     * @param PackageInterface $package
     */
    protected function registerActivePackage(PackageInterface $package)
    {
        // reset the active packages so they are rebuilt.
        $this->activePackages = [];
        $this->packagePathMatchRegex = null;
        $this->packageStatesConfiguration['packages'][$package->getPackageKey()] = ['packagePath' => str_replace($this->packagesBasePath, '', $package->getPackagePath())];
    }

    /**
     * Scans all directories in the packages directories for available packages.
     * For each package a Package object is created and stored in $this->packages.
     * @internal
     */
    public function scanAvailablePackages()
    {
        if (Environment::isComposerMode()) {
            return;
        }
        $packagePaths = $this->scanPackagePathsForExtensions();
        $packages = [];
        foreach ($packagePaths as $packageKey => $packagePath) {
            $packages[$packageKey] = ['packagePath' => str_replace($this->packagesBasePath, '', $packagePath)];
        }

        $this->availablePackagesScanned = true;
        $registerOnlyNewPackages = !empty($this->packages);
        $this->registerPackagesFromConfiguration($packages, $registerOnlyNewPackages);
    }

    /**
     * Resolves a path in the form EXT:vendor/package/Path/To/Resource to an absolute filesystem path
     *
     * @param string $path
     * @return string
     * @throws UnknownPackageException
     * @throws UnknownPackagePathException
     */
    public function resolvePackagePath(string $path): string
    {
        $packageKey = $this->extractPackageKeyFromPackagePath($path);
        $package = $this->getPackage($packageKey);

        return str_replace('EXT:' . $packageKey . '/', $package->getPackagePath(), $path);
    }

    /**
     * Extracts the package key from a path in the form EXT:vendor/package/Path/To/Resource
     *
     * @param string $path
     * @return string
     * @throws UnknownPackageException
     * @throws UnknownPackagePathException
     * @internal
     */
    public function extractPackageKeyFromPackagePath(string $path): string
    {
        if (!PathUtility::isExtensionPath($path)) {
            throw new UnknownPackageException('Given path is not an extension path starting with "EXT:" ' . $path, 1631630764);
        }
        if (!isset($this->packagePathMatchRegex)) {
            $this->packagePathMatchRegex = sprintf(
                '/^EXT:(%s)\//',
                implode(
                    '|',
                    array_map(
                        static function ($packageKey) {
                            return preg_quote($packageKey, '/');
                        },
                        array_merge(
                            array_keys($this->getActivePackages()),
                            array_keys($this->packageAliasMap),
                            array_keys($this->composerNameToPackageKeyMap)
                        )
                    )
                )
            );
        }
        $result = preg_match($this->packagePathMatchRegex, $path, $matches);
        if (!$result || empty($matches[1])) {
            throw new UnknownPackagePathException('Package path "' . $path . '" is not available. Please check if the package referenced in the path exists and that the package key is correct (package keys are case sensitive).', 1631630087);
        }

        return $matches[1];
    }

    /**
     * Event listener to retrigger scanning of available packages.
     *
     * @param PackagesMayHaveChangedEvent $event
     */
    public function packagesMayHaveChanged(PackagesMayHaveChangedEvent $event): void
    {
        $this->scanAvailablePackages();
    }

    /**
     * Fetches all directories from sysext/global/local locations and checks if the extension contains an ext_emconf.php
     *
     * @return array
     */
    protected function scanPackagePathsForExtensions()
    {
        $collectedExtensionPaths = [];
        foreach ($this->getPackageBasePaths() as $packageBasePath) {
            // Only add the extension if we have an EMCONF and the extension is not yet registered.
            // This is crucial in order to allow overriding of system extension by local extensions
            // and strongly depends on the order of paths defined in $this->packagesBasePaths.
            $finder = new Finder();
            $finder
                ->name('ext_emconf.php')
                ->followLinks()
                ->depth(0)
                ->ignoreUnreadableDirs()
                ->in($packageBasePath);

            /** @var SplFileInfo $fileInfo */
            foreach ($finder as $fileInfo) {
                $path = PathUtility::dirname($fileInfo->getPathname());
                $extensionName = PathUtility::basename($path);
                // Fix Windows backslashes
                // we can't use GeneralUtility::fixWindowsFilePath as we have to keep double slashes for Unit Tests (vfs://)
                $currentPath = str_replace('\\', '/', $path) . '/';
                if (!isset($collectedExtensionPaths[$extensionName])) {
                    $collectedExtensionPaths[$extensionName] = $currentPath;
                }
            }
        }
        return $collectedExtensionPaths;
    }

    /**
     * Requires and registers all packages which were defined in packageStatesConfiguration
     *
     * @param array $packages
     * @param bool $registerOnlyNewPackages
     * @throws Exception\InvalidPackageStateException
     * @throws Exception\PackageStatesFileNotWritableException
     */
    protected function registerPackagesFromConfiguration(array $packages, $registerOnlyNewPackages = false)
    {
        $packageStatesHasChanged = false;
        foreach ($packages as $packageKey => $stateConfiguration) {
            if ($registerOnlyNewPackages && $this->isPackageRegistered($packageKey)) {
                continue;
            }

            if (!isset($stateConfiguration['packagePath'])) {
                $this->unregisterPackageByPackageKey($packageKey);
                $packageStatesHasChanged = true;
                continue;
            }

            try {
                $packagePath = PathUtility::sanitizeTrailingSeparator($this->packagesBasePath . $stateConfiguration['packagePath']);
                $package = new Package($this, $packageKey, $packagePath);
            } catch (InvalidPackagePathException|InvalidPackageKeyException|InvalidPackageManifestException $exception) {
                $this->unregisterPackageByPackageKey($packageKey);
                $packageStatesHasChanged = true;
                continue;
            }

            $this->registerPackage($package);
        }
        if ($packageStatesHasChanged) {
            $this->sortAndSavePackageStates();
        }
    }

    /**
     * Register a native TYPO3 package
     *
     * @param PackageInterface $package The Package to be registered
     * @return PackageInterface
     * @throws Exception\InvalidPackageStateException
     * @internal
     */
    public function registerPackage(PackageInterface $package)
    {
        $packageKey = $package->getPackageKey();
        if ($this->isPackageRegistered($packageKey)) {
            throw new InvalidPackageStateException('Package "' . $packageKey . '" is already registered.', 1338996122);
        }

        $this->composerNameToPackageKeyMap[$package->getValueFromComposerManifest('name')] = $packageKey;
        $this->packages[$packageKey] = $package;

        if ($package instanceof PackageInterface) {
            foreach ($package->getPackageReplacementKeys() as $packageToReplace => $versionConstraint) {
                $this->packageAliasMap[$packageToReplace] = $package->getPackageKey();
            }
        }
        return $package;
    }

    /**
     * Unregisters a package from the list of available packages
     *
     * @param string $packageKey Package Key of the package to be unregistered
     */
    protected function unregisterPackageByPackageKey($packageKey)
    {
        try {
            $package = $this->getPackage($packageKey);
            if ($package instanceof PackageInterface) {
                foreach ($package->getPackageReplacementKeys() as $packageToReplace => $versionConstraint) {
                    unset($this->packageAliasMap[$packageToReplace]);
                }
            }
        } catch (UnknownPackageException $e) {
        }
        $this->composerNameToPackageKeyMap = array_filter(
            $this->composerNameToPackageKeyMap,
            static function ($aliasedKey) use ($packageKey) {
                return $aliasedKey !== $packageKey;
            }
        );
        unset($this->packages[$packageKey]);
        unset($this->packageStatesConfiguration['packages'][$packageKey]);
    }

    /**
     * Resolves a TYPO3 package key from a composer package name.
     *
     * @param string $composerName
     * @return string
     * @internal
     */
    public function getPackageKeyFromComposerName($composerName)
    {
        if (isset($this->packageAliasMap[$composerName])) {
            return $this->packageAliasMap[$composerName];
        }
        if (isset($this->composerNameToPackageKeyMap[$composerName])) {
            return $this->composerNameToPackageKeyMap[$composerName];
        }
        return $composerName;
    }

    /**
     * Returns a PackageInterface object for the specified package.
     * A package is available, if the package directory contains valid MetaData information.
     *
     * @param string $packageKey
     * @return PackageInterface The requested package object
     * @throws Exception\UnknownPackageException if the specified package is not known
     */
    public function getPackage($packageKey)
    {
        if (!$this->isPackageRegistered($packageKey) && !$this->isPackageAvailable($packageKey)) {
            throw new UnknownPackageException('Package "' . $packageKey . '" is not available. Please check if the package exists and that the package key is correct (package keys are case sensitive).', 1166546734);
        }
        return $this->packages[$this->getPackageKeyFromComposerName($packageKey)];
    }

    /**
     * Returns TRUE if a package is available (the package's files exist in the packages directory)
     * or FALSE if it's not. If a package is available it doesn't mean necessarily that it's active!
     *
     * @param string $packageKey The key of the package to check
     * @return bool TRUE if the package is available, otherwise FALSE
     */
    public function isPackageAvailable($packageKey)
    {
        if ($this->isPackageRegistered($packageKey)) {
            return true;
        }

        // If activePackages is empty, the PackageManager is currently initializing
        // thus packages should not be scanned
        if (!$this->availablePackagesScanned && !empty($this->activePackages)) {
            $this->scanAvailablePackages();
        }

        return $this->isPackageRegistered($packageKey);
    }

    /**
     * Returns TRUE if a package is activated or FALSE if it's not.
     *
     * @param string $packageKey The key of the package to check
     * @return bool TRUE if package is active, otherwise FALSE
     */
    public function isPackageActive($packageKey)
    {
        $packageKey = $this->getPackageKeyFromComposerName($packageKey);

        return isset($this->packageStatesConfiguration['packages'][$packageKey]);
    }

    /**
     * Deactivates a package and updates the packagestates configuration
     *
     * @param string $packageKey
     * @throws Exception\PackageStatesFileNotWritableException
     * @throws Exception\ProtectedPackageKeyException
     * @throws Exception\UnknownPackageException
     * @internal
     */
    public function deactivatePackage($packageKey)
    {
        $packagesWithDependencies = $this->sortActivePackagesByDependencies();

        foreach ($packagesWithDependencies as $packageStateKey => $packageStateConfiguration) {
            if ($packageKey === $packageStateKey || empty($packageStateConfiguration['dependencies'])) {
                continue;
            }
            if (in_array($packageKey, $packageStateConfiguration['dependencies'], true)) {
                $this->deactivatePackage($packageStateKey);
            }
        }

        if (!$this->isPackageActive($packageKey)) {
            return;
        }

        $package = $this->getPackage($packageKey);
        if ($package->isProtected()) {
            throw new ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be deactivated.', 1308662891);
        }

        $this->activePackages = [];
        $this->packagePathMatchRegex = null;
        unset($this->packageStatesConfiguration['packages'][$packageKey]);
        $this->sortAndSavePackageStates();
    }

    /**
     * @param string $packageKey
     * @internal
     */
    public function activatePackage($packageKey)
    {
        $package = $this->getPackage($packageKey);
        $this->registerTransientClassLoadingInformationForPackage($package);

        if ($this->isPackageActive($packageKey)) {
            return;
        }

        $this->registerActivePackage($package);
        $this->sortAndSavePackageStates();
    }

    /**
     * @param PackageInterface $package
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function registerTransientClassLoadingInformationForPackage(PackageInterface $package)
    {
        if (Environment::isComposerMode()) {
            return;
        }
        ClassLoadingInformation::registerTransientClassLoadingInformationForPackage($package);
    }

    /**
     * Removes a package from the file system.
     *
     * @param string $packageKey
     * @throws Exception
     * @throws Exception\ProtectedPackageKeyException
     * @throws Exception\UnknownPackageException
     * @internal
     */
    public function deletePackage($packageKey)
    {
        if (!$this->isPackageAvailable($packageKey)) {
            throw new UnknownPackageException('Package "' . $packageKey . '" is not available and cannot be removed.', 1166543253);
        }

        $package = $this->getPackage($packageKey);
        if ($package->isProtected()) {
            throw new ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be removed.', 1220722120);
        }

        if ($this->isPackageActive($packageKey)) {
            $this->deactivatePackage($packageKey);
        }

        $this->unregisterPackage($package);
        $this->sortAndSavePackageStates();

        $packagePath = $package->getPackagePath();
        $deletion = GeneralUtility::rmdir($packagePath, true);
        if ($deletion === false) {
            throw new Exception('Please check file permissions. The directory "' . $packagePath . '" for package "' . $packageKey . '" could not be removed.', 1301491089);
        }
    }

    /**
     * Returns an array of \TYPO3\CMS\Core\Package objects of all active packages.
     * A package is active, if it is available and has been activated in the package
     * manager settings.
     *
     * @return PackageInterface[]
     */
    public function getActivePackages()
    {
        if (empty($this->activePackages)) {
            if (!empty($this->packageStatesConfiguration['packages'])) {
                foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $packageConfig) {
                    $this->activePackages[$packageKey] = $this->getPackage($packageKey);
                }
            }
        }
        return $this->activePackages;
    }

    /**
     * Returns TRUE if a package was already registered or FALSE if it's not.
     *
     * @param string $packageKey
     * @return bool
     */
    protected function isPackageRegistered($packageKey)
    {
        $packageKey = $this->getPackageKeyFromComposerName($packageKey);

        return isset($this->packages[$packageKey]);
    }

    /**
     * Orders all active packages by comparing their dependencies. By this, the packages
     * and package configurations arrays holds all packages in the correct
     * initialization order.
     *
     * @return array
     */
    protected function sortActivePackagesByDependencies()
    {
        $packagesWithDependencies = $this->resolvePackageDependencies($this->packageStatesConfiguration['packages']);

        // sort the packages by key at first, so we get a stable sorting of "equivalent" packages afterwards
        ksort($packagesWithDependencies);
        $sortedPackageKeys = $this->sortPackageStatesConfigurationByDependency($packagesWithDependencies);

        // Reorder the packages according to the loading order
        $this->packageStatesConfiguration['packages'] = [];
        foreach ($sortedPackageKeys as $packageKey) {
            $this->registerActivePackage($this->packages[$packageKey]);
        }
        return $packagesWithDependencies;
    }

    /**
     * Resolves the dependent packages from the meta data of all packages recursively. The
     * resolved direct or indirect dependencies of each package will put into the package
     * states configuration array.
     *
     * @param array $packageConfig
     * @return array
     */
    protected function resolvePackageDependencies($packageConfig)
    {
        $packagesWithDependencies = [];
        foreach ($packageConfig as $packageKey => $_) {
            $packagesWithDependencies[$packageKey]['dependencies'] = $this->getDependencyArrayForPackage($packageKey);
            $packagesWithDependencies[$packageKey]['suggestions'] = $this->getSuggestionArrayForPackage($packageKey);
        }
        return $packagesWithDependencies;
    }

    /**
     * Returns an array of suggested package keys for the given package.
     *
     * @param string $packageKey The package key to fetch the suggestions for
     * @return array|null An array of directly suggested packages
     */
    protected function getSuggestionArrayForPackage($packageKey)
    {
        if (!isset($this->packages[$packageKey])) {
            return null;
        }
        $suggestedPackageKeys = [];
        $suggestedPackageConstraints = $this->packages[$packageKey]->getPackageMetaData()->getConstraintsByType(MetaData::CONSTRAINT_TYPE_SUGGESTS);
        foreach ($suggestedPackageConstraints as $constraint) {
            if ($constraint instanceof PackageConstraint) {
                $suggestedPackageKey = $constraint->getValue();
                if (isset($this->packages[$suggestedPackageKey])) {
                    $suggestedPackageKeys[] = $suggestedPackageKey;
                }
            }
        }
        return array_reverse($suggestedPackageKeys);
    }

    /**
     * Saves the current content of $this->packageStatesConfiguration to the
     * PackageStates.php file.
     *
     * @throws Exception\PackageStatesFileNotWritableException
     */
    protected function savePackageStates()
    {
        $this->packageStatesConfiguration['version'] = 5;

        $fileDescription = "# PackageStates.php\n\n";
        $fileDescription .= "# This file is maintained by TYPO3's package management. Although you can edit it\n";
        $fileDescription .= "# manually, you should rather use the extension manager for maintaining packages.\n";
        $fileDescription .= "# This file will be regenerated automatically if it doesn't exist. Deleting this file\n";
        $fileDescription .= "# should, however, never become necessary if you use the package commands.\n";

        if (!@is_writable($this->packageStatesPathAndFilename)) {
            // If file does not exist, try to create it
            $fileHandle = @fopen($this->packageStatesPathAndFilename, 'x');
            if (!$fileHandle) {
                throw new PackageStatesFileNotWritableException(
                    sprintf('We could not update the list of installed packages because the file %s is not writable. Please, check the file system permissions for this file and make sure that the web server can update it.', $this->packageStatesPathAndFilename),
                    1382449759
                );
            }
            fclose($fileHandle);
        }
        $packageStatesCode = "<?php\n$fileDescription\nreturn " . ArrayUtility::arrayExport($this->packageStatesConfiguration) . ";\n";
        GeneralUtility::writeFile($this->packageStatesPathAndFilename, $packageStatesCode, true);
        // Cache depends on package states file, therefore we invalidate it
        $this->packageCache->invalidate();

        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive($this->packageStatesPathAndFilename);
    }

    /**
     * Saves the current content of $this->packageStatesConfiguration to the
     * PackageStates.php file.
     *
     * @throws Exception\PackageStatesFileNotWritableException
     */
    protected function sortAndSavePackageStates()
    {
        $this->sortActivePackagesByDependencies();
        $this->savePackageStates();
    }

    /**
     * Check the conformance of the given package key
     *
     * @param string $packageKey The package key to validate
     * @return bool If the package key is valid, returns TRUE otherwise FALSE
     */
    public function isPackageKeyValid($packageKey)
    {
        return preg_match(PackageInterface::PATTERN_MATCH_EXTENSIONKEY, $packageKey) === 1 || preg_match(PackageInterface::PATTERN_MATCH_COMPOSER_NAME, $packageKey) === 1 || preg_match(PackageInterface::PATTERN_MATCH_PACKAGEKEY, $packageKey) === 1;
    }

    /**
     * Returns an array of \TYPO3\CMS\Core\Package objects of all available packages.
     * A package is available, if the package directory contains valid meta information.
     *
     * @return PackageInterface[] Array of PackageInterface
     */
    public function getAvailablePackages()
    {
        if ($this->availablePackagesScanned === false) {
            $this->scanAvailablePackages();
        }

        return $this->packages;
    }

    /**
     * Unregisters a package from the list of available packages
     *
     * @param PackageInterface $package The package to be unregistered
     * @throws Exception\InvalidPackageStateException
     * @internal
     */
    public function unregisterPackage(PackageInterface $package)
    {
        $packageKey = $package->getPackageKey();
        if (!$this->isPackageRegistered($packageKey)) {
            throw new InvalidPackageStateException('Package "' . $packageKey . '" is not registered.', 1338996142);
        }
        $this->unregisterPackageByPackageKey($packageKey);
    }

    /**
     * Reloads a package and its information
     *
     * @param string $packageKey
     * @throws Exception\InvalidPackageStateException if the package isn't available
     * @internal
     */
    public function reloadPackageInformation($packageKey)
    {
        if (!$this->isPackageRegistered($packageKey)) {
            throw new InvalidPackageStateException('Package "' . $packageKey . '" is not registered.', 1436201329);
        }

        $package = $this->packages[$packageKey];
        $packagePath = $package->getPackagePath();
        $newPackage = new Package($this, $packageKey, $packagePath);
        $this->packages[$packageKey] = $newPackage;
        unset($package);
    }

    /**
     * Returns contents of Composer manifest as a stdObject
     *
     * @param string $manifestPath
     * @param bool $ignoreExtEmConf
     * @return \stdClass
     * @throws InvalidPackageManifestException
     * @internal
     */
    public function getComposerManifest(string $manifestPath, bool $ignoreExtEmConf = false)
    {
        $composerManifest = new \stdClass();
        if (file_exists($manifestPath . 'composer.json')) {
            $json = file_get_contents($manifestPath . 'composer.json');
            if ($json !== false) {
                $composerManifest = json_decode($json);
            }
            if (!$composerManifest instanceof \stdClass) {
                throw new InvalidPackageManifestException('The composer.json found for extension "' . PathUtility::basename($manifestPath) . '" is invalid!', 1439555561);
            }
        }

        if ($ignoreExtEmConf) {
            return $composerManifest;
        }

        $packageKey = $this->getPackageKeyFromManifest($composerManifest, $manifestPath);
        $extensionManagerConfiguration = $this->getExtensionEmConf($manifestPath, $packageKey);
        if ($extensionManagerConfiguration !== null) {
            $composerManifest = $this->mapExtensionManagerConfigurationToComposerManifest(
                $packageKey,
                $extensionManagerConfiguration,
                $composerManifest
            );
        }

        return $composerManifest;
    }

    /**
     * Fetches MetaData information from ext_emconf.php, used for
     * resolving dependencies as well.
     *
     * @param string $packagePath
     * @param string $packageKey
     * @return array|null if no ext_emconf.php was found, or the contents of the ext_emconf.php file.
     * @throws Exception\InvalidPackageManifestException
     */
    protected function getExtensionEmConf(string $packagePath, string $packageKey): ?array
    {
        $_EXTKEY = $packageKey;
        $path = $packagePath . 'ext_emconf.php';
        $EM_CONF = null;
        if (@file_exists($path)) {
            include $path;
            if (is_array($EM_CONF[$_EXTKEY])) {
                return $EM_CONF[$_EXTKEY];
            }
            throw new InvalidPackageManifestException('No valid ext_emconf.php file found for package "' . $packageKey . '".', 1360403545);
        }
        return null;
    }

    /**
     * Fetches information from ext_emconf.php and maps it so it is treated as it would come from composer.json
     *
     * @param string $packageKey
     * @param array $extensionManagerConfiguration
     * @param \stdClass $composerManifest
     * @return \stdClass
     * @throws Exception\InvalidPackageManifestException
     */
    protected function mapExtensionManagerConfigurationToComposerManifest($packageKey, array $extensionManagerConfiguration, \stdClass $composerManifest)
    {
        $this->setComposerManifestValueIfEmpty($composerManifest, 'name', $packageKey);
        $this->setComposerManifestValueIfEmpty($composerManifest, 'type', 'typo3-cms-extension');
        $this->setComposerManifestValueIfEmpty($composerManifest, 'description', $extensionManagerConfiguration['title'] ?? '');
        $this->setComposerManifestValueIfEmpty($composerManifest, 'authors', [['name' => $extensionManagerConfiguration['author'] ?? '', 'email' => $extensionManagerConfiguration['author_email'] ?? '']]);
        $composerManifest->version = $extensionManagerConfiguration['version'] ?? '';
        // "Invent" a new title attribute here for internal use in non Composer mode
        $composerManifest->title = $extensionManagerConfiguration['title'] ?? null;
        $composerManifest->require = new \stdClass();
        $composerManifest->conflict = new \stdClass();
        $composerManifest->suggest = new \stdClass();
        if (isset($extensionManagerConfiguration['constraints']['depends']) && is_array($extensionManagerConfiguration['constraints']['depends'])) {
            foreach ($extensionManagerConfiguration['constraints']['depends'] as $requiredPackageKey => $requiredPackageVersion) {
                if (!empty($requiredPackageKey)) {
                    if ($requiredPackageKey === 'typo3') {
                        // Add implicit dependency to 'core'
                        $composerManifest->require->core = $requiredPackageVersion;
                    } elseif ($requiredPackageKey !== 'php') {
                        // Skip php dependency
                        $composerManifest->require->{$requiredPackageKey} = $requiredPackageVersion;
                    }
                } else {
                    throw new InvalidPackageManifestException(sprintf('The extension "%s" has invalid version constraints in depends section. Extension key is missing!', $packageKey), 1439552058);
                }
            }
        }
        if (isset($extensionManagerConfiguration['constraints']['conflicts']) && is_array($extensionManagerConfiguration['constraints']['conflicts'])) {
            foreach ($extensionManagerConfiguration['constraints']['conflicts'] as $conflictingPackageKey => $conflictingPackageVersion) {
                if (!empty($conflictingPackageKey)) {
                    $composerManifest->conflict->$conflictingPackageKey = $conflictingPackageVersion;
                } else {
                    throw new InvalidPackageManifestException(sprintf('The extension "%s" has invalid version constraints in conflicts section. Extension key is missing!', $packageKey), 1439552059);
                }
            }
        }
        if (isset($extensionManagerConfiguration['constraints']['suggests']) && is_array($extensionManagerConfiguration['constraints']['suggests'])) {
            foreach ($extensionManagerConfiguration['constraints']['suggests'] as $suggestedPackageKey => $suggestedPackageVersion) {
                if (!empty($suggestedPackageKey)) {
                    $composerManifest->suggest->$suggestedPackageKey = $suggestedPackageVersion;
                } else {
                    throw new InvalidPackageManifestException(sprintf('The extension "%s" has invalid version constraints in suggests section. Extension key is missing!', $packageKey), 1439552060);
                }
            }
        }
        if (isset($extensionManagerConfiguration['autoload'])) {
            $autoload = json_encode($extensionManagerConfiguration['autoload']);
            if ($autoload !== false) {
                $composerManifest->autoload = json_decode($autoload);
            }
        }
        // composer.json autoload-dev information must be discarded, as it may contain information only available after a composer install
        unset($composerManifest->{'autoload-dev'});
        if (isset($extensionManagerConfiguration['autoload-dev'])) {
            $autoloadDev = json_encode($extensionManagerConfiguration['autoload-dev']);
            if ($autoloadDev !== false) {
                $composerManifest->{'autoload-dev'} = json_decode($autoloadDev);
            }
        }

        return $composerManifest;
    }

    /**
     * @param \stdClass $manifest
     * @param string $property
     * @param mixed $value
     * @return \stdClass
     */
    protected function setComposerManifestValueIfEmpty(\stdClass $manifest, $property, $value)
    {
        if (empty($manifest->{$property})) {
            $manifest->{$property} = $value;
        }

        return $manifest;
    }

    /**
     * Returns an array of dependent package keys for the given package. It will
     * do this recursively, so dependencies of dependent packages will also be
     * in the result.
     *
     * @param string $packageKey The package key to fetch the dependencies for
     * @param array $dependentPackageKeys
     * @param array $trace An array of already visited package keys, to detect circular dependencies
     * @return array|null An array of direct or indirect dependent packages
     * @throws Exception\InvalidPackageKeyException
     */
    protected function getDependencyArrayForPackage($packageKey, array &$dependentPackageKeys = [], array $trace = [])
    {
        if (!isset($this->packages[$packageKey])) {
            return null;
        }
        if (in_array($packageKey, $trace, true) !== false) {
            return $dependentPackageKeys;
        }
        $trace[] = $packageKey;
        $dependentPackageConstraints = $this->packages[$packageKey]->getPackageMetaData()->getConstraintsByType(MetaData::CONSTRAINT_TYPE_DEPENDS);
        foreach ($dependentPackageConstraints as $constraint) {
            if ($constraint instanceof PackageConstraint) {
                $dependentPackageKey = $constraint->getValue();
                if (in_array($dependentPackageKey, $dependentPackageKeys, true) === false && in_array($dependentPackageKey, $trace, true) === false) {
                    $dependentPackageKeys[] = $dependentPackageKey;
                }
                $this->getDependencyArrayForPackage($dependentPackageKey, $dependentPackageKeys, $trace);
            }
        }
        return array_reverse($dependentPackageKeys);
    }

    /**
     * Resolves package key from Composer manifest
     *
     * If it is a TYPO3 package the name of the containing directory will be used.
     *
     * Else if the composer name of the package matches the first part of the lowercased namespace of the package, the mixed
     * case version of the composer name / namespace will be used, with backslashes replaced by dots.
     *
     * Else the composer name will be used with the slash replaced by a dot
     *
     * @param object $manifest
     * @param string $packagePath
     * @throws Exception\InvalidPackageManifestException
     * @return string
     */
    protected function getPackageKeyFromManifest($manifest, $packagePath)
    {
        if (!is_object($manifest)) {
            throw new InvalidPackageManifestException('Invalid composer manifest in package path: ' . $packagePath, 1348146451);
        }
        if (!empty($manifest->extra->{'typo3/cms'}->{'extension-key'})) {
            return $manifest->extra->{'typo3/cms'}->{'extension-key'};
        }
        if (empty($manifest->name) || (isset($manifest->type) && strpos($manifest->type, 'typo3-cms-') === 0)) {
            return PathUtility::basename($packagePath);
        }

        return $manifest->name;
    }

    /**
     * The order of paths is crucial for allowing overriding of system extension by local extensions.
     * Pay attention if you change order of the paths here.
     *
     * @return array
     */
    protected function getPackageBasePaths()
    {
        if (count($this->packagesBasePaths) < 3) {
            // Check if the directory even exists and if it is not empty
            if (is_dir(Environment::getExtensionsPath()) && $this->hasSubDirectories(Environment::getExtensionsPath())) {
                $this->packagesBasePaths['local'] = Environment::getExtensionsPath() . '/*/';
            }
            if (is_dir(Environment::getBackendPath() . '/ext') && $this->hasSubDirectories(Environment::getBackendPath() . '/ext')) {
                $this->packagesBasePaths['global'] = Environment::getBackendPath() . '/ext/*/';
            }
            $this->packagesBasePaths['system'] = Environment::getFrameworkBasePath() . '/*/';
        }
        return $this->packagesBasePaths;
    }

    /**
     * Returns true if the given path has valid subdirectories, false otherwise.
     *
     * @param string $path
     * @return bool
     */
    protected function hasSubDirectories(string $path): bool
    {
        return !empty(glob(rtrim($path, '/\\') . '/*', GLOB_ONLYDIR));
    }

    /**
     * @param array $packageStatesConfiguration
     * @return array Returns the packageStatesConfiguration sorted by dependencies
     * @throws \UnexpectedValueException
     */
    protected function sortPackageStatesConfigurationByDependency(array $packageStatesConfiguration)
    {
        return $this->dependencyOrderingService->calculateOrder($this->buildDependencyGraph($packageStatesConfiguration));
    }

    /**
     * Convert the package configuration into a dependency definition
     *
     * This converts "dependencies" and "suggestions" to "after" syntax for the usage in DependencyOrderingService
     *
     * @param array $packageStatesConfiguration
     * @param array $packageKeys
     * @return array
     * @throws \UnexpectedValueException
     */
    protected function convertConfigurationForGraph(array $packageStatesConfiguration, array $packageKeys)
    {
        $dependencies = [];
        foreach ($packageKeys as $packageKey) {
            if (!isset($packageStatesConfiguration[$packageKey]['dependencies']) && !isset($packageStatesConfiguration[$packageKey]['suggestions'])) {
                continue;
            }
            $dependencies[$packageKey] = [
                'after' => [],
            ];
            if (isset($packageStatesConfiguration[$packageKey]['dependencies'])) {
                foreach ($packageStatesConfiguration[$packageKey]['dependencies'] as $dependentPackageKey) {
                    if (!in_array($dependentPackageKey, $packageKeys, true)) {
                        if ($this->isComposerDependency($dependentPackageKey)) {
                            // The given package has a dependency to a Composer package that has no relation to TYPO3
                            // We can ignore those, when calculating the extension order
                            continue;
                        }
                        throw new \UnexpectedValueException(
                            'The package "' . $packageKey . '" depends on "'
                            . $dependentPackageKey . '" which is not present in the system.',
                            1519931815
                        );
                    }
                    $dependencies[$packageKey]['after'][] = $dependentPackageKey;
                }
            }
            if (isset($packageStatesConfiguration[$packageKey]['suggestions'])) {
                foreach ($packageStatesConfiguration[$packageKey]['suggestions'] as $suggestedPackageKey) {
                    // skip suggestions on not existing packages
                    if (in_array($suggestedPackageKey, $packageKeys, true)) {
                        // Suggestions actually have never been meant to influence loading order.
                        // We misuse this currently, as there is no other way to influence the loading order
                        // for not-required packages (soft-dependency).
                        // When considering suggestions for the loading order, we might create a cyclic dependency
                        // if the suggested package already has a real dependency on this package, so the suggestion
                        // has do be dropped in this case and must *not* be taken into account for loading order evaluation.
                        $dependencies[$packageKey]['after-resilient'][] = $suggestedPackageKey;
                    }
                }
            }
        }
        return $dependencies;
    }

    /**
     * Checks whether the given package name is a Composer dependency.
     * In non Composer mode this is always false
     *
     * @param string $packageName
     * @return bool
     */
    protected function isComposerDependency(string $packageName): bool
    {
        return false;
    }

    /**
     * Adds all root packages of current dependency graph as dependency to all extensions
     *
     * This ensures that the framework extensions (aka sysext) are
     * always loaded first, before any other external extension.
     *
     * @param array $packageStateConfiguration
     * @param array $rootPackageKeys
     * @return array
     */
    protected function addDependencyToFrameworkToAllExtensions(array $packageStateConfiguration, array $rootPackageKeys)
    {
        $frameworkPackageKeys = $this->findFrameworkPackages($packageStateConfiguration);
        $extensionPackageKeys = array_diff(array_keys($packageStateConfiguration), $frameworkPackageKeys);
        foreach ($extensionPackageKeys as $packageKey) {
            // Remove framework packages from list
            $packageKeysWithoutFramework = array_diff(
                $packageStateConfiguration[$packageKey]['dependencies'],
                $frameworkPackageKeys
            );
            // The order of the array_merge is crucial here,
            // we want the framework first
            $packageStateConfiguration[$packageKey]['dependencies'] = array_merge(
                $rootPackageKeys,
                $packageKeysWithoutFramework
            );
        }
        return $packageStateConfiguration;
    }

    /**
     * Builds the dependency graph for all packages
     *
     * This method also introduces dependencies among the dependencies
     * to ensure the loading order is exactly as specified in the list.
     *
     * @param array $packageStateConfiguration
     * @return array
     */
    protected function buildDependencyGraph(array $packageStateConfiguration)
    {
        $frameworkPackageKeys = $this->findFrameworkPackages($packageStateConfiguration);
        $frameworkPackagesDependencyGraph = $this->dependencyOrderingService->buildDependencyGraph($this->convertConfigurationForGraph($packageStateConfiguration, $frameworkPackageKeys));
        $packageStateConfiguration = $this->addDependencyToFrameworkToAllExtensions($packageStateConfiguration, $this->dependencyOrderingService->findRootIds($frameworkPackagesDependencyGraph));

        $packageKeys = array_keys($packageStateConfiguration);
        return $this->dependencyOrderingService->buildDependencyGraph($this->convertConfigurationForGraph($packageStateConfiguration, $packageKeys));
    }

    /**
     * @param array $packageStateConfiguration
     * @return array
     */
    protected function findFrameworkPackages(array $packageStateConfiguration)
    {
        $frameworkPackageKeys = [];
        foreach ($packageStateConfiguration as $packageKey => $packageConfiguration) {
            $package = $this->getPackage($packageKey);
            if ($package->getPackageMetaData()->isFrameworkType()) {
                $frameworkPackageKeys[] = $packageKey;
            }
        }

        return $frameworkPackageKeys;
    }

    /**
     * @internal
     */
    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if (Environment::isComposerMode()) {
            return;
        }
        if ($event->hasGroup('system')) {
            if (count($this->packageStatesConfiguration) === 0) {
                $this->loadPackageStates();
                $this->initializePackageObjects();
            }
            $this->saveToPackageCache();
        }
    }
}
