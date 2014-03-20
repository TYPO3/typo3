<?php
namespace TYPO3\CMS\Core\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Maroschik <tmaroschik@dfau.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Locking\Locker;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Cache;

/**
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 */
class ClassLoader {

	const VALID_CLASSNAME_PATTERN = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9\\\\_\x7f-\xff]*$/';

	/**
	 * @var ClassAliasMap
	 */
	protected $classAliasMap;

	/**
	 * @var ClassAliasMap
	 */
	static protected $staticAliasMap;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\StringFrontend
	 */
	protected $classesCache;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
	 */
	protected $coreCache;

	/**
	 * @var string
	 */
	protected $cacheIdentifier;

	/**
	 * @var \TYPO3\Flow\Package\Package[]
	 */
	protected $packages = array();

	/**
	 * @var bool
	 */
	protected $isEarlyCache = TRUE;

	/**
	 * @var array
	 */
	protected $runtimeClassLoadingInformationCache = array();

	/**
	 * @var array A list of namespaces this class loader is definitely responsible for
	 */
	protected $packageNamespaces = array();

	/**
	 * @var array A list of packages and their replaces pointing to class paths
	 */
	protected $packageClassesPaths = array();

	/**
	 * @var bool Is TRUE while loading the Locker class to prevent a deadlock in the implicit call to loadClass
	 */
	protected $isLoadingLocker = FALSE;

	/**
	 * @var \TYPO3\CMS\Core\Locking\Locker
	 */
	protected $lockObject = NULL;

	/**
	 * Constructor
	 *
	 * @param ApplicationContext $context
	 */
	public function __construct(ApplicationContext $context) {
		$this->classesCache = new Cache\Frontend\StringFrontend('cache_classes', new Cache\Backend\TransientMemoryBackend($context));
	}

	/**
	 * Get class alias map list injected
	 *
	 * @param ClassAliasMap
	 * @return void
	 */
	public function injectClassAliasMap(ClassAliasMap $classAliasMap) {
		$this->classAliasMap = $classAliasMap;
		static::$staticAliasMap = $classAliasMap;
	}

	/**
	 * Get core cache injected
	 *
	 * @param \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $coreCache
	 * @return void
	 */
	public function injectCoreCache(Cache\Frontend\PhpFrontend $coreCache) {
		$this->coreCache = $coreCache;
		$this->classAliasMap->injectCoreCache($coreCache);
	}

	/**
	 * Get classes cache injected
	 *
	 * @param \TYPO3\CMS\Core\Cache\Frontend\StringFrontend $classesCache
	 * @return void
	 */
	public function injectClassesCache(Cache\Frontend\StringFrontend $classesCache) {
		$earlyClassesCache = $this->classesCache;
		$this->classesCache = $classesCache;
		$this->isEarlyCache = FALSE;
		$this->classAliasMap->injectClassesCache($classesCache);
		foreach ($earlyClassesCache->getByTag('early') as $originalClassLoadingInformation) {
			$classLoadingInformation = explode("\xff", $originalClassLoadingInformation);
			$cacheEntryIdentifier = strtolower(str_replace('\\', '_', $classLoadingInformation[1]));
			if (!$this->classesCache->has($cacheEntryIdentifier)) {
				$this->classesCache->set($cacheEntryIdentifier, $originalClassLoadingInformation);
			}
		}
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * a package and specifically registered classes.
	 *
	 * Caution: This function may be called "recursively" by the spl_autoloader if a class depends on another classes.
	 *
	 * @param string $className Name of the class/interface to load
	 * @return bool
	 */
	public function loadClass($className) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}

		if (!$this->isValidClassName($className)) {
			return FALSE;
		}

		$cacheEntryIdentifier = strtolower(str_replace('\\', '_', $className));
		$classLoadingInformation = $this->getClassLoadingInformationFromCache($cacheEntryIdentifier);
		// Handle a cache miss
		if ($classLoadingInformation === FALSE) {
			$classLoadingInformation = $this->buildCachedClassLoadingInformation($cacheEntryIdentifier, $className);
		}

		// Class loading information structure
		// array(
		//   0 => class file path
		//   1 => original class name
		//   2 and following => alias class names
		// )
		$loadingSuccessful = FALSE;
		if (!empty($classLoadingInformation)) {
			// The call to class_exists fixes a rare case when early instances need to be aliased
			// but PHP fails to recognize the real path of the class. See #55904
			$loadingSuccessful = class_exists($classLoadingInformation[1], FALSE) || (bool)require_once $classLoadingInformation[0];
		}
		if ($loadingSuccessful && count($classLoadingInformation) > 2) {
			$originalClassName = $classLoadingInformation[1];
			foreach (array_slice($classLoadingInformation, 2) as $aliasClassName) {
				$this->setAliasForClassName($aliasClassName, $originalClassName);
			}
		}

		return $loadingSuccessful;
	}

	/**
	 * Get class loading information for the given identifier for cache
	 * Return values:
	 *  - array with class information (empty if the class is invalid)
	 *  - FALSE if no class information is found in cache (cache miss)
	 *  - NULL if the cache identifier is invalid (cache failure)
	 *
	 * @param string $cacheEntryIdentifier The identifier to fetch entry from cache
	 * @return array|FALSE The class information, empty array if class is unkown or FALSE if class information was not found in cache.
	 */
	public function getClassLoadingInformationFromCache($cacheEntryIdentifier) {
		$rawClassLoadingInformation = $this->classesCache->get($cacheEntryIdentifier);

		if ($rawClassLoadingInformation === '') {
			return array();
		}

		if ($rawClassLoadingInformation) {
			return explode("\xff", $rawClassLoadingInformation);
		}
		return FALSE;
	}

	/**
	 * Builds the class loading information and writes it to the cache. It handles Locking for this cache.
	 *
	 * Caution: The function loadClass can be called "recursively" by spl_autoloader. This needs to be observed when
	 * locking for cache access. Only the first call to loadClass may acquire and release the lock!
	 *
	 * @param string $cacheEntryIdentifier Cache identifier for this class
	 * @param string $className Name of class this information is for
	 *
	 * @return array|FALSE The class information, empty array if class is unkown or FALSE if class information was not found in cache.
	 */
	protected function buildCachedClassLoadingInformation($cacheEntryIdentifier, $className) {
		// We do not need locking if we are in earlyCache mode
		$didLock = FALSE;
		if (!$this->isEarlyCache) {
			$didLock = $this->acquireLock();
		}

		// Look again into the cache after we got the lock, data might have been generated meanwhile
		$classLoadingInformation = $this->getClassLoadingInformationFromCache($cacheEntryIdentifier);
		// Handle repeated cache miss
		if ($classLoadingInformation === FALSE) {
			// Generate class information
			$classLoadingInformation = $this->buildClassLoadingInformation($className);

			if ($classLoadingInformation !== FALSE) {
				// If we found class information, cache it
				$this->classesCache->set(
					$cacheEntryIdentifier,
					implode("\xff", $classLoadingInformation),
					$this->isEarlyCache ? array('early') : array()
				);
			} elseif (!$this->isEarlyCache) {
				// Cache that the class is unknown
				$this->classesCache->set($cacheEntryIdentifier, '');
			}
		}

		$this->releaseLock($didLock);

		return $classLoadingInformation;
	}

	/**
	 * Builds the class loading information
	 *
	 * @param string $className Name of class this information is for
	 *
	 * @return array|FALSE The class information or FALSE if class was not found
	 */
	public function buildClassLoadingInformation($className) {
		$classLoadingInformation = $this->buildClassLoadingInformationForClassFromCorePackage($className);

		if ($classLoadingInformation === FALSE) {
			$classLoadingInformation = $this->fetchClassLoadingInformationFromRuntimeCache($className);
		}

		if ($classLoadingInformation === FALSE) {
			$classLoadingInformation = $this->buildClassLoadingInformationForClassFromRegisteredPackages($className);
		}

		if ($classLoadingInformation === FALSE) {
			$classLoadingInformation = $this->buildClassLoadingInformationForClassByNamingConvention($className);
		}

		return $classLoadingInformation;
	}

	/**
	 * Find out if a class name is valid
	 *
	 * @param string $className
	 * @return bool
	 */
	protected function isValidClassName($className) {
		return (bool)preg_match(self::VALID_CLASSNAME_PATTERN, $className);
	}

	/**
	 * Retrieve class loading information for class from core package
	 *
	 * @param string $className
	 * @return array|FALSE
	 */
	protected function buildClassLoadingInformationForClassFromCorePackage($className) {
		if (substr($className, 0, 14) === 'TYPO3\\CMS\\Core') {
			$classesFolder = substr($className, 15, 5) === 'Tests' ? '' : 'Classes/';
			$classFilePath = PATH_typo3 . 'sysext/core/' . $classesFolder . str_replace('\\', '/', substr($className, 15)) . '.php';
			if (@file_exists($classFilePath)) {
				return array($classFilePath, $className);
			}
		}
		return FALSE;
	}

	/**
	 * Retrieve class loading information from early class name autoload registry cache
	 *
	 * @param string $className
	 * @return array|FALSE
	 */
	protected function fetchClassLoadingInformationFromRuntimeCache($className) {
		$lowercasedClassName = strtolower($className);
		if (!isset($this->runtimeClassLoadingInformationCache[$lowercasedClassName])) {
			return FALSE;
		}
		$classInformation = $this->runtimeClassLoadingInformationCache[$lowercasedClassName];
		return @file_exists($classInformation[0]) ? $classInformation : FALSE;
	}

	/**
	 * Retrieve class loading information from registered packages
	 *
	 * @param string $className
	 * @return array|FALSE
	 */
	protected function buildClassLoadingInformationForClassFromRegisteredPackages($className) {;
		foreach ($this->packageNamespaces as $packageNamespace => $packageData) {
			if (substr(str_replace('_', '\\', $className), 0, $packageData['namespaceLength']) === $packageNamespace) {
				if ($packageData['substituteNamespaceInPath']) {
					// If it's a TYPO3 package, classes don't comply to PSR-0.
					// The namespace part is substituted.
					$classPathAndFilename = '/' . str_replace('\\', '/', ltrim(substr($className, $packageData['namespaceLength']), '\\')) . '.php';
				} else {
					// Make the classname PSR-0 compliant by replacing underscores only in the classname not in the namespace
					$classPathAndFilename  = '';
					$lastNamespacePosition = strrpos($className, '\\');
					if ($lastNamespacePosition !== FALSE) {
						$namespace = substr($className, 0, $lastNamespacePosition);
						$className = substr($className, $lastNamespacePosition + 1);
						$classPathAndFilename  = str_replace('\\', '/', $namespace) . '/';
					}
					$classPathAndFilename .= str_replace('_', '/', $className) . '.php';
				}
				if (strtolower(substr($className, $packageData['namespaceLength'], 5)) === 'tests') {
					$classPathAndFilename = $packageData['packagePath'] . $classPathAndFilename;
				} else {
					$classPathAndFilename = $packageData['classesPath'] . $classPathAndFilename;
				}
				if (@file_exists($classPathAndFilename)) {
					return array($classPathAndFilename, $className);
				}
			}
		}
		return FALSE;
	}

	/**
	 * Retrieve class loading information based on 'extbase' naming convention into the registry.
	 *
	 * @param string $className Class name to find source file of
	 * @return array|FALSE
	 */
	protected function buildClassLoadingInformationForClassByNamingConvention($className) {
		$delimiter = '_';
		// To handle namespaced class names, split the class name at the
		// namespace delimiters.
		if (strpos($className, '\\') !== FALSE) {
			$delimiter = '\\';
		}

		$classNameParts = explode($delimiter, $className, 4);

		// We only handle classes that follow the convention Vendor\Product\Classname or is longer
		// so we won't deal with class names that only have one or two parts
		if (count($classNameParts) <= 2) {
			return FALSE;
		}

		if (
				isset($classNameParts[0])
				&& isset($classNameParts[1])
				&& $classNameParts[0] === 'TYPO3'
				&& $classNameParts[1] === 'CMS'
		) {
			$extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($classNameParts[2]);
			$classNameWithoutVendorAndProduct = $classNameParts[3];
		} else {
			$extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($classNameParts[1]);
			$classNameWithoutVendorAndProduct = $classNameParts[2];

			if (isset($classNameParts[3])) {
				$classNameWithoutVendorAndProduct .= $delimiter . $classNameParts[3];
			}
		}

		if ($extensionKey && isset($this->packageClassesPaths[$extensionKey])) {
			if (substr(strtolower($classNameWithoutVendorAndProduct), 0, 5) === 'tests') {
				$classesPath = $this->packages[$extensionKey]->getPackagePath();
			} else {
				$classesPath = $this->packageClassesPaths[$extensionKey];
			}
			$classFilePath = $classesPath . strtr($classNameWithoutVendorAndProduct, $delimiter, '/') . '.php';
			if (@file_exists($classFilePath)) {
				return array($classFilePath, $className);
			}
		}

		return FALSE;
	}

	/**
	 * Get cache entry identifier for the package namespaces cache
	 *
	 * @return string|NULL identifier
	 */
	protected function getCacheEntryIdentifier() {
		return $this->cacheIdentifier !== NULL
			? 'ClassLoader_' . $this->cacheIdentifier
			: NULL;
	}

	/**
	 * Set cache identifier
	 *
	 * @param string $cacheIdentifier Cache identifier for package namespaces cache
	 * @return ClassLoader
	 */
	public function setCacheIdentifier($cacheIdentifier) {
		$this->cacheIdentifier = $cacheIdentifier;
		return $this;
	}

	/**
	 * Sets the available packages
	 *
	 * @param array $packages An array of \TYPO3\Flow\Package\Package objects
	 * @return ClassLoader
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;

		if (!$this->loadPackageNamespacesFromCache()) {
			$this->buildPackageNamespacesAndClassesPaths();
		} else {
			$this->classAliasMap->setPackages($packages);
		}
		// Clear the runtime cache for runtime activated packages
		$this->runtimeClassLoadingInformationCache = array();
		return $this;
	}

	/**
	 * Add a package to class loader just during runtime, so classes can be loaded without the need for a new request
	 *
	 * @param \TYPO3\Flow\Package\PackageInterface $package
	 * @return ClassLoader
	 */
	public function addActivePackage(\TYPO3\Flow\Package\PackageInterface $package) {
		$packageKey = $package->getPackageKey();
		if (!isset($this->packages[$packageKey])) {
			$this->packages[$packageKey] = $package;
			$this->buildPackageNamespaceAndClassesPath($package);
			$this->sortPackageNamespaces();
			$this->loadClassFilesFromAutoloadRegistryIntoRuntimeClassInformationCache(array($package));
		}
		return $this;
	}

	/**
	 * Builds the package namespaces and classes paths for the given packages
	 *
	 * @return void
	 */
	protected function buildPackageNamespacesAndClassesPaths() {
		$didLock = $this->acquireLock();

		// Take a look again, after lock is acquired
		if (!$this->loadPackageNamespacesFromCache()) {
			foreach ($this->packages as $package) {
				$this->buildPackageNamespaceAndClassesPath($package);
			}
			$this->sortPackageNamespaces();
			$this->savePackageNamespacesAndClassesPathsToCache();
			// The class alias map has to be rebuilt first, because ext_autoload files can contain
			// old class names that need established class aliases.
			$classNameToAliasMapping = $this->classAliasMap->setPackages($this->packages)->buildMappingAndInitializeEarlyInstanceMapping();
			$this->loadClassFilesFromAutoloadRegistryIntoRuntimeClassInformationCache($this->packages);
			$this->classAliasMap->buildMappingFiles($classNameToAliasMapping);
			$this->transferRuntimeClassInformationCacheEntriesToClassesCache();
		}

		$this->releaseLock($didLock);
	}

	/**
	 * Builds the namespace and class paths for a single package
	 *
	 * @param \TYPO3\Flow\Package\PackageInterface $package
	 * @return void
	 */
	protected function buildPackageNamespaceAndClassesPath(\TYPO3\Flow\Package\PackageInterface $package) {
		if ($package instanceof \TYPO3\Flow\Package\PackageInterface) {
			$this->buildPackageNamespace($package);
		}
		if ($package instanceof PackageInterface) {
			$this->buildPackageClassPathsForLegacyExtension($package);
		}
	}

	/**
	 * Load package namespaces from cache
	 *
	 * @return bool TRUE if package namespaces were loaded
	 */
	protected function loadPackageNamespacesFromCache() {
		$cacheEntryIdentifier = $this->getCacheEntryIdentifier();
		if ($cacheEntryIdentifier === NULL) {
			return FALSE;
		}
		$packageData = $this->coreCache->requireOnce($cacheEntryIdentifier);
		if ($packageData !== FALSE) {
			list($packageNamespaces, $packageClassesPaths) = $packageData;
			if (is_array($packageNamespaces) && is_array($packageClassesPaths)) {
				$this->packageNamespaces = $packageNamespaces;
				$this->packageClassesPaths = $packageClassesPaths;
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Extracts the namespace from a package
	 *
	 * @param \TYPO3\Flow\Package\PackageInterface $package
	 * @return void
	 */
	protected function buildPackageNamespace(\TYPO3\Flow\Package\PackageInterface $package) {
		$packageNamespace = $package->getNamespace();
		// Ignore legacy extensions with unkown vendor name
		if ($packageNamespace[0] !== '*') {
			$this->packageNamespaces[$packageNamespace] = array(
				'namespaceLength' => strlen($packageNamespace),
				'classesPath' => $package->getClassesPath(),
				'packagePath' => $package->getPackagePath(),
				'substituteNamespaceInPath' => ($package instanceof PackageInterface)
			);
		}
	}

	/**
	 * Save autoload registry to cache
	 *
	 * @param array $packages
	 * @return void
	 */
	protected function loadClassFilesFromAutoloadRegistryIntoRuntimeClassInformationCache(array $packages) {
		$classFileAutoloadRegistry = array();
		foreach ($packages as $package) {
			if ($package instanceof PackageInterface) {
				$classFilesFromAutoloadRegistry = $package->getClassFilesFromAutoloadRegistry();
				if (is_array($classFilesFromAutoloadRegistry)) {
					$classFileAutoloadRegistry = array_merge($classFileAutoloadRegistry, $classFilesFromAutoloadRegistry);
				}
			}
		}
		foreach ($classFileAutoloadRegistry as $className => $classFilePath) {
			$lowercasedClassName = strtolower($className);
			if (!isset($this->runtimeClassLoadingInformationCache[$lowercasedClassName]) && @file_exists($classFilePath)) {
				$this->runtimeClassLoadingInformationCache[$lowercasedClassName] = array($classFilePath, $className);
			}
		}
	}

	/**
	 * Transfers all entries from the early class information cache to
	 * the classes cache in order to make them persistent
	 *
	 * @return void
	 */
	protected function transferRuntimeClassInformationCacheEntriesToClassesCache() {
		foreach ($this->runtimeClassLoadingInformationCache as $classLoadingInformation) {
			$cacheEntryIdentifier = strtolower(str_replace('\\', '_', $classLoadingInformation[1]));
			if (!$this->classesCache->has($cacheEntryIdentifier)) {
				$this->classesCache->set($cacheEntryIdentifier, implode("\xff", $classLoadingInformation));
			}
		}
	}

	/**
	 * @param PackageInterface $package
	 * @return void
	 */
	protected function buildPackageClassPathsForLegacyExtension(PackageInterface $package) {
		$this->packageClassesPaths[$package->getPackageKey()] = $package->getClassesPath();
		foreach (array_keys($package->getPackageReplacementKeys()) as $packageToReplace) {
			$this->packageClassesPaths[$packageToReplace] = $package->getClassesPath();
		}
	}

	/**
	 * Save package namespaces and classes paths to cache
	 *
	 * @return void
	 */
	protected function savePackageNamespacesAndClassesPathsToCache() {
		$cacheEntryIdentifier = $this->getCacheEntryIdentifier();
		if ($cacheEntryIdentifier !== NULL) {
			$this->coreCache->set(
				$this->getCacheEntryIdentifier(),
				'return ' . var_export(array($this->packageNamespaces, $this->packageClassesPaths), TRUE) . ';'
			);
		}
	}

	/**
	 * Sorts longer package namespaces first, to find specific matches before generic ones
	 *
	 * @return void
	 */
	protected function sortPackageNamespaces() {
		$sortPackages = function ($a, $b) {
			if (($lenA = strlen($a)) === ($lenB = strlen($b))) {
				return strcmp($a, $b);
			}
			return $lenA > $lenB ? -1 : 1;
		};
		uksort($this->packageNamespaces, $sortPackages);
	}

	/**
	 * This method is necessary for the early loading of the cores autoload registry
	 *
	 * @param array $classFileAutoloadRegistry
	 * @return void
	 */
	public function setRuntimeClassLoadingInformationFromAutoloadRegistry(array $classFileAutoloadRegistry) {
		foreach ($classFileAutoloadRegistry as $className => $classFilePath) {
			$lowercasedClassName = strtolower($className);
			if (!isset($this->runtimeClassLoadingInformationCache[$lowercasedClassName])) {
				$this->runtimeClassLoadingInformationCache[$lowercasedClassName] = array($classFilePath, $className);
			}
		}
	}

	/**
	 * Set alias for class name
	 *
	 * @param string $aliasClassName
	 * @param string $originalClassName
	 * @return bool
	 */
	public function setAliasForClassName($aliasClassName, $originalClassName) {
		return $this->classAliasMap->setAliasForClassName($aliasClassName, $originalClassName);
	}

	/**
	 * Get class name for alias
	 *
	 * @param string $alias
	 * @return mixed
	 */
	static public function getClassNameForAlias($alias) {
		return static::$staticAliasMap->getClassNameForAlias($alias);
	}

	/**
	 * Get alias for class name
	 *
	 * @param string $className
	 * @return mixed
	 * @deprecated since 6.2, will be removed 2 versions later - use getAliasesForClassName() instead
	 */
	static public function getAliasForClassName($className) {
		GeneralUtility::logDeprecatedFunction();
		$aliases = static::$staticAliasMap->getAliasesForClassName($className);
		return is_array($aliases) && isset($aliases[0]) ? $aliases[0] : NULL;
	}

	/**
	 * Get an aliases for a class name
	 *
	 * @param string $className
	 * @return mixed
	 */
	static public function getAliasesForClassName($className) {
		return static::$staticAliasMap->getAliasesForClassName($className);
	}

	/**
	 * Acquires a lock for the cache if we didn't already lock before.
	 *
	 * @return bool TRUE if the cache was acquired by this call and needs to be released
	 * @throws \RuntimeException
	 */
	protected function acquireLock() {
		if (!$this->isLoadingLocker) {
			$lockObject = $this->getLocker();

			if ($lockObject === NULL) {
				// During installation typo3temp does not yet exist, so the locker can not
				// do its job. In this case it does not need to be released again.
				return FALSE;
			}

			// We didn't lock yet so do it
			if (!$lockObject->getLockStatus()) {
				if (!$lockObject->acquireExclusiveLock()) {
					throw new \RuntimeException('Could not acquire lock for ClassLoader cache creation.', 1394480725);
				}
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Releases a lock
	 *
	 * @param bool $needRelease The result of the call to acquireLock()
	 *
	 * @return void
	 */
	protected function releaseLock($needRelease) {
		if ($needRelease) {
			$lockObject = $this->getLocker();
			$lockObject->release();
		}
	}

	/**
	 * Gets the TYPO3 Locker object or creates an instance of it.
	 *
	 * @throws \RuntimeException
	 * @return \TYPO3\CMS\Core\Locking\Locker|NULL Only NULL if we are in installer and typo3temp does not exist yet
	 */
	protected function getLocker() {
		if (NULL === $this->lockObject) {
			$this->isLoadingLocker = TRUE;

			try {
				$this->lockObject = new Locker('ClassLoader-cache-classes', Locker::LOCKING_METHOD_SIMPLE);
			} catch (\RuntimeException $e) {
				// The RuntimeException in constructor happens if directory typo3temp/locks could not be created.
				// This usually happens during installation step 1 where typo3temp itself does not exist yet. In
				// this case we proceed without locking, otherwise a missing typo3temp directory indicates a
				// hard problem of the instance and we throw up.
				// @TODO: This solution currently conflicts with separation of concerns since the class loader
				// handles installation specific stuff. Find a better way to do this.
				if (defined('TYPO3_enterInstallScript') && TYPO3_enterInstallScript) {
					// Installer is running => So work without Locking.
					return NULL;
				} else {
					throw $e;
				}
			}
			$this->lockObject->setEnableLogging(FALSE);
			$this->isLoadingLocker = FALSE;
		}

		return $this->lockObject;
	}
}
