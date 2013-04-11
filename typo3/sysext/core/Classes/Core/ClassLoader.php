<?php
namespace TYPO3\CMS\Core\Core;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Dmitry Dulepov <dmitry@typo3.org>
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
 * This class contains TYPO3 class loader for classes.
 * It handles:
 * - The core of TYPO3
 * - All extensions with an ext_autoload.php file
 * - All extensions that stick to the 'extbase' like naming convention
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 * @author Martin Kutschker <masi@typo3.org>
 * @author Oliver Hader <oliver@typo3.org>
 * @author Sebastian Kurfürst <sebastian@typo3.org>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class ClassLoader {

	/**
	 * Contains the class loaders class name
	 *
	 * @var string
	 */
	static protected $className = __CLASS__;

	/**
	 * Class name to file mapping. Key: class name. Value: fully qualified file name.
	 *
	 * @var array
	 */
	static protected $classNameToFileMapping = array();

	/**
	 * @var boolean TRUE, if old to new and new to old mapping was populated to PHP
	 */
	static protected $mappingLoaded = FALSE;

	/**
	 * Old class name to new class name mapping
	 *
	 * @var array
	 */
	static protected $aliasToClassNameMapping = array();

	/**
	 * New class name to old class name mapping
	 *
	 * @var array
	 */
	static protected $classNameToAliasMapping = array();

	/**
	 * Name of cache entry identifier in autoload cache
	 *
	 * @var string
	 */
	static protected $classLoaderCacheIdentifier = NULL;

	/**
	 * Track if the cache file written to disk should be updated.
	 * This is set to TRUE if during script run new classes are
	 * found (for example due to new requested extbase classes)
	 * and is used in unregisterAutoloader() to decide whether or not
	 * the cache file should be re-written.
	 *
	 * @var bool True if mapping changed
	 */
	static protected $cacheUpdateRequired = FALSE;

	/**
	 * The class loader is static, thus we do not allow instances of this class.
	 */
	private function __construct() {

	}

	/**
	 * Installs TYPO3 class loader, and loads the autoload registry for the core.
	 *
	 * @return boolean TRUE in case of success
	 */
	static public function registerAutoloader() {
		static::loadClassLoaderCache();
		return spl_autoload_register(static::$className . '::autoload', TRUE, TRUE);
	}

	/**
	 * Unload TYPO3 class loader and write any additional classes
	 * found during the script run to the cache file.
	 *
	 * This method is called during shutdown of the framework.
	 *
	 * @return boolean TRUE in case of success
	 */
	static public function unregisterAutoloader() {
		if (static::$cacheUpdateRequired) {
			static::updateClassLoaderCacheEntry(array(static::$classNameToFileMapping, static::$aliasToClassNameMapping));
			static::$cacheUpdateRequired = FALSE;
		}
		static::$classNameToFileMapping = array();
		static::$aliasToClassNameMapping = array();
		static::$classNameToAliasMapping = array();
		return spl_autoload_unregister(static::$className . '::autoload');
	}

	/**
	 * Autoload function for TYPO3.
	 *
	 * This method looks up class names in the registry
	 * (which contains extensions and core files)
	 *
	 * @param string $className Class name
	 * @return void
	 */
	static public function autoload($className) {
		$className = ltrim($className, '\\');
		$realClassName = static::getClassNameForAlias($className);
		$aliasClassName = static::getAliasForClassName($className);
		$hasAliasClassName = ($aliasClassName !== $className);
		$lookUpClassName = ($hasRealClassName = $className !== $realClassName) ? $realClassName : $className;
		// Use core and extension registry
		$classPath = static::getClassPathByRegistryLookup($lookUpClassName);
		if ($classPath && !class_exists($realClassName, FALSE)) {
			// Include the required file that holds the class
			// Handing over the class name here is only done for the
			// compatibility class loader so that it can skip class names
			// which do not require rewriting. We can remove this bad
			// code smell once we can get rid of the compatibility class loader.
			static::requireClassFileOnce($classPath, $className);
			try {
				// Regular expression for a valid classname taken from
				// http://www.php.net/manual/en/language.oop5.basic.php
				if (preg_match('/^[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*$/', $className)) {
					spl_autoload($className);
				}
			} catch (\LogicException $exception) {

			}
		}
		if ($hasRealClassName && !class_exists($className, FALSE)) {
			class_alias($realClassName, $className);
		}
		if ($hasAliasClassName && !class_exists($aliasClassName, FALSE)) {
			class_alias($className, $aliasClassName);
		}
	}

	/**
	 * Require the class file
	 *
	 * @static
	 * @param string $classPath
	 * @param string $className
	 */
	static protected function requireClassFileOnce($classPath, $className) {
		GeneralUtility::requireOnce($classPath);
	}

	/**
	 * Load registry from cache file if available or search
	 * for all loaded extensions and create a cache file
	 *
	 * @return void
	 */
	static public function loadClassLoaderCache() {
		$classRegistry = NULL;
		$aliasToClassNameMapping = NULL;
		/** @var $phpCodeCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
		$phpCodeCache = $GLOBALS['typo3CacheManager']->getCache('cache_core');
		// Create autoload cache file if it does not exist yet
		if ($phpCodeCache->has(static::getClassLoaderCacheIdentifier())) {
			list($classRegistry, $aliasToClassNameMapping) = $phpCodeCache->requireOnce(static::getClassLoaderCacheIdentifier());
		}
		// This can only happen if the class loader was already registered
		// in the same call once, the requireOnce of the cache file then
		// does not give the cached array back. In this case we just read
		// all cache entries manually again.
		// This can happen in unit tests and if the cache backend was
		// switched to NullBackend for example to simplify development
		if (!is_array($aliasToClassNameMapping)) {
			static::$cacheUpdateRequired = TRUE;
			$aliasToClassNameMapping = static::createCoreAndExtensionClassAliasMap();
		}
		static::$aliasToClassNameMapping = $aliasToClassNameMapping;
		static::$classNameToAliasMapping = array_flip($aliasToClassNameMapping);
		self::setAliasesForEarlyInstances();

		if (!is_array($classRegistry)) {
			static::$cacheUpdateRequired = TRUE;
			$classRegistry = static::lowerCaseClassRegistry(static::createCoreAndExtensionRegistry());
		}
		static::$classNameToFileMapping = $classRegistry;
	}

	/**
	 * Collects and merges the class alias maps of extensions
	 *
	 * @return array The class alias map
	 */
	static protected function createCoreAndExtensionClassAliasMap() {
		$aliasToClassNameMapping = array();
		foreach (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray() as $extensionKey) {
			try {
				$extensionClassAliasMap = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey, 'Migrations/Code/ClassAliasMap.php');
				if (@file_exists($extensionClassAliasMap)) {
					$aliasToClassNameMapping = array_merge($aliasToClassNameMapping, require $extensionClassAliasMap);
				}
			} catch (\BadFunctionCallException $e) {
			}
		}
		foreach ($aliasToClassNameMapping as $oldClassName => $newClassName) {
			$aliasToClassNameMapping[GeneralUtility::strtolower($oldClassName)] = $newClassName;
		}
		// Order by key length longest first
		uksort($aliasToClassNameMapping, function($a, $b) {
			return strlen($b) - strlen($a);
		});
		return $aliasToClassNameMapping;
	}

	/**
	 * Create aliases for early loaded classes
	 */
	protected static function setAliasesForEarlyInstances() {
		$classedLoadedPriorToClassLoader = array_intersect(static::$aliasToClassNameMapping, get_declared_classes());
		if (!empty($classedLoadedPriorToClassLoader)) {
			foreach ($classedLoadedPriorToClassLoader as $oldClassName => $newClassName) {
				if (!class_exists($oldClassName, FALSE)) {
					class_alias($newClassName, $oldClassName);
				}
			}
		}
	}

	/**
	 * @param string $alias
	 * @return mixed
	 */
	static public function getClassNameForAlias($alias) {
		$lookUpClassName = GeneralUtility::strtolower($alias);
		return isset(static::$aliasToClassNameMapping[$lookUpClassName]) ? static::$aliasToClassNameMapping[$lookUpClassName] : $alias;
	}


	/**
	 * @param string $className
	 * @return mixed
	 */
	static public function getAliasForClassName($className) {
		return isset(static::$classNameToAliasMapping[$className]) ? static::$classNameToAliasMapping[$className] : $className;
	}

	/**
	 * Get the full path to a class by looking it up in the registry.
	 * If not found, returns NULL.
	 *
	 * Warning: This method is public as it is needed by \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(),
	 * but it is _not_ part of the public API and should not be used in own extensions!
	 *
	 * @param string $className Class name to find source file of
	 * @return mixed If String: Full name of the file where $className is declared, NULL if no entry is found
	 * @internal
	 */
	static public function getClassPathByRegistryLookup($className) {
		$classPath = NULL;
		$classNameLower = GeneralUtility::strtolower($className);
		// Try to resolve extbase naming scheme if class is not already in cache file
		if (!array_key_exists($classNameLower, static::$classNameToFileMapping)) {
			static::attemptToLoadRegistryWithNamingConventionForGivenClassName($className);
		}
		// Look up class name in cache file
		if (array_key_exists($classNameLower, static::$classNameToFileMapping)) {
			$classPath = static::$classNameToFileMapping[$classNameLower];
		}

		return $classPath;
	}

	/**
	 * Find all ext_autoload files and merge with core_autoload.
	 *
	 * @return array
	 */
	static protected function createCoreAndExtensionRegistry() {
		$classRegistry = array();
		// At this point during bootstrap the local configuration is initialized,
		// ExtensionManagementUtility is ready to get the list of enabled extensions
		foreach (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray() as $extensionKey) {
			try {
				$extensionAutoloadFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey, 'ext_autoload.php');
				if (@file_exists($extensionAutoloadFile)) {
					$classRegistry = array_merge($classRegistry, require $extensionAutoloadFile);
				}
			} catch (\BadFunctionCallException $e) {

			}
		}
		return $classRegistry;
	}

	/**
	 * Try to load a given class name based on 'extbase' naming convention into the registry.
	 * If the file is found it writes an entry to $classNameToFileMapping and re-caches the
	 * array to the file system to save this lookup for next call.
	 *
	 * @param string $className Class name to find source file of
	 * @return void
	 */
	static protected function attemptToLoadRegistryWithNamingConventionForGivenClassName($className) {
		$delimiter = '_';
		$tempClassName = $className;
		// To handle namespaced class names, get rid of the first backslash
		// and replace the remaining ones with underscore. This will simulate
		// a 'usual' "extbase" structure like 'Tx_ExtensionName_Foo_bar'
		if (strpos($className, '\\') !== FALSE) {
			$tempClassName = ltrim($className, '\\');
			$delimiter = '\\';
		}
		$classNameParts = explode($delimiter, $tempClassName, 4);
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

		if ($extensionKey) {
			try {
				// This will throw a BadFunctionCallException if the extension is not loaded
				$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey);
				$classPath = (substr(strtolower($classNameWithoutVendorAndProduct), 0, 5) === 'tests') ? '' : 'Classes/';
				$classFilePathAndName = $extensionPath . $classPath . strtr($classNameWithoutVendorAndProduct, $delimiter, '/') . '.php';
				static::addClassToCache($classFilePathAndName, $className);
			} catch (\BadFunctionCallException $exception) {

			}
		}
	}

	/**
	 * Adds a single class to class loader cache.
	 *
	 * @static
	 * @param string $classFilePathAndName Physical path of file containing $className
	 * @param string $className Class name
	 * @return void
	 */
	static protected function addClassToCache($classFilePathAndName, $className) {
		if (file_exists($classFilePathAndName)) {
			static::$cacheUpdateRequired = TRUE;
			static::$classNameToFileMapping[GeneralUtility::strtolower($className)] = $classFilePathAndName;
		}
	}

	/**
	 * Set or update class loader cache entry.
	 * It is expected that all class names (keys) are already lowercased!
	 *
	 * @param array $cacheContent Current class loader cache entries
	 * @return void
	 */
	static protected function updateClassLoaderCacheEntry(array $cacheContent) {
		$cachedFileContent = 'return ' . var_export($cacheContent, TRUE) . ';';
		$GLOBALS['typo3CacheManager']->getCache('cache_core')->set(static::getClassLoaderCacheIdentifier(), $cachedFileContent);
	}

	/**
	 * Gets the identifier used for caching the registry files.
	 * The identifier depends on the current TYPO3 version and the
	 * installation path of the TYPO3 site (PATH_site).
	 *
	 * In effect, a new registry cache file will be created
	 * when moving to a newer version with possible new core classes
	 * or moving the webroot to another absolute path.
	 *
	 * @return string identifier
	 */
	static protected function getClassLoaderCacheIdentifier() {
		if (is_null(static::$classLoaderCacheIdentifier)) {
			static::$classLoaderCacheIdentifier = 'ClassLoader_' . sha1((TYPO3_version . PATH_site . 'ClassLoader'));
		}
		return static::$classLoaderCacheIdentifier;
	}

	/**
	 * Lowercase all keys of the class registry.
	 *
	 * Use the multi byte safe version of strtolower from
	 * GeneralUtility, so array_change_key_case() can not be used
	 *
	 * @param array $registry Given registry entries
	 * @return array with lower cased keys
	 */
	static protected function lowerCaseClassRegistry($registry) {
		$lowerCasedClassRegistry = array();
		foreach ($registry as $className => $classFile) {
			$lowerCasedClassRegistry[GeneralUtility::strtolower($className)] = $classFile;
		}
		return $lowerCasedClassRegistry;
	}

}


?>