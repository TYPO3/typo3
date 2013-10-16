<?php
namespace TYPO3\CMS\Core\Package;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The default TYPO3 Package Manager
 * Adapted from FLOW for TYPO3 CMS
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PackageManager extends \TYPO3\Flow\Package\PackageManager implements \TYPO3\CMS\Core\SingletonInterface {


	/**
	 * @var \TYPO3\CMS\Core\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var \TYPO3\CMS\Core\Core\Bootstrap
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
	protected $extAutoloadClassFiles;

	/**
	 * @var array
	 */
	protected $packagesBasePaths = array();

	/**
	 * @var array
	 */
	protected $packageAliasMap = array();

	/**
	 * Adjacency matrix for the dependency graph (DAG)
	 *
	 * Example structure is:
	 *    A => (A => FALSE, B => TRUE,  C => FALSE)
	 *    B => (A => FALSE, B => FALSE, C => FALSE)
	 *    C => (A => TRUE,  B => FALSE, C => FALSE)
	 *
	 *    A depends on B, C depends on A, B is independent
	 *
	 * @var array<array<boolean>>
	 */
	protected $dependencyGraph;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->packagesBasePaths = array(
			'local'     => PATH_typo3conf . 'ext',
			'global'    => PATH_typo3 . 'ext',
			'sysext'    => PATH_typo3 . 'sysext',
			'composer'  => PATH_site . 'Packages',
		);
	}

	/**
	 * @param \TYPO3\CMS\Core\Core\ClassLoader $classLoader
	 */
	public function injectClassLoader(\TYPO3\CMS\Core\Core\ClassLoader $classLoader) {
		$this->classLoader = $classLoader;
	}

	/**
	 * @param \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $coreCache
	 */
	public function injectCoreCache(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $coreCache) {
		$this->coreCache = $coreCache;
	}

	/**
	 * Initializes the package manager
	 *
	 * @param \TYPO3\CMS\Core\Core\Bootstrap $bootstrap The current bootstrap; Flow Bootstrap is here by intention to keep the PackageManager valid to the interface
	 * @param string $packagesBasePath Absolute path of the Packages directory
	 * @param string $packageStatesPathAndFilename
	 * @return void
	 */
	public function initialize(\TYPO3\Flow\Core\Bootstrap $bootstrap, $packagesBasePath = PATH_site, $packageStatesPathAndFilename = '') {

		$this->bootstrap = $bootstrap;
		$this->packagesBasePath = $packagesBasePath;
		$this->packageStatesPathAndFilename = ($packageStatesPathAndFilename === '') ? PATH_typo3conf . 'PackageStates.php' : $packageStatesPathAndFilename;
		$this->packageFactory = new PackageFactory($this);

		$this->loadPackageStates();

		$requiredList = array();
		foreach ($this->packages as $packageKey => $package) {
			$protected = $package->isProtected();
			if ($protected) {
				$requiredList[$packageKey] = $package;
			}
			if (isset($this->packageStatesConfiguration['packages'][$packageKey]['state']) && $this->packageStatesConfiguration['packages'][$packageKey]['state'] === 'active') {
				$this->activePackages[$packageKey] = $package;
			}
		}
		$previousActivePackage = $this->activePackages;
		$this->activePackages = array_merge($requiredList, $this->activePackages);

		if ($this->activePackages != $previousActivePackage) {
			foreach ($requiredList as $requiredPackageKey => $package) {
				$this->packageStatesConfiguration['packages'][$requiredPackageKey]['state'] = 'active';
			}
			$this->sortAndSavePackageStates();
		}

		//@deprecated since 6.2, don't use
		if (!defined('REQUIRED_EXTENSIONS')) {
			// List of extensions required to run the core
			define('REQUIRED_EXTENSIONS', implode(',', array_keys($requiredList)));
		}

		$cacheIdentifier = $this->getCacheIdentifier();
		if ($cacheIdentifier === NULL) {
			// Create an artificial cache identifier if the package states file is not available yet
			// in order that the class loader and class alias map can cache anyways.
			$cacheIdentifier = substr(md5(implode('###', array_keys($this->activePackages))), 0, 13);
		}
		$this->classLoader->setCacheIdentifier($cacheIdentifier)->setPackages($this->activePackages);

		foreach ($this->activePackages as $package) {
			$package->boot($bootstrap);
		}

		$this->saveToPackageCache();
	}

	/**
	 * @return string
	 */
	protected function getCacheIdentifier() {
		if ($this->cacheIdentifier === NULL) {
			if (@file_exists($this->packageStatesPathAndFilename)) {
				$this->cacheIdentifier = substr(md5_file($this->packageStatesPathAndFilename), 0, 13);
			} else {
				$this->cacheIdentifier = NULL;
			}
		}
		return $this->cacheIdentifier;
	}

	/**
	 * @return string
	 */
	protected function getCacheEntryIdentifier() {
		$cacheIdentifier = $this->getCacheIdentifier();
		return $cacheIdentifier !== NULL ? 'PackageManager_' . $cacheIdentifier : NULL;
	}

	/**
	 *
	 */
	protected function saveToPackageCache() {
		$cacheEntryIdentifier = $this->getCacheEntryIdentifier();
		if ($cacheEntryIdentifier !== NULL && !$this->coreCache->has($cacheEntryIdentifier)) {
			$cacheEntryPath = rtrim(\TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath($this->coreCache->getBackend()->getCacheDirectory()), '/');
			// Package objects get their own cache entry, so PHP does not have to parse the serialized string
			$packageObjectsCacheEntryIdentifier = uniqid('PackageObjects_');
			// Build cache file
			$packageCache = array(
				'packageStatesConfiguration'  => $this->packageStatesConfiguration,
				'packageAliasMap' => $this->packageAliasMap,
				'packageKeys' => $this->packageKeys,
				'declaringPackageClassPathsAndFilenames' => array(),
				'packageObjectsCacheEntryIdentifier' => $packageObjectsCacheEntryIdentifier
			);
			foreach ($this->packages as $package) {
				if (!isset($packageCache['declaringPackageClassPathsAndFilenames'][$packageClassName = get_class($package)])) {
					$reflectionPackageClass = new \ReflectionClass($packageClassName);
					$packageCache['declaringPackageClassPathsAndFilenames'][$packageClassName] = $reflectionPackageClass->getFileName();
				}
			}
			$this->coreCache->set($packageObjectsCacheEntryIdentifier, serialize($this->packages));
			$this->coreCache->set(
				$cacheEntryIdentifier,
				'return __DIR__ !== \'' . $cacheEntryPath . '\' ? FALSE : ' . PHP_EOL .
					var_export($packageCache, TRUE) . ';'
			);
		}
	}

	/**
	 * Loads the states of available packages from the PackageStates.php file.
	 * The result is stored in $this->packageStatesConfiguration.
	 *
	 * @return void
	 */
	protected function loadPackageStates() {
		$cacheEntryIdentifier = $this->getCacheEntryIdentifier();
		if ($cacheEntryIdentifier !== NULL && $this->coreCache->has($cacheEntryIdentifier) && $packageCache = $this->coreCache->requireOnce($cacheEntryIdentifier)) {
			foreach ($packageCache['declaringPackageClassPathsAndFilenames'] as $packageClassPathAndFilename) {
				require_once $packageClassPathAndFilename;
			}
			$this->packageStatesConfiguration = $packageCache['packageStatesConfiguration'];
			$this->packageAliasMap = $packageCache['packageAliasMap'];
			$this->packageKeys = $packageCache['packageKeys'];
			$GLOBALS['TYPO3_currentPackageManager'] = $this;
			// Strip off PHP Tags from Php Cache Frontend
			$packageObjects = substr(substr($this->coreCache->get($packageCache['packageObjectsCacheEntryIdentifier']), 6), 0, -2);
			$this->packages = unserialize($packageObjects);
			unset($GLOBALS['TYPO3_currentPackageManager']);
		} else {
			$this->packageStatesConfiguration = @include($this->packageStatesPathAndFilename) ?: array();
			if (!isset($this->packageStatesConfiguration['version']) || $this->packageStatesConfiguration['version'] < 4) {
				$this->packageStatesConfiguration = array();
			}
			if ($this->packageStatesConfiguration !== array()) {
				$this->registerPackagesFromConfiguration();
			} else {
				throw new Exception\PackageStatesUnavailableException('The PackageStates.php file is either corrupt or unavailable.', 1381507733);
			}
		}
	}


	/**
	 * Scans all directories in the packages directories for available packages.
	 * For each package a Package object is created and stored in $this->packages.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Package\Exception\DuplicatePackageException
	 */
	public function scanAvailablePackages() {
		$previousPackageStatesConfiguration = $this->packageStatesConfiguration;

		if (isset($this->packageStatesConfiguration['packages'])) {
			foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $configuration) {
				if (!@file_exists($this->packagesBasePath . $configuration['packagePath'])) {
					unset($this->packageStatesConfiguration['packages'][$packageKey]);
				}
			}
		} else {
			$this->packageStatesConfiguration['packages'] = array();
		}

		foreach ($this->packagesBasePaths as $key => $packagesBasePath) {
			if (!is_dir($packagesBasePath)) {
				unset($this->packagesBasePaths[$key]);
			}
		}

		$packagePaths = $this->scanLegacyExtensions();
		foreach ($this->packagesBasePaths as $packagesBasePath) {
			$this->scanPackagesInPath($packagesBasePath, $packagePaths);
		}

		foreach ($packagePaths as $packagePath => $composerManifestPath) {
			$packagesBasePath = PATH_site;
			foreach ($this->packagesBasePaths as $basePath) {
				if (strpos($packagePath, $basePath) === 0) {
					$packagesBasePath = $basePath;
					break;
				}
			}
			try {
				$composerManifest = self::getComposerManifest($composerManifestPath);
				$packageKey = \TYPO3\CMS\Core\Package\PackageFactory::getPackageKeyFromManifest($composerManifest, $packagePath, $packagesBasePath);
				$this->composerNameToPackageKeyMap[strtolower($composerManifest->name)] = $packageKey;
				$this->packageStatesConfiguration['packages'][$packageKey]['manifestPath'] = substr($composerManifestPath, strlen($packagePath)) ? : '';
				$this->packageStatesConfiguration['packages'][$packageKey]['composerName'] = $composerManifest->name;
			} catch (\TYPO3\Flow\Package\Exception\MissingPackageManifestException $exception) {
				$relativePackagePath = substr($packagePath, strlen($packagesBasePath));
				$packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
			} catch (\TYPO3\Flow\Package\Exception\InvalidPackageKeyException $exception) {
				continue;
			}
			if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['state'])) {
				$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
			}

			$this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $packagePath);

			// Change this to read the target from Composer or any other source
			$this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = \TYPO3\Flow\Package\Package::DIRECTORY_CLASSES;
		}

		$registerOnlyNewPackages = !empty($this->packages);
		$this->registerPackagesFromConfiguration($registerOnlyNewPackages);
		if ($this->packageStatesConfiguration != $previousPackageStatesConfiguration) {
			$this->sortAndsavePackageStates();
		}
	}

	/**
	 * @return array
	 */
	protected function scanLegacyExtensions(&$collectedExtensionPaths = array()) {
		$legacyCmsPackageBasePathTypes = array('sysext', 'global', 'local');
		foreach ($this->packagesBasePaths as $type => $packageBasePath) {
			if (!in_array($type, $legacyCmsPackageBasePathTypes)) {
				continue;
			}
			/** @var $fileInfo \SplFileInfo */
			foreach (new \DirectoryIterator($packageBasePath) as $fileInfo) {
				if (!$fileInfo->isDir()) {
					continue;
				}
				$filename = $fileInfo->getFilename();
				if ($filename[0] !== '.') {
					$currentPath = \TYPO3\Flow\Utility\Files::getUnixStylePath($fileInfo->getPathName()) . '/';
					if (file_exists($currentPath . 'ext_emconf.php')) {
						$collectedExtensionPaths[$currentPath] = $currentPath;
					}
				}
			}
		}
		return $collectedExtensionPaths;
	}

	/**
	 * Looks for composer.json in the given path and returns a path or NULL.
	 *
	 * @param string $packagePath
	 * @return array
	 */
	protected function findComposerManifestPaths($packagePath) {
		// If an ext_emconf.php file is found, we don't need to look deeper
		if (file_exists($packagePath . '/ext_emconf.php')) {
			return array();
		}
		return parent::findComposerManifestPaths($packagePath);
	}

	/**
	 * Requires and registers all packages which were defined in packageStatesConfiguration
	 *
	 * @param boolean $registerOnlyNewPackages
	 * @return void
	 * @throws \TYPO3\Flow\Package\Exception\CorruptPackageException
	 */
	protected function registerPackagesFromConfiguration($registerOnlyNewPackages = FALSE) {
		foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $stateConfiguration) {

			if ($registerOnlyNewPackages && $this->isPackageAvailable($packageKey)) {
				continue;
			}

			$packagePath = isset($stateConfiguration['packagePath']) ? $stateConfiguration['packagePath'] : NULL;
			$classesPath = isset($stateConfiguration['classesPath']) ? $stateConfiguration['classesPath'] : NULL;
			$manifestPath = isset($stateConfiguration['manifestPath']) ? $stateConfiguration['manifestPath'] : NULL;

			try {
				$package = $this->packageFactory->create($this->packagesBasePath, $packagePath, $packageKey, $classesPath, $manifestPath);
			} catch (\TYPO3\Flow\Package\Exception\InvalidPackagePathException $exception) {
				$this->unregisterPackageByPackageKey($packageKey);
				continue;
			} catch (\TYPO3\Flow\Package\Exception\InvalidPackageKeyException $exception) {
				$this->unregisterPackageByPackageKey($packageKey);
				continue;
			}

			$this->registerPackage($package, FALSE);

			if (!$this->packages[$packageKey] instanceof \TYPO3\Flow\Package\PackageInterface) {
				throw new \TYPO3\Flow\Package\Exception\CorruptPackageException(sprintf('The package class in package "%s" does not implement PackageInterface.', $packageKey), 1300782487);
			}

			$this->packageKeys[strtolower($packageKey)] = $packageKey;
			if ($stateConfiguration['state'] === 'active') {
				$this->activePackages[$packageKey] = $this->packages[$packageKey];
			}
		}
	}

	/**
	 * Register a native Flow package
	 *
	 * @param string $packageKey The Package to be registered
	 * @param boolean $sortAndSave allows for not saving packagestates when used in loops etc.
	 * @return \TYPO3\Flow\Package\PackageInterface
	 * @throws \TYPO3\Flow\Package\Exception\CorruptPackageException
	 */
	public function registerPackage(\TYPO3\Flow\Package\PackageInterface $package, $sortAndSave = TRUE) {
		$package = parent::registerPackage($package, $sortAndSave);
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
	protected function unregisterPackageByPackageKey($packageKey) {
		try {
			$package = $this->getPackage($packageKey);
			if ($package instanceof PackageInterface) {
				foreach ($package->getPackageReplacementKeys() as $packageToReplace => $versionConstraint) {
					unset($this->packageAliasMap[strtolower($packageToReplace)]);
				}
				$packageKey = $package->getPackageKey();
			}
		} catch (\TYPO3\Flow\Package\Exception\UnknownPackageException $e) {
		}
		parent::unregisterPackageByPackageKey($packageKey);
	}

	/**
	 * Resolves a Flow package key from a composer package name.
	 *
	 * @param string $composerName
	 * @return string
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackageStateException
	 */
	public function getPackageKeyFromComposerName($composerName) {
		if (isset($this->packageAliasMap[$composerName])) {
			return $this->packageAliasMap[$composerName];
		}
		try {
			return parent::getPackageKeyFromComposerName($composerName);
		} catch (\TYPO3\Flow\Package\Exception\InvalidPackageStateException $exception) {
			return $composerName;
		}
	}

	/**
	 * @return array
	 */
	public function getExtAutoloadRegistry() {
		if (!isset($this->extAutoloadClassFiles)) {
			$classRegistry = array();
			foreach ($this->activePackages as $packageKey => $packageData) {
				try {
					$extensionAutoloadFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($packageKey, 'ext_autoload.php');
					if (@file_exists($extensionAutoloadFile)) {
						$classRegistry = array_merge($classRegistry, require $extensionAutoloadFile);
					}
				} catch (\BadFunctionCallException $e) {
				}
			}
			$this->extAutoloadClassFiles = $classRegistry;
		}
		return $this->extAutoloadClassFiles;
	}

	/**
	 * Returns a PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid MetaData information.
	 *
	 * @param string $packageKey
	 * @return \TYPO3\Flow\Package\PackageInterface The requested package object
	 * @throws \TYPO3\Flow\Package\Exception\UnknownPackageException if the specified package is not known
	 * @api
	 */
	public function getPackage($packageKey) {
		if (isset($this->packageAliasMap[$lowercasedPackageKey = strtolower($packageKey)])) {
			$packageKey = $this->packageAliasMap[$lowercasedPackageKey];
		}
		return parent::getPackage($packageKey);
	}

	/**
	 * Returns TRUE if a package is available (the package's files exist in the packages directory)
	 * or FALSE if it's not. If a package is available it doesn't mean necessarily that it's active!
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if the package is available, otherwise FALSE
	 * @api
	 */
	public function isPackageAvailable($packageKey) {
		if (isset($this->packageAliasMap[$lowercasedPackageKey = strtolower($packageKey)])) {
			$packageKey = $this->packageAliasMap[$lowercasedPackageKey];
		}
		return parent::isPackageAvailable($packageKey);
	}

	/**
	 * Returns TRUE if a package is activated or FALSE if it's not.
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if package is active, otherwise FALSE
	 * @api
	 */
	public function isPackageActive($packageKey) {
		if (isset($this->packageAliasMap[$lowercasedPackageKey = strtolower($packageKey)])) {
			$packageKey = $this->packageAliasMap[$lowercasedPackageKey];
		}
		return parent::isPackageActive($packageKey);
	}

	/**
	 * @param string $packageKey
	 */
	public function deactivatePackage($packageKey) {
		$package = $this->getPackage($packageKey);
		parent::deactivatePackage($package->getPackageKey());
	}

	/**
	 * @param string $packageKey
	 */
	public function activatePackage($packageKey) {
		$package = $this->getPackage($packageKey);
		parent::activatePackage($package->getPackageKey());
	}


	/**
	 * @param string $packageKey
	 */
	public function deletePackage($packageKey) {
		$package = $this->getPackage($packageKey);
		parent::deletePackage($package->getPackageKey());
	}


	/**
	 * @param string $packageKey
	 */
	public function freezePackage($packageKey) {
		$package = $this->getPackage($packageKey);
		parent::freezePackage($package->getPackageKey());
	}

	/**
	 * @param string $packageKey
	 */
	public function isPackageFrozen($packageKey) {
		$package = $this->getPackage($packageKey);
		parent::isPackageFrozen($package->getPackageKey());
	}

	/**
	 * @param string $packageKey
	 */
	public function unfreezePackage($packageKey) {
		$package = $this->getPackage($packageKey);
		parent::unfreezePackage($package->getPackageKey());
	}

	/**
	 * @param string $packageKey
	 */
	public function refreezePackage($packageKey) {
		$package = $this->getPackage($packageKey);
		parent::refreezePackage($package->getPackageKey());
	}


	/**
	 * Get packages of specific type
	 *
	 * @param string $type Type of package. Empty string for all types
	 * @param array $excludedTypes Array of package types to exclude
	 * @return array List of packages
	 */
	protected function getPackageKeysOfType($type, array $excludedTypes = array()) {
		$packageKeys = array();
		foreach ($this->packages as $packageKey => $package) {
			$packageType = $package->getComposerManifest('type');
			if (($type === '' || $packageType === $type) && !in_array($packageType, $excludedTypes)) {
				$packageKeys[] = $packageKey;
			}
		}
		return $packageKeys;
	}

	/**
	 * Build the dependency graph for the given packages
	 *
	 * @param array $packageKeys
	 * @return void
	 * @throws \UnexpectedValueException
	 */
	protected function buildDependencyGraphForPackages(array $packageKeys) {
		// Initialize the dependencies with FALSE
		$this->dependencyGraph = array_fill_keys($packageKeys, array_fill_keys($packageKeys, FALSE));
		foreach ($packageKeys as $packageKey) {
			$dependentPackageKeys = $this->packageStatesConfiguration['packages'][$packageKey]['dependencies'];
			foreach ($dependentPackageKeys as $dependentPackageKey) {
				if (!in_array($dependentPackageKey, $packageKeys)) {
					throw new \UnexpectedValueException(
						'The package "' . $packageKey .'" depends on "'
						. $dependentPackageKey . '" which is not present in the system.',
						1382276561);
				}
				$this->dependencyGraph[$packageKey][$dependentPackageKey] = TRUE;
			}
		}
	}

	/**
	 * Adds all root packages of current dependency graph as dependency
	 * to all extensions
	 *
	 * @return void
	 */
	protected function addDependencyToFrameworkToAllExtensions() {
		$rootPackageKeys = array();
		foreach (array_keys($this->dependencyGraph) as $packageKey) {
			if (!$this->getIncomingEdgeCount($packageKey)) {
				$rootPackageKeys[] = $packageKey;
			}
		}
		$extensionPackageKeys = $this->getPackageKeysOfType('', array('typo3-cms-framework'));
		$frameworkPackageKeys = $this->getPackageKeysOfType('typo3-cms-framework');
		foreach ($extensionPackageKeys as $packageKey) {
			// Remove framework packages from list
			$packageKeysWithoutFramework = array_diff(
				$this->packageStatesConfiguration['packages'][$packageKey]['dependencies'],
				$frameworkPackageKeys
			);
			// The order of the array_merge is crucial here,
			// we want the framework first
			$this->packageStatesConfiguration['packages'][$packageKey]['dependencies'] = array_merge(
				$rootPackageKeys, $packageKeysWithoutFramework
			);
		}
	}

	/**
	 * Builds the dependency graph for all packages
	 *
	 * This method also introduces dependencies among the dependencies
	 * to ensure the loading order is exactly as specified in the list.
	 *
	 * @return void
	 */
	protected function buildDependencyGraph() {
		$this->resolvePackageDependencies();

		$frameworkPackageKeys = $this->getPackageKeysOfType('typo3-cms-framework');
		$this->buildDependencyGraphForPackages($frameworkPackageKeys);

		$this->addDependencyToFrameworkToAllExtensions();

		$packageKeys = array_keys($this->packages);
		$this->buildDependencyGraphForPackages($packageKeys);
	}

	/**
	 * Get the number of incoming edges in the dependency graph
	 * for given package key.
	 *
	 * @param string $packageKey
	 * @return integer
	 */
	protected function getIncomingEdgeCount($packageKey) {
		$incomingEdgeCount = 0;
		foreach ($this->dependencyGraph as $dependencies) {
			if ($dependencies[$packageKey]) {
				$incomingEdgeCount++;
			}
		}
		return $incomingEdgeCount;
	}

	/**
	 * Get the loading order for packages
	 *
	 * @return array The properly sorted loading order
	 * @throws \UnexpectedValueException
	 */
	protected function getAvailablePackageLoadingOrder() {
		$this->buildDependencyGraph();

		// This will contain our final result
		$sortedPackageKeys = array();

		$rootPackageKeys = array();
		// Filter extensions with no incoming edge
		foreach (array_keys($this->dependencyGraph) as $packageKey) {
			if (!$this->getIncomingEdgeCount($packageKey)) {
				$rootPackageKeys[] = $packageKey;
			}
		}

		while (count($rootPackageKeys)) {
			$currentPackageKey = array_shift($rootPackageKeys);
			array_push($sortedPackageKeys, $currentPackageKey);

			$dependingPackageKeys = array_keys(array_filter($this->dependencyGraph[$currentPackageKey]));
			foreach ($dependingPackageKeys as $dependingPackageKey) {
				// Remove the edge to this dependency
				$this->dependencyGraph[$currentPackageKey][$dependingPackageKey] = FALSE;
				if (!$this->getIncomingEdgeCount($dependingPackageKey)) {
					// We found a new root, lets add it
					array_unshift($rootPackageKeys, $dependingPackageKey);
				}
			}
		}

		// Check for remaining edges in the graph
		$cycles = array();
		array_walk($this->dependencyGraph, function($dependencies, $packageKeyFrom) use(&$cycles) {
			array_walk($dependencies, function($dependency, $packageKeyTo) use(&$cycles, $packageKeyFrom) {
				if ($dependency) {
					$cycles[] = $packageKeyFrom . '->' . $packageKeyTo;
				}
			});
		});
		if (count($cycles)) {
			throw new \UnexpectedValueException('Your dependencies have cycles. That will not work out. Cycles found: ' . implode(', ', $cycles), 1381960493);
		}

		// We built now a list of dependencies
		// Reverse the list to get the correct loading order
		return array_reverse($sortedPackageKeys);
	}

	/**
	 * Orders all packages by comparing their dependencies. By this, the packages
	 * and package configurations arrays holds all packages in the correct
	 * initialization order.
	 *
	 * @return void
	 */
	protected function sortAvailablePackagesByDependencies() {
		$newPackages = array();
		$newPackageStatesConfiguration = array();

		$sortedPackageKeys = $this->getAvailablePackageLoadingOrder();

		// Reorder the packages according to the loading order
		foreach ($sortedPackageKeys as $packageKey) {
			$newPackages[$packageKey] = $this->packages[$packageKey];
			$newPackageStatesConfiguration[$packageKey] = $this->packageStatesConfiguration['packages'][$packageKey];
		}

		$this->packages = $newPackages;
		$this->packageStatesConfiguration['packages'] = $newPackageStatesConfiguration;
	}
}
