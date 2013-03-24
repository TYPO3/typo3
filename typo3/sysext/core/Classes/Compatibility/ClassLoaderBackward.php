<?php
namespace TYPO3\CMS\Core\Compatibility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Thomas Maroschik <tmaroschik@dfau.de>
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
 * This class contains TYPO3 class loader for older classes.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 * @author Martin Kutschker <masi@typo3.org>
 * @author Oliver Hader <oliver@typo3.org>
 * @author Sebastian Kurfürst <sebastian@typo3.org>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class ClassLoaderBackward {

	/**
	 * Contains the class loaders class name
	 *
	 * @var string
	 */
	static protected $className = __CLASS__;

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
	 * @var boolean TRUE, if old to new and new to old mapping was populated to PHP
	 */
	static protected $mappingLoaded = FALSE;

	/**
	 * Installs TYPO3 autoloader, and loads the autoload registry for the core.
	 *
	 * @return boolean TRUE in case of success
	 */
	static public function registerAutoloader() {
		return spl_autoload_register(static::$className . '::autoload', TRUE, FALSE);
	}

	/**
	 * Unload TYPO3 autoloader and write any additional classes
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
		return spl_autoload_unregister(static::$className . '::autoload');
	}

	/**
	 * Cleanup the static vars.
	 * As this is slow and not needed on shutdown, this function is only for
	 * TYPO3 tests.
	 *
	 * @return void
	 */
	static public function cleanStatics() {
		static::$mappingLoaded = FALSE;
		static::$aliasToClassNameMapping = array();
		static::$classNameToAliasMapping = array();
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
		static::loadClassLoaderCache();

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
	 * @param string $alias
	 * @return mixed
	 */
	static public function getClassNameForAlias($alias) {
		$lookUpClassName = GeneralUtility::strtolower($alias);
		return isset(static::$aliasToClassNameMapping[$lookUpClassName]) ? static::$aliasToClassNameMapping[$lookUpClassName] : null;
	}


	/**
	 * @param string $className
	 * @return mixed
	 */
	static public function getAliasForClassName($className) {
		return isset(static::$classNameToAliasMapping[$className]) ? static::$classNameToAliasMapping[$className] : $className;
	}


	/**
	 * Load registry from cache file if available or search
	 * for all loaded extensions and create a cache file
	 *
	 * @return void
	 */
	static public function loadClassLoaderCache() {
		if (self::$mappingLoaded) {
			return;
		}
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
		static::$mappingLoaded = TRUE;
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
	 * Lowercase all keys of the class registry.
	 *
	 * Use the multi byte safe version of strtolower from t3lib_div,
	 * so array_change_key_case() can not be used
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
			static::$classLoaderCacheIdentifier = 'ClassLoaderBackward_' . sha1((TYPO3_version . PATH_site . 'ClassLoader') . '2');
		}
		return static::$classLoaderCacheIdentifier;
	}
}

?>