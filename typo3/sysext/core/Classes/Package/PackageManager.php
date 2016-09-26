<?php
namespace TYPO3\CMS\Core\Package;

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

use TYPO3\CMS\Core\Compatibility\LoadedExtensionArrayElement;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * The default TYPO3 Package Manager
 * Adapted from FLOW for TYPO3 CMS
 */
class PackageManager implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Core\Package\DependencyResolver
     */
    protected $dependencyResolver;

    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
     */
    protected $coreCache;

    /**
     * @var string
     */
    protected $cacheIdentifier;

    /**
     * @var array
     */
    protected $packagesBasePaths = [];

    /**
     * @var array
     */
    protected $packageAliasMap = [];

    /**
     * @var array
     */
    protected $requiredPackageKeys = [];

    /**
     * @var array
     */
    protected $runtimeActivatedPackages = [];

    /**
     * Absolute path leading to the various package directories
     * @var string
     */
    protected $packagesBasePath = PATH_site;

    /**
     * Array of available packages, indexed by package key
     * @var PackageInterface[]
     */
    protected $packages = [];

    /**
     * A translation table between lower cased and upper camel cased package keys
     * @var array
     */
    protected $packageKeys = [];

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
     * Constructor
     */
    public function __construct()
    {
        // The order of paths is crucial for allowing overriding of system extension by local extensions.
        // Pay attention if you change order of the paths here.
        $this->packagesBasePaths = [
            'local'     => PATH_typo3conf . 'ext',
            'global'    => PATH_typo3 . 'ext',
            'sysext'    => PATH_typo3 . 'sysext',
        ];
        $this->packageStatesPathAndFilename = PATH_typo3conf . 'PackageStates.php';
    }

    /**
     * @param \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $coreCache
     */
    public function injectCoreCache(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $coreCache)
    {
        $this->coreCache = $coreCache;
    }

    /**
     * @param DependencyResolver
     */
    public function injectDependencyResolver(DependencyResolver $dependencyResolver)
    {
        $this->dependencyResolver = $dependencyResolver;
    }

    /**
     * Initializes the package manager
     *
     * @param Bootstrap $bootstrap The current bootstrap
     *
     * @return void
     */
    public function initialize(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;

        $loadedFromCache = false;
        try {
            $this->loadPackageManagerStatesFromCache();
            $loadedFromCache = true;
        } catch (Exception\PackageManagerCacheUnavailableException $exception) {
            $this->loadPackageStates();
            $this->initializePackageObjects();
            $this->initializeCompatibilityLoadedExtArray();
        }

        if (!$loadedFromCache) {
            $this->saveToPackageCache();
        }
    }

    /**
     * @return string
     */
    protected function getCacheIdentifier()
    {
        if ($this->cacheIdentifier === null) {
            $mTime = @filemtime($this->packageStatesPathAndFilename);
            if ($mTime !== false) {
                $this->cacheIdentifier = md5($this->packageStatesPathAndFilename . $mTime);
            } else {
                $this->cacheIdentifier = null;
            }
        }
        return $this->cacheIdentifier;
    }

    /**
     * @return string
     */
    protected function getCacheEntryIdentifier()
    {
        $cacheIdentifier = $this->getCacheIdentifier();
        return $cacheIdentifier !== null ? 'PackageManager_' . $cacheIdentifier : null;
    }

    /**
     * Saves the current state of all relevant information to the TYPO3 Core Cache
     */
    protected function saveToPackageCache()
    {
        $cacheEntryIdentifier = $this->getCacheEntryIdentifier();
        if ($cacheEntryIdentifier !== null && !$this->coreCache->has($cacheEntryIdentifier)) {
            // Package objects get their own cache entry, so PHP does not have to parse the serialized string
            $packageObjectsCacheEntryIdentifier = StringUtility::getUniqueId('PackageObjects_');
            // Build cache file
            $packageCache = [
                'packageStatesConfiguration'  => $this->packageStatesConfiguration,
                'packageAliasMap' => $this->packageAliasMap,
                'packageKeys' => $this->packageKeys,
                'activePackageKeys' => array_keys($this->activePackages),
                'requiredPackageKeys' => $this->requiredPackageKeys,
                'loadedExtArray' => $GLOBALS['TYPO3_LOADED_EXT'],
                'packageObjectsCacheEntryIdentifier' => $packageObjectsCacheEntryIdentifier
            ];
            $this->coreCache->set($packageObjectsCacheEntryIdentifier, serialize($this->packages));
            $this->coreCache->set(
                $cacheEntryIdentifier,
                'return ' . PHP_EOL . var_export($packageCache, true) . ';'
            );
        }
    }

    /**
     * Attempts to load the package manager states from cache
     *
     * @throws Exception\PackageManagerCacheUnavailableException
     */
    protected function loadPackageManagerStatesFromCache()
    {
        $cacheEntryIdentifier = $this->getCacheEntryIdentifier();
        if ($cacheEntryIdentifier === null || !$this->coreCache->has($cacheEntryIdentifier) || !($packageCache = $this->coreCache->requireOnce($cacheEntryIdentifier))) {
            throw new Exception\PackageManagerCacheUnavailableException('The package state cache could not be loaded.', 1393883342);
        }
        $this->packageStatesConfiguration = $packageCache['packageStatesConfiguration'];
        $this->packageAliasMap = $packageCache['packageAliasMap'];
        $this->packageKeys = $packageCache['packageKeys'];
        $this->requiredPackageKeys = $packageCache['requiredPackageKeys'];
        $GLOBALS['TYPO3_LOADED_EXT'] = $packageCache['loadedExtArray'];
        $GLOBALS['TYPO3_currentPackageManager'] = $this;
        // Strip off PHP Tags from Php Cache Frontend
        $packageObjects = substr(substr($this->coreCache->get($packageCache['packageObjectsCacheEntryIdentifier']), 6), 0, -2);
        $this->packages = unserialize($packageObjects);
        foreach ($packageCache['activePackageKeys'] as $activePackageKey) {
            $this->activePackages[$activePackageKey] = $this->packages[$activePackageKey];
        }
        unset($GLOBALS['TYPO3_currentPackageManager']);
    }

    /**
     * Loads the states of available packages from the PackageStates.php file.
     * The result is stored in $this->packageStatesConfiguration.
     *
     * @throws Exception\PackageStatesUnavailableException
     * @return void
     */
    protected function loadPackageStates()
    {
        $this->packageStatesConfiguration = @include($this->packageStatesPathAndFilename) ?: [];
        if (!isset($this->packageStatesConfiguration['version']) || $this->packageStatesConfiguration['version'] < 4) {
            $this->packageStatesConfiguration = [];
        }
        if ($this->packageStatesConfiguration !== []) {
            $this->registerPackagesFromConfiguration();
        } else {
            throw new Exception\PackageStatesUnavailableException('The PackageStates.php file is either corrupt or unavailable.', 1381507733);
        }
    }

    /**
     * Initializes activePackages and requiredPackageKeys properties
     *
     * Saves PackageStates.php if list of required extensions has changed.
     *
     * @return void
     */
    protected function initializePackageObjects()
    {
        $requiredPackages = [];
        foreach ($this->packages as $packageKey => $package) {
            if ($package->isProtected()) {
                $requiredPackages[$packageKey] = $package;
            }
            if (isset($this->packageStatesConfiguration['packages'][$packageKey]['state']) && $this->packageStatesConfiguration['packages'][$packageKey]['state'] === 'active') {
                $this->activePackages[$packageKey] = $package;
            }
        }
        $previousActivePackages = $this->activePackages;
        $this->activePackages = array_merge($requiredPackages, $this->activePackages);
        $this->requiredPackageKeys = array_keys($requiredPackages);

        if ($this->activePackages != $previousActivePackages) {
            foreach ($this->requiredPackageKeys as $requiredPackageKey) {
                $this->packageStatesConfiguration['packages'][$requiredPackageKey]['state'] = 'active';
            }
            $this->sortAndSavePackageStates();
        }
    }

    /**
     * Initializes a backwards compatibility $GLOBALS['TYPO3_LOADED_EXT'] array
     *
     * @return void
     */
    protected function initializeCompatibilityLoadedExtArray()
    {
        $loadedExtObj = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($this);
        $GLOBALS['TYPO3_LOADED_EXT'] = $loadedExtObj->toArray();
    }

    /**
     * Scans all directories in the packages directories for available packages.
     * For each package a Package object is created and stored in $this->packages.
     *
     * @return void
     */
    public function scanAvailablePackages()
    {
        $previousPackageStatesConfiguration = $this->packageStatesConfiguration;

        if (isset($this->packageStatesConfiguration['packages'])) {
            foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $configuration) {
                if (!@file_exists($this->packagesBasePath . $configuration['packagePath'])) {
                    unset($this->packageStatesConfiguration['packages'][$packageKey]);
                }
            }
        } else {
            $this->packageStatesConfiguration['packages'] = [];
        }

        foreach ($this->packagesBasePaths as $key => $packagesBasePath) {
            if (!is_dir($packagesBasePath)) {
                unset($this->packagesBasePaths[$key]);
            }
        }

        $packagePaths = $this->scanLegacyExtensions();
        foreach ($this->packagesBasePaths as $packagesBasePath) {
            $packagePaths = $this->scanPackagesInPath($packagesBasePath, $packagePaths);
        }

        foreach ($packagePaths as $packagePath) {
            $packagesBasePath = $this->packagesBasePath;
            foreach ($this->packagesBasePaths as $basePath) {
                if (strpos($packagePath, $basePath) === 0) {
                    $packagesBasePath = $basePath;
                    break;
                }
            }
            try {
                $composerManifest = $this->getComposerManifest($packagePath);
                $packageKey = $this->getPackageKeyFromManifest($composerManifest, $packagePath, $packagesBasePath);
                $this->composerNameToPackageKeyMap[strtolower($composerManifest->name)] = $packageKey;
                $this->packageStatesConfiguration['packages'][$packageKey]['composerName'] = $composerManifest->name;
            } catch (Exception\MissingPackageManifestException $exception) {
                $relativePackagePath = substr($packagePath, strlen($packagesBasePath));
                $packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
                if (!$this->isPackageKeyValid($packageKey)) {
                    continue;
                }
            } catch (Exception\InvalidPackageKeyException $exception) {
                continue;
            }
            if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['state'])) {
                $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
            }

            $this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $packagePath);
        }

        $registerOnlyNewPackages = !empty($this->packages);
        $this->registerPackagesFromConfiguration($registerOnlyNewPackages);
        if ($this->packageStatesConfiguration != $previousPackageStatesConfiguration) {
            $this->sortAndsavePackageStates();
        }
    }

    /**
     * Fetches all directories from sysext/global/local locations and checks if the extension contains an ext_emconf.php
     *
     * @param array $collectedExtensionPaths
     * @return array
     */
    protected function scanLegacyExtensions(&$collectedExtensionPaths = [])
    {
        $legacyCmsPackageBasePathTypes = ['sysext', 'global', 'local'];
        foreach ($this->packagesBasePaths as $type => $packageBasePath) {
            if (!in_array($type, $legacyCmsPackageBasePathTypes, true)) {
                continue;
            }
            /** @var $fileInfo \SplFileInfo */
            foreach (new \DirectoryIterator($packageBasePath) as $fileInfo) {
                if (!$fileInfo->isDir()) {
                    continue;
                }
                $filename = $fileInfo->getFilename();
                if ($filename[0] !== '.') {
                    // Fix Windows backslashes
                    // we can't use GeneralUtility::fixWindowsFilePath as we have to keep double slashes for Unit Tests (vfs://)
                    $currentPath = str_replace('\\', '/', $fileInfo->getPathName()) . '/';
                    // Only add the extension if we have an EMCONF and the extension is not yet registered.
                    // This is crucial in order to allow overriding of system extension by local extensions
                    // and strongly depends on the order of paths defined in $this->packagesBasePaths.
                    if (file_exists($currentPath . 'ext_emconf.php') && !isset($collectedExtensionPaths[$filename])) {
                        $collectedExtensionPaths[$filename] = $currentPath;
                    }
                }
            }
        }
        return $collectedExtensionPaths;
    }

    /**
     * Looks for composer.json in the given path and returns TRUE or FALSE if an ext_emconf.php exists
     * or no composer.json is found.
     *
     * @param string $packagePath
     * @return bool TRUE if a composer.json exists or FALSE if none exists
     */
    protected function hasComposerManifestFile($packagePath)
    {
        // If an ext_emconf.php file is found, we don't need to look further
        if (file_exists($packagePath . 'ext_emconf.php')) {
            return false;
        }
        if (file_exists($packagePath . 'composer.json')) {
            return true;
        }
        return false;
    }

    /**
     * Requires and registers all packages which were defined in packageStatesConfiguration
     *
     * @param bool $registerOnlyNewPackages
     * @return void
     */
    protected function registerPackagesFromConfiguration($registerOnlyNewPackages = false)
    {
        $packageStatesHasChanged = false;
        foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $stateConfiguration) {
            if ($registerOnlyNewPackages && $this->isPackageAvailable($packageKey)) {
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
            } catch (Exception\InvalidPackagePathException $exception) {
                $this->unregisterPackageByPackageKey($packageKey);
                $packageStatesHasChanged = true;
                continue;
            } catch (Exception\InvalidPackageKeyException $exception) {
                $this->unregisterPackageByPackageKey($packageKey);
                $packageStatesHasChanged = true;
                continue;
            } catch (Exception\InvalidPackageManifestException $exception) {
                $this->unregisterPackageByPackageKey($packageKey);
                $packageStatesHasChanged = true;
                continue;
            }

            $this->registerPackage($package, false);

            $this->packageKeys[strtolower($packageKey)] = $packageKey;
            if ($stateConfiguration['state'] === 'active') {
                $this->activePackages[$packageKey] = $this->packages[$packageKey];
            }
        }
        if ($packageStatesHasChanged) {
            $this->sortAndSavePackageStates();
        }
    }

    /**
     * Register a native TYPO3 package
     *
     * @param PackageInterface $package The Package to be registered
     * @param bool $sortAndSave allows for not saving packagestates when used in loops etc.
     * @return PackageInterface
     * @throws Exception\InvalidPackageStateException
     * @throws Exception\PackageStatesFileNotWritableException
     */
    public function registerPackage(PackageInterface $package, $sortAndSave = true)
    {
        $packageKey = $package->getPackageKey();
        if ($this->isPackageAvailable($packageKey)) {
            throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is already registered.', 1338996122);
        }

        $this->packages[$packageKey] = $package;
        $this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $package->getPackagePath());

        if ($sortAndSave === true) {
            $this->sortAndSavePackageStates();
        }

        if ($package instanceof PackageInterface) {
            foreach ($package->getPackageReplacementKeys() as $packageToReplace => $versionConstraint) {
                $this->packageAliasMap[strtolower($packageToReplace)] = $package->getPackageKey();
            }
        }
        return $package;
    }

    /**
     * Unregisters a package from the list of available packages
     *
     * @param string $packageKey Package Key of the package to be unregistered
     * @return void
     */
    protected function unregisterPackageByPackageKey($packageKey)
    {
        try {
            $package = $this->getPackage($packageKey);
            if ($package instanceof PackageInterface) {
                foreach ($package->getPackageReplacementKeys() as $packageToReplace => $versionConstraint) {
                    unset($this->packageAliasMap[strtolower($packageToReplace)]);
                }
            }
        } catch (Exception\UnknownPackageException $e) {
        }
        unset($this->packages[$packageKey]);
        unset($this->packageKeys[strtolower($packageKey)]);
        unset($this->packageStatesConfiguration['packages'][$packageKey]);
    }

    /**
     * Resolves a TYPO3 package key from a composer package name.
     *
     * @param string $composerName
     * @return string
     */
    public function getPackageKeyFromComposerName($composerName)
    {
        if (isset($this->packageAliasMap[$composerName])) {
            return $this->packageAliasMap[$composerName];
        }
        if (empty($this->composerNameToPackageKeyMap)) {
            foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $packageStateConfiguration) {
                $this->composerNameToPackageKeyMap[strtolower($packageStateConfiguration['composerName'])] = $packageKey;
            }
            // Hard coded compatibility layer for old cms extension
            // @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
            $this->composerNameToPackageKeyMap['typo3/cms-cms'] = 'cms';
        }
        $lowercasedComposerName = strtolower($composerName);
        if (!isset($this->composerNameToPackageKeyMap[$lowercasedComposerName])) {
            return $composerName;
        }
        return $this->composerNameToPackageKeyMap[$lowercasedComposerName];
    }

    /**
     * Returns a PackageInterface object for the specified package.
     * A package is available, if the package directory contains valid MetaData information.
     *
     * @param string $packageKey
     * @return PackageInterface The requested package object
     * @throws Exception\UnknownPackageException if the specified package is not known
     * @api
     */
    public function getPackage($packageKey)
    {
        if (isset($this->packageAliasMap[$lowercasedPackageKey = strtolower($packageKey)])) {
            $packageKey = $this->packageAliasMap[$lowercasedPackageKey];
        }
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception\UnknownPackageException('Package "' . $packageKey . '" is not available. Please check if the package exists and that the package key is correct (package keys are case sensitive).', 1166546734);
        }
        return $this->packages[$packageKey];
    }

    /**
     * Returns TRUE if a package is available (the package's files exist in the packages directory)
     * or FALSE if it's not. If a package is available it doesn't mean necessarily that it's active!
     *
     * @param string $packageKey The key of the package to check
     * @return bool TRUE if the package is available, otherwise FALSE
     * @api
     */
    public function isPackageAvailable($packageKey)
    {
        if (isset($this->packageAliasMap[$lowercasedPackageKey = strtolower($packageKey)])) {
            $packageKey = $this->packageAliasMap[$lowercasedPackageKey];
        }
        return isset($this->packages[$packageKey]);
    }

    /**
     * Returns TRUE if a package is activated or FALSE if it's not.
     *
     * @param string $packageKey The key of the package to check
     * @return bool TRUE if package is active, otherwise FALSE
     * @api
     */
    public function isPackageActive($packageKey)
    {
        return isset($this->runtimeActivatedPackages[$packageKey]) || isset($this->activePackages[$packageKey]);
    }

    /**
     * Deactivates a package and updates the packagestates configuration
     *
     * @param string $packageKey
     * @throws Exception\PackageStatesFileNotWritableException
     * @throws Exception\ProtectedPackageKeyException
     * @throws Exception\UnknownPackageException
     */
    public function deactivatePackage($packageKey)
    {
        $this->sortAvailablePackagesByDependencies();

        foreach ($this->packageStatesConfiguration['packages'] as $packageStateKey => $packageStateConfiguration) {
            if ($packageKey === $packageStateKey || empty($packageStateConfiguration['dependencies']) || $packageStateConfiguration['state'] !== 'active') {
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
            throw new Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be deactivated.', 1308662891);
        }

        unset($this->activePackages[$packageKey]);
        $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
        $this->sortAndSavePackageStates();
    }

    /**
     * @param string $packageKey
     */
    public function activatePackage($packageKey)
    {
        $package = $this->getPackage($packageKey);
        $this->registerTransientClassLoadingInformationForPackage($package);

        if ($this->isPackageActive($packageKey)) {
            return;
        }

        $this->activePackages[$packageKey] = $package;
        $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
        if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['packagePath'])) {
            $this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $package->getPackagePath());
        }
        $this->sortAndSavePackageStates();
    }

    /**
     * Enables packages during runtime, but no class aliases will be available
     *
     * @param string $packageKey
     * @api
     */
    public function activatePackageDuringRuntime($packageKey)
    {
        $package = $this->getPackage($packageKey);
        $this->runtimeActivatedPackages[$package->getPackageKey()] = $package;
        if (!isset($GLOBALS['TYPO3_LOADED_EXT'][$package->getPackageKey()])) {
            $loadedExtArrayElement = new LoadedExtensionArrayElement($package);
            $GLOBALS['TYPO3_LOADED_EXT'][$package->getPackageKey()] = $loadedExtArrayElement->toArray();
        }
        $this->registerTransientClassLoadingInformationForPackage($package);
    }

    /**
     * @param PackageInterface $package
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function registerTransientClassLoadingInformationForPackage(PackageInterface $package)
    {
        if (Bootstrap::usesComposerClassLoading()) {
            return;
        }
        ClassLoadingInformation::registerTransientClassLoadingInformationForPackage($package);
    }

    /**
     * Removes a package from the file system.
     *
     * @param string $packageKey
     * @throws Exception
     * @throws Exception\InvalidPackageStateException
     * @throws Exception\ProtectedPackageKeyException
     * @throws Exception\UnknownPackageException
     */
    public function deletePackage($packageKey)
    {
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception\UnknownPackageException('Package "' . $packageKey . '" is not available and cannot be removed.', 1166543253);
        }

        $package = $this->getPackage($packageKey);
        if ($package->isProtected()) {
            throw new Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be removed.', 1220722120);
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
     * manager settings. This method returns runtime activated packages too
     *
     * @return PackageInterface[]
     * @api
     */
    public function getActivePackages()
    {
        return array_merge($this->activePackages, $this->runtimeActivatedPackages);
    }

    /**
     * Orders all packages by comparing their dependencies. By this, the packages
     * and package configurations arrays holds all packages in the correct
     * initialization order.
     *
     * @return void
     */
    protected function sortAvailablePackagesByDependencies()
    {
        $this->resolvePackageDependencies();

        // sort the packages by key at first, so we get a stable sorting of "equivalent" packages afterwards
        ksort($this->packageStatesConfiguration['packages']);
        $this->packageStatesConfiguration['packages'] = $this->dependencyResolver->sortPackageStatesConfigurationByDependency($this->packageStatesConfiguration['packages']);

        // Reorder the packages according to the loading order
        $newPackages = [];
        foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $_) {
            $newPackages[$packageKey] = $this->packages[$packageKey];
        }
        $this->packages = $newPackages;
    }

    /**
     * Resolves the dependent packages from the meta data of all packages recursively. The
     * resolved direct or indirect dependencies of each package will put into the package
     * states configuration array.
     *
     * @return void
     */
    protected function resolvePackageDependencies()
    {
        foreach ($this->packages as $packageKey => $package) {
            $this->packageStatesConfiguration['packages'][$packageKey]['dependencies'] = $this->getDependencyArrayForPackage($packageKey);
        }
        foreach ($this->packages as $packageKey => $package) {
            $this->packageStatesConfiguration['packages'][$packageKey]['suggestions'] = $this->getSuggestionArrayForPackage($packageKey);
        }
    }

    /**
     * Returns an array of suggested package keys for the given package.
     *
     * @param string $packageKey The package key to fetch the suggestions for
     * @return array|NULL An array of directly suggested packages
     */
    protected function getSuggestionArrayForPackage($packageKey)
    {
        if (!isset($this->packages[$packageKey])) {
            return null;
        }
        $suggestedPackageKeys = [];
        $suggestedPackageConstraints = $this->packages[$packageKey]->getPackageMetaData()->getConstraintsByType(MetaData::CONSTRAINT_TYPE_SUGGESTS);
        foreach ($suggestedPackageConstraints as $constraint) {
            if ($constraint instanceof MetaData\PackageConstraint) {
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
    protected function sortAndSavePackageStates()
    {
        $this->sortAvailablePackagesByDependencies();

        $this->packageStatesConfiguration['version'] = 4;

        $fileDescription = "# PackageStates.php\n\n";
        $fileDescription .= "# This file is maintained by TYPO3's package management. Although you can edit it\n";
        $fileDescription .= "# manually, you should rather use the extension manager for maintaining packages.\n";
        $fileDescription .= "# This file will be regenerated automatically if it doesn't exist. Deleting this file\n";
        $fileDescription .= "# should, however, never become necessary if you use the package commands.\n";

        // We do not need the dependencies on disk...
        foreach ($this->packageStatesConfiguration['packages'] as &$packageConfiguration) {
            if (isset($packageConfiguration['dependencies'])) {
                unset($packageConfiguration['dependencies']);
            }
        }
        if (!@is_writable($this->packageStatesPathAndFilename)) {
            // If file does not exists try to create it
            $fileHandle = @fopen($this->packageStatesPathAndFilename, 'x');
            if (!$fileHandle) {
                throw new Exception\PackageStatesFileNotWritableException(
                    sprintf('We could not update the list of installed packages because the file %s is not writable. Please, check the file system permissions for this file and make sure that the web server can update it.', $this->packageStatesPathAndFilename),
                    1382449759
                );
            }
            fclose($fileHandle);
        }
        $packageStatesCode = "<?php\n$fileDescription\nreturn " . ArrayUtility::arrayExport($this->packageStatesConfiguration) . ";\n";
        GeneralUtility::writeFile($this->packageStatesPathAndFilename, $packageStatesCode, true);

        $this->initializeCompatibilityLoadedExtArray();

        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive($this->packageStatesPathAndFilename);
    }

    /**
     * Check the conformance of the given package key
     *
     * @param string $packageKey The package key to validate
     * @return bool If the package key is valid, returns TRUE otherwise FALSE
     * @api
     */
    public function isPackageKeyValid($packageKey)
    {
        return preg_match(PackageInterface::PATTERN_MATCH_PACKAGEKEY, $packageKey) === 1 || preg_match(PackageInterface::PATTERN_MATCH_EXTENSIONKEY, $packageKey) === 1;
    }

    /**
     * Returns an array of \TYPO3\CMS\Core\Package objects of all available packages.
     * A package is available, if the package directory contains valid meta information.
     *
     * @return PackageInterface[] Array of PackageInterface
     * @api
     */
    public function getAvailablePackages()
    {
        return $this->packages;
    }

    /**
     * Unregisters a package from the list of available packages
     *
     * @param PackageInterface $package The package to be unregistered
     * @return void
     * @throws Exception\InvalidPackageStateException
     */
    public function unregisterPackage(PackageInterface $package)
    {
        $packageKey = $package->getPackageKey();
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is not registered.', 1338996142);
        }
        $this->unregisterPackageByPackageKey($packageKey);
    }

    /**
     * Reloads a package and its information
     *
     * @param string $packageKey
     * @throws Exception\InvalidPackageStateException if the package isn't available
     * @throws Exception\InvalidPackageKeyException if an invalid package key was passed
     * @throws Exception\InvalidPackagePathException if an invalid package path was passed
     * @throws Exception\InvalidPackageManifestException if no extension configuration file could be found
     */
    public function reloadPackageInformation($packageKey)
    {
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is not registered.', 1436201329);
        }

        /** @var PackageInterface $package */
        $package = $this->packages[$packageKey];
        $packagePath = $package->getPackagePath();
        $newPackage = new Package($this, $packageKey, $packagePath);
        $this->packages[$packageKey] = $newPackage;
        unset($package);
    }

    /**
     * Scans all sub directories of the specified directory and collects the package keys of packages it finds.
     *
     * The return of the array is to make this method usable in array_merge.
     *
     * @param string $startPath
     * @param array $collectedPackagePaths
     * @return array
     */
    protected function scanPackagesInPath($startPath, array $collectedPackagePaths)
    {
        foreach (new \DirectoryIterator($startPath) as $fileInfo) {
            if (!$fileInfo->isDir()) {
                continue;
            }
            $filename = $fileInfo->getFilename();
            if ($filename[0] !== '.') {
                $currentPath = $fileInfo->getPathName();
                $currentPath = PathUtility::sanitizeTrailingSeparator($currentPath);
                if ($this->hasComposerManifestFile($currentPath)) {
                    $collectedPackagePaths[$currentPath] = $currentPath;
                }
            }
        }
        return $collectedPackagePaths;
    }

    /**
     * Returns contents of Composer manifest as a stdObject
     *
     * @param string $manifestPath
     * @return \stdClass
     * @throws Exception\InvalidPackageManifestException
     */
    public function getComposerManifest($manifestPath)
    {
        $composerManifest = null;
        if (file_exists($manifestPath . 'composer.json')) {
            $json = file_get_contents($manifestPath . 'composer.json');
            $composerManifest = json_decode($json);
            if (!$composerManifest instanceof \stdClass) {
                throw new Exception\InvalidPackageManifestException('The composer.json found for extension "' . basename($manifestPath) . '" is invalid!', 1439555561);
            }
        }

        $extensionManagerConfiguration = $this->getExtensionEmConf($manifestPath);
        $composerManifest = $this->mapExtensionManagerConfigurationToComposerManifest(
            basename($manifestPath),
            $extensionManagerConfiguration,
            $composerManifest ?: new \stdClass()
        );

        return $composerManifest;
    }

    /**
     * Fetches MetaData information from ext_emconf.php, used for
     * resolving dependencies as well.
     *
     * @param string $packagePath
     * @return array
     * @throws Exception\InvalidPackageManifestException
     */
    protected function getExtensionEmConf($packagePath)
    {
        $packageKey = basename($packagePath);
        $_EXTKEY = $packageKey;
        $path = $packagePath . 'ext_emconf.php';
        $EM_CONF = null;
        if (@file_exists($path)) {
            include $path;
            if (is_array($EM_CONF[$_EXTKEY])) {
                return $EM_CONF[$_EXTKEY];
            }
        }
        throw new Exception\InvalidPackageManifestException('No valid ext_emconf.php file found for package "' . $packageKey . '".', 1360403545);
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
        $this->setComposerManifestValueIfEmpty($composerManifest, 'description', $extensionManagerConfiguration['title']);
        $composerManifest->version = $extensionManagerConfiguration['version'];
        if (isset($extensionManagerConfiguration['constraints']['depends']) && is_array($extensionManagerConfiguration['constraints']['depends'])) {
            $composerManifest->require = new \stdClass();
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
                    throw new Exception\InvalidPackageManifestException(sprintf('The extension "%s" has invalid version constraints in depends section. Extension key is missing!', $packageKey), 1439552058);
                }
            }
        }
        if (isset($extensionManagerConfiguration['constraints']['conflicts']) && is_array($extensionManagerConfiguration['constraints']['conflicts'])) {
            $composerManifest->conflict = new \stdClass();
            foreach ($extensionManagerConfiguration['constraints']['conflicts'] as $conflictingPackageKey => $conflictingPackageVersion) {
                if (!empty($conflictingPackageKey)) {
                    $composerManifest->conflict->$conflictingPackageKey = $conflictingPackageVersion;
                } else {
                    throw new Exception\InvalidPackageManifestException(sprintf('The extension "%s" has invalid version constraints in conflicts section. Extension key is missing!', $packageKey), 1439552059);
                }
            }
        }
        if (isset($extensionManagerConfiguration['constraints']['suggests']) && is_array($extensionManagerConfiguration['constraints']['suggests'])) {
            $composerManifest->suggest = new \stdClass();
            foreach ($extensionManagerConfiguration['constraints']['suggests'] as $suggestedPackageKey => $suggestedPackageVersion) {
                if (!empty($suggestedPackageKey)) {
                    $composerManifest->suggest->$suggestedPackageKey = $suggestedPackageVersion;
                } else {
                    throw new Exception\InvalidPackageManifestException(sprintf('The extension "%s" has invalid version constraints in suggests section. Extension key is missing!', $packageKey), 1439552060);
                }
            }
        }
        if (isset($extensionManagerConfiguration['autoload'])) {
            $composerManifest->autoload = json_decode(json_encode($extensionManagerConfiguration['autoload']));
        }
        // composer.json autoload-dev information must be discarded, as it may contain information only available after a composer install
        unset($composerManifest->{'autoload-dev'});
        if (isset($extensionManagerConfiguration['autoload-dev'])) {
            $composerManifest->{'autoload-dev'} = json_decode(json_encode($extensionManagerConfiguration['autoload-dev']));
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
     * do this recursively, so dependencies of dependant packages will also be
     * in the result.
     *
     * @param string $packageKey The package key to fetch the dependencies for
     * @param array $dependentPackageKeys
     * @param array $trace An array of already visited package keys, to detect circular dependencies
     * @return array|NULL An array of direct or indirect dependant packages
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
            if ($constraint instanceof MetaData\PackageConstraint) {
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
     * @param string $packagesBasePath
     * @throws Exception\InvalidPackageManifestException
     * @return string
     */
    protected function getPackageKeyFromManifest($manifest, $packagePath, $packagesBasePath)
    {
        if (!is_object($manifest)) {
            throw new Exception\InvalidPackageManifestException('Invalid composer manifest in package path: ' . $packagePath, 1348146451);
        }
        if (isset($manifest->type) && substr($manifest->type, 0, 10) === 'typo3-cms-') {
            $relativePackagePath = substr($packagePath, strlen($packagesBasePath));
            $packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
            return preg_replace('/[^A-Za-z0-9._-]/', '', $packageKey);
        } else {
            $packageKey = str_replace('/', '.', $manifest->name);
            return preg_replace('/[^A-Za-z0-9.]/', '', $packageKey);
        }
    }
}
