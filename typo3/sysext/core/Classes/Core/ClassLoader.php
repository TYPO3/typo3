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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class ClassLoader {

	/**
	 * @var ClassAliasMap
	 */
	protected $classAliasMap;

	/**
	 * @var ClassAliasMap
	 */
	static protected $staticAliasMap;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * @var string
	 */
	protected $cacheIdentifier;

	/**
	 * An array of \TYPO3\Flow\Package\Package objects
	 * @var array
	 */
	protected $packages = array();

	/**
	 * @var array
	 */
	protected $earlyClassFileAutoloadRegistry = array();

	/**
	 * A list of namespaces this class loader is definitely responsible for
	 * @var array
	 */
	protected $packageNamespaces = array(
		'TYPO3\CMS\Core' => 14
	);

	/**
	 * A list of packages and their replaces pointing to class paths
	 * @var array
	 */
	protected $packageClassesPaths = array();

	public function __construct() {
		$this->classesCache = new \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend('cache_classes', new \TYPO3\CMS\Core\Cache\Backend\EarlyClassLoaderBackend());
	}

	/**
	 * @param ClassAliasMap
	 */
	public function injectClassAliasMap(ClassAliasMap $classAliasMap) {
		$this->classAliasMap = $classAliasMap;
		static::$staticAliasMap = $classAliasMap;
	}

	/**
	 * @param \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $classesCache
	 */
	public function injectClassesCache(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $classesCache) {
		/** @var $earlyClassLoaderBackend \TYPO3\CMS\Core\Cache\Backend\EarlyClassLoaderBackend */
		$earlyClassLoaderBackend = $this->classesCache->getBackend();
		$this->classesCache = $classesCache;
		$this->classAliasMap->injectClassesCache($classesCache);
		foreach ($earlyClassLoaderBackend->getAll() as $cacheEntryIdentifier => $classFilePath) {
			if (!$this->classesCache->has($cacheEntryIdentifier)) {
				$this->addClassToCache($classFilePath, $cacheEntryIdentifier);
			}
		}
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * a package and specifically registered classes.
	 *
	 * @param string $className Name of the class/interface to load
	 * @return boolean
	 */
	public function loadClass($className, $require = TRUE) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}

		$cacheEntryIdentifier = strtolower(str_replace('\\', '_', $className));
		$cacheEntryCreated = FALSE;

		// Loads any known class via caching framework
		if ($require) {
			if ($this->classesCache->has($cacheEntryIdentifier) && $this->classesCache->requireOnce($cacheEntryIdentifier) !== FALSE) {
				$cacheEntryCreated = TRUE;
			}
		}

		if (!$cacheEntryCreated) {
			$cacheEntryCreated = $this->createCacheEntryForClassFromCorePackage($className, $cacheEntryIdentifier);
		}

		if (!$cacheEntryCreated) {
			$cacheEntryCreated = $this->createCacheEntryForClassFromEarlyAutoloadRegistry($className, $cacheEntryIdentifier);
		}

		if (!$cacheEntryCreated) {
			$cacheEntryCreated = $this->createCacheEntryForClassFromRegisteredPackages($className, $cacheEntryIdentifier);
		}

		if (!$cacheEntryCreated) {
			$cacheEntryCreated = $this->createCacheEntryForClassByNamingConvention($className, $cacheEntryIdentifier);
		}

		if ($cacheEntryCreated && $require) {
			if ($this->classesCache->has($cacheEntryIdentifier) && $this->classesCache->requireOnce($cacheEntryIdentifier) !== FALSE) {
				$cacheEntryCreated = TRUE;
			}
		}

		return $cacheEntryCreated;
	}

	/**
	 * @param string $className
	 * @param string $cacheEntryIdentifier
	 * @return bool
	 */
	protected function createCacheEntryForClassFromCorePackage($className, $cacheEntryIdentifier) {
		if (substr($cacheEntryIdentifier, 0, 14) === 'typo3_cms_core') {
			$classesFolder = substr($cacheEntryIdentifier, 15, 5) === 'tests' ? '' : 'Classes/';
			$classFilePath = PATH_typo3 . 'sysext/core/' . $classesFolder . str_replace('\\', '/', substr($className, 15)) . '.php';
			if (@file_exists($classFilePath)) {
				$this->addClassToCache($classFilePath, $cacheEntryIdentifier);
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * @param string $className
	 * @param string $cacheEntryIdentifier
	 * @return bool
	 */
	protected function createCacheEntryForClassFromEarlyAutoloadRegistry($className, $cacheEntryIdentifier) {
		if (isset($this->earlyClassFileAutoloadRegistry[$lowercasedClassName = strtolower($className)])) {
			if (@file_exists($this->earlyClassFileAutoloadRegistry[$lowercasedClassName])) {
				$this->addClassToCache($this->earlyClassFileAutoloadRegistry[$lowercasedClassName], $cacheEntryIdentifier);
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * @param string $className
	 * @param string $cacheEntryIdentifier
	 * @return bool
	 */
	protected function createCacheEntryForClassFromRegisteredPackages($className, $cacheEntryIdentifier) {;
		foreach ($this->packageNamespaces as $packageNamespace => $packageData) {
			if (substr($className, 0, $packageData['namespaceLength']) === $packageNamespace) {
				if ($packageData['substituteNamespaceInPath']) {
					// If it's a TYPO3 package, classes don't comply to PSR-0.
					// The namespace part is substituted.
					$classPathAndFilename = '/' . str_replace('\\', '/', ltrim(substr($className, $packageData['namespaceLength']), '\\')) . '.php';
				} else {
					$classPathAndFilename = '/' . str_replace('\\', '/', $className) . '.php';
				}
				if (strtolower(substr($className, $packageData['namespaceLength'], 5)) === 'tests') {
					$classPathAndFilename = $packageData['packagePath'] . $classPathAndFilename;
				} else {
					$classPathAndFilename = $packageData['classesPath'] . $classPathAndFilename;
				}
				if (@file_exists($classPathAndFilename)) {
					$this->addClassToCache($classPathAndFilename, $cacheEntryIdentifier);
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Try to load a given class name based on 'extbase' naming convention into the registry.
	 * If the file is found it writes an entry to $classNameToFileMapping and re-caches the
	 * array to the file system to save this lookup for next call.
	 *
	 * @param string $className Class name to find source file of
	 * @param string $classCacheEntryIdentifier
	 * @return bool
	 */
	protected function createCacheEntryForClassByNamingConvention($className, $classCacheEntryIdentifier) {
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

		if (isset($classNameParts[0]) && $classNameParts[0] === 'TYPO3' && (isset($classNameParts[1]) && $classNameParts[1] === 'CMS')) {
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
				$this->addClassToCache($classFilePath, $classCacheEntryIdentifier);
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @return string
	 */
	protected function getCacheIdentifier() {
		return $this->cacheIdentifier;
	}

	/**
	 * @return string
	 */
	protected function getCacheEntryIdentifier() {
		$cacheIdentifier = $this->getCacheIdentifier();
		return $cacheIdentifier !== NULL ? 'ClassLoader_' . $this->getCacheIdentifier() : NULL;
	}

	/**
	 * @param string $cacheIdentifier
	 */
	public function setCacheIdentifier($cacheIdentifier) {
		$this->cacheIdentifier = $cacheIdentifier;
		$this->classAliasMap->setCacheIdentifier($cacheIdentifier);
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
			$this->buildPackageNamespaces();
			$this->buildPackageClassesPathsForLegacyExtensions();
			$this->savePackageNamespacesAndClassesPathsToCache();
			// Rebuild the class alias map too because ext_autoload can contain aliases
			$classNameToAliasMapping = $this->classAliasMap->setPackagesButDontBuildMappingFilesReturnClassNameToAliasMappingInstead($packages);
			$this->buildAutoloadRegistryAndSaveToCache();
			$this->classAliasMap->buildMappingFiles($classNameToAliasMapping);
		} else {
			$this->classAliasMap->setPackages($packages);
		}
		return $this;
	}

	/**
	 * @return bool
	 */
	protected function loadPackageNamespacesFromCache() {
		$cacheEntryIdentifier = $this->getCacheEntryIdentifier();
		if ($cacheEntryIdentifier !== NULL && $this->classesCache->has($cacheEntryIdentifier)) {
			list($packageNamespaces, $packageClassesPaths) = $this->classesCache->requireOnce($cacheEntryIdentifier);
			if (is_array($packageNamespaces) && is_array($packageClassesPaths)) {
				$this->packageNamespaces = $packageNamespaces;
				$this->packageClassesPaths = $packageClassesPaths;
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 *
	 */
	protected function buildPackageNamespaces() {
		/** @var $package \TYPO3\Flow\Package\Package */
		foreach ($this->packages as $package) {
			$packageNamespace = $package->getNamespace();
			// Ignore legacy extensions with unkown vendor name
			if ($packageNamespace[0] !== '*') {
				$this->packageNamespaces[$packageNamespace] = array(
					'namespaceLength' => strlen($packageNamespace),
					'classesPath' => $package->getClassesPath(),
					'packagePath' => $package->getPackagePath(),
					'substituteNamespaceInPath' => ($package instanceof \TYPO3\CMS\Core\Package\Package)
				);
			}
		}
		// sort longer package namespaces first, to find specific matches before generic ones
		$sortPackages = function($a, $b) {
			if (($lenA = strlen($a)) === ($lenB = strlen($b))) {
				return strcmp($a, $b);
			}
			return ($lenA > $lenB) ? -1 : 1;
		};
		uksort($this->packageNamespaces, $sortPackages);
	}

	/**
	 *
	 */
	protected function buildAutoloadRegistryAndSaveToCache() {
		$classFileAutoloadRegistry = array();
		foreach ($this->packages as $package) {
			/** @var $package \TYPO3\CMS\Core\Package\Package */
			if ($package instanceof \TYPO3\CMS\Core\Package\Package) {
				$classFilesFromAutoloadRegistry = $package->getClassFilesFromAutoloadRegistry();
				if (is_array($classFilesFromAutoloadRegistry)) {
					$classFileAutoloadRegistry = array_merge($classFileAutoloadRegistry, $classFilesFromAutoloadRegistry);
				}
			}
		}
		foreach ($classFileAutoloadRegistry as $className => $classFilePath) {
			if (@file_exists($classFilePath)) {
				$this->addClassToCache($classFilePath, strtolower(str_replace('\\', '_', $className)));
			}
		}
	}

	/**
	 * Builds the classes paths for legacy extensions with unkown vendor name
	 */
	protected function buildPackageClassesPathsForLegacyExtensions() {
		foreach ($this->packages as $package) {
			if ($package instanceof \TYPO3\CMS\Core\Package\PackageInterface) {
				$this->packageClassesPaths[$package->getPackageKey()] = $package->getClassesPath();
				foreach ($package->getPackageReplacementKeys() as $packageToReplace => $versionConstraint) {
					$this->packageClassesPaths[$packageToReplace] = $package->getClassesPath();
				}
			}
		}
	}

	/**
	 *
	 */
	protected function savePackageNamespacesAndClassesPathsToCache() {
		$cacheEntryIdentifier = $this->getCacheEntryIdentifier();
		if ($cacheEntryIdentifier !== NULL) {
			$this->classesCache->set(
				$this->getCacheEntryIdentifier(),
				'return ' . var_export(array($this->packageNamespaces, $this->packageClassesPaths), TRUE) . ';'
			);
		}
	}

	/**
	 * Adds a single class to class loader cache.
	 *
	 * @param string $classFilePathAndName Physical path of file containing $className
	 * @param string $classCacheEntryIdentifier
	 */
	protected function addClassToCache($classFilePathAndName, $classCacheEntryIdentifier) {
		/** @var $classesCacheBackend \TYPO3\CMS\Core\Cache\Backend\EarlyClassLoaderBackend|\TYPO3\CMS\Core\Cache\Backend\ClassLoaderBackend */
		$classesCacheBackend = $this->classesCache->getBackend();
		$classesCacheBackend->setLinkToPhpFile(
			$classCacheEntryIdentifier,
			$classFilePathAndName
		);
	}

	/**
	 * This method is necessary for the early loading of the cores autoload registry
	 *
	 * @param array $classFileAutoloadRegistry
	 */
	public function setEarlyClassFileAutoloadRegistry($classFileAutoloadRegistry) {
		$this->earlyClassFileAutoloadRegistry = $classFileAutoloadRegistry;
	}

	/**
	 * @param string $aliasClassName
	 * @param string $originalClassName
	 * @return bool
	 */
	public function setAliasForClassName($aliasClassName, $originalClassName) {
		return $this->classAliasMap->setAliasForClassName($aliasClassName, $originalClassName);
	}

	/**
	 * @param string $alias
	 * @return mixed
	 */
	static public function getClassNameForAlias($alias) {
		return static::$staticAliasMap->getClassNameForAlias($alias);
	}

	/**
	 * @param string $className
	 * @deprecated since 6.2, use getAliasesForClassName instead. will be removed 2 versions later
	 * @return mixed
	 */
	static public function getAliasForClassName($className) {
		$aliases = static::$staticAliasMap->getAliasesForClassName($className);
		return (is_array($aliases) && isset($aliases[0])) ? $aliases[0] : NULL;
	}

	/**
	 * @param string $className
	 * @return mixed
	 */
	static public function getAliasesForClassName($className) {
		return static::$staticAliasMap->getAliasesForClassName($className);
	}

}

?>