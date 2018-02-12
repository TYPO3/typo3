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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
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
     * Constructor
     */
    public function __construct()
    {
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
     * @param DependencyResolver $dependencyResolver
     */
    public function injectDependencyResolver(DependencyResolver $dependencyResolver)
    {
        $this->dependencyResolver = $dependencyResolver;
    }

    /**
     * Initializes the package manager
     */
    public function initialize()
    {
        try {
            $this->loadPackageManagerStatesFromCache();
        } catch (Exception\PackageManagerCacheUnavailableException $exception) {
            $this->loadPackageStates();
            $this->initializePackageObjects();
            $this->initializeCompatibilityLoadedExtArray();
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
                'packageStatesConfiguration' => $this->packageStatesConfiguration,
                'packageAliasMap' => $this->packageAliasMap,
                'loadedExtArray' => $GLOBALS['TYPO3_LOADED_EXT'],
                'composerNameToPackageKeyMap' => $this->composerNameToPackageKeyMap,
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
        if ($this->packageStatesConfiguration['version'] < 5) {
            throw new Exception\PackageManagerCacheUnavailableException('The package state cache could not be loaded.', 1393883341);
        }
        $this->packageAliasMap = $packageCache['packageAliasMap'];
        $this->composerNameToPackageKeyMap = $packageCache['composerNameToPackageKeyMap'];
        $GLOBALS['TYPO3_LOADED_EXT'] = $packageCache['loadedExtArray'];
        $GLOBALS['TYPO3_currentPackageManager'] = $this;
        // Strip off PHP Tags from Php Cache Frontend
        $packageObjects = substr(substr($this->coreCache->get($packageCache['packageObjectsCacheEntryIdentifier']), 6), 0, -2);
        $this->packages = unserialize($packageObjects);
        unset($GLOBALS['TYPO3_currentPackageManager']);
    }

    /**
     * Loads the states of available packages from the PackageStates.php file.
     * The result is stored in $this->packageStatesConfiguration.
     *
     * @throws Exception\PackageStatesUnavailableException
     */
    protected function loadPackageStates()
    {
        $forcePackageStatesRewrite = false;
        $this->packageStatesConfiguration = @include($this->packageStatesPathAndFilename) ?: [];
        if (!isset($this->packageStatesConfiguration['version']) || $this->packageStatesConfiguration['version'] < 4) {
            $this->packageStatesConfiguration = [];
        } elseif ($this->packageStatesConfiguration['version'] === 4) {
            // Convert to v5 format which only includes a list of active packages.
            // Deprecated since version 8, will be removed in version 9.
            $activePackages = [];
            foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $packageConfiguration) {
                if ($packageConfiguration['state'] !== 'active') {
                    continue;
                }
                $activePackages[$packageKey] = ['packagePath' => $packageConfiguration['packagePath']];
            }
            $this->packageStatesConfiguration['packages'] = $activePackages;
            $this->packageStatesConfiguration['version'] = 5;
            $forcePackageStatesRewrite = true;
        }
        if ($this->packageStatesConfiguration !== []) {
            $this->registerPackagesFromConfiguration($this->packageStatesConfiguration['packages'], false, $forcePackageStatesRewrite);
        } else {
            throw new Exception\PackageStatesUnavailableException('The PackageStates.php file is either corrupt or unavailable.', 1381507733);
        }
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
        $this->packageStatesConfiguration['packages'][$package->getPackageKey()] = ['packagePath' => str_replace($this->packagesBasePath, '', $package->getPackagePath())];
    }

    /**
     * Initializes a backwards compatibility $GLOBALS['TYPO3_LOADED_EXT'] array
     */
    protected function initializeCompatibilityLoadedExtArray()
    {
        $loadedExtObj = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($this);
        $GLOBALS['TYPO3_LOADED_EXT'] = $loadedExtObj->toArray();
    }

    /**
     * Scans all directories in the packages directories for available packages.
     * For each package a Package object is created and stored in $this->packages.
     */
    public function scanAvailablePackages()
    {
        $packagePaths = $this->scanPackagePathsForExtensions();
        $packages = [];
        foreach ($packagePaths as $packageKey => $packagePath) {
            try {
                $composerManifest = $this->getComposerManifest($packagePath);
                $packageKey = $this->getPackageKeyFromManifest($composerManifest, $packagePath);
                $this->composerNameToPackageKeyMap[strtolower($composerManifest->name)] = $packageKey;
                $packages[$packageKey] = ['packagePath' => str_replace($this->packagesBasePath, '', $packagePath)];
            } catch (Exception\MissingPackageManifestException $exception) {
                if (!$this->isPackageKeyValid($packageKey)) {
                    continue;
                }
            } catch (Exception\InvalidPackageKeyException $exception) {
                continue;
            }
        }

        $this->availablePackagesScanned = true;
        $registerOnlyNewPackages = !empty($this->packages);
        $this->registerPackagesFromConfiguration($packages, $registerOnlyNewPackages);
    }

    /**
     * Scans all directories for a certain package.
     *
     * @param string $packageKey
     * @return PackageInterface
     */
    protected function registerPackageDuringRuntime($packageKey)
    {
        $packagePaths = $this->scanPackagePathsForExtensions();
        $packagePath = $packagePaths[$packageKey];
        $composerManifest = $this->getComposerManifest($packagePath);
        $packageKey = $this->getPackageKeyFromManifest($composerManifest, $packagePath);
        $this->composerNameToPackageKeyMap[strtolower($composerManifest->name)] = $packageKey;
        $packagePath = PathUtility::sanitizeTrailingSeparator($packagePath);
        $package = new Package($this, $packageKey, $packagePath);
        $this->registerPackage($package);
        return $package;
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
                $path = dirname($fileInfo->getPathname());
                $extensionName = basename($path);
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
     * @param bool $packageStatesHasChanged
     * @throws Exception\InvalidPackageStateException
     * @throws Exception\PackageStatesFileNotWritableException
     */
    protected function registerPackagesFromConfiguration(array $packages, $registerOnlyNewPackages = false, $packageStatesHasChanged = false)
    {
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
     */
    public function registerPackage(PackageInterface $package)
    {
        $packageKey = $package->getPackageKey();
        if ($this->isPackageRegistered($packageKey)) {
            throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is already registered.', 1338996122);
        }

        $this->packages[$packageKey] = $package;

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
        $lowercasedComposerName = strtolower($composerName);
        if (isset($this->packageAliasMap[$lowercasedComposerName])) {
            return $this->packageAliasMap[$lowercasedComposerName];
        }
        if (isset($this->composerNameToPackageKeyMap[$lowercasedComposerName])) {
            return $this->composerNameToPackageKeyMap[$lowercasedComposerName];
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
     * @api
     */
    public function getPackage($packageKey)
    {
        if (!$this->isPackageRegistered($packageKey) && !$this->isPackageAvailable($packageKey)) {
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
     * @api
     */
    public function isPackageActive($packageKey)
    {
        $packageKey = $this->getPackageKeyFromComposerName($packageKey);

        return isset($this->runtimeActivatedPackages[$packageKey]) || isset($this->packageStatesConfiguration['packages'][$packageKey]);
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
            throw new Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be deactivated.', 1308662891);
        }

        $this->activePackages = [];
        unset($this->packageStatesConfiguration['packages'][$packageKey]);
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

        $this->registerActivePackage($package);
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
        $package = $this->registerPackageDuringRuntime($packageKey);
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
        if (empty($this->activePackages)) {
            if (!empty($this->packageStatesConfiguration['packages'])) {
                foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $packageConfig) {
                    $this->activePackages[$packageKey] = $this->getPackage($packageKey);
                }
            }
        }
        return array_merge($this->activePackages, $this->runtimeActivatedPackages);
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
        $sortedPackageKeys = $this->dependencyResolver->sortPackageStatesConfigurationByDependency($packagesWithDependencies);

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
     * @param $packageConfig
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
        $this->sortActivePackagesByDependencies();

        $this->packageStatesConfiguration['version'] = 5;

        $fileDescription = "# PackageStates.php\n\n";
        $fileDescription .= "# This file is maintained by TYPO3's package management. Although you can edit it\n";
        $fileDescription .= "# manually, you should rather use the extension manager for maintaining packages.\n";
        $fileDescription .= "# This file will be regenerated automatically if it doesn't exist. Deleting this file\n";
        $fileDescription .= "# should, however, never become necessary if you use the package commands.\n";

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
     */
    public function unregisterPackage(PackageInterface $package)
    {
        $packageKey = $package->getPackageKey();
        if (!$this->isPackageRegistered($packageKey)) {
            throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is not registered.', 1338996142);
        }
        $this->unregisterPackageByPackageKey($packageKey);
    }

    /**
     * Reloads a package and its information
     *
     * @param string $packageKey
     * @throws Exception\InvalidPackageStateException if the package isn't available
     */
    public function reloadPackageInformation($packageKey)
    {
        if (!$this->isPackageRegistered($packageKey)) {
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
        $this->setComposerManifestValueIfEmpty($composerManifest, 'authors', [['name' => $extensionManagerConfiguration['author'], 'email' => $extensionManagerConfiguration['author_email']]]);
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
     * @throws Exception\InvalidPackageManifestException
     * @return string
     */
    protected function getPackageKeyFromManifest($manifest, $packagePath)
    {
        if (!is_object($manifest)) {
            throw new Exception\InvalidPackageManifestException('Invalid composer manifest in package path: ' . $packagePath, 1348146451);
        }
        if (isset($manifest->type) && substr($manifest->type, 0, 10) === 'typo3-cms-') {
            $packageKey = basename($packagePath);
            return preg_replace('/[^A-Za-z0-9._-]/', '', $packageKey);
        }
        $packageKey = str_replace('/', '.', $manifest->name);
        return preg_replace('/[^A-Za-z0-9.]/', '', $packageKey);
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
            if (is_dir(PATH_typo3conf . 'ext') && $this->hasSubDirectories(PATH_typo3conf . 'ext')) {
                $this->packagesBasePaths['local'] = PATH_typo3conf . 'ext/*/';
            }
            if (is_dir(PATH_typo3 . 'ext') && $this->hasSubDirectories(PATH_typo3 . 'ext')) {
                $this->packagesBasePaths['global'] = PATH_typo3 . 'ext/*/';
            }
            $this->packagesBasePaths['system'] = PATH_typo3 . 'sysext/*/';
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
}
