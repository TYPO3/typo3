<?php
namespace TYPO3\CMS\Core\Core;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Dmitry Dulepov <dmitry@typo3.org>
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
	 * Name of cache entry identifier in autoload cache
	 *
	 * @var string
	 */
	static protected $classLoaderCacheIdentifier = NULL;

	/**
	 * @var boolean TRUE, if old to new and new to old mapping was populated to PHP
	 */
	static protected $mappingLoaded = FALSE;


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
		if (static::$cacheUpdateRequired) {
			static::updateClassLoaderCacheEntry(static::$aliasToClassNameMapping);
			static::$cacheUpdateRequired = FALSE;
		}
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
		static::$mappingLoaded = false;
		static::$classNameToFileMapping = array();
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

		// Use core and extension registry
		$classPath = static::getClassPathByRegistryLookup($className);

		if ($classPath && !class_exists($className, FALSE)) {
			static::requireClassFileOnce($classPath, $className);
		}
	}

	/**
	 * Require the class file
	 *
	 * @param string $classPath
	 * @param string $className
	 */
	static protected function requireClassFileOnce($classPath, $className) {
		GeneralUtility::requireOnce($classPath);
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
	 * Load registry from cache file if available or search
	 * for all loaded extensions and create a cache file
	 *
	 * @return void
	 */
	static public function loadClassLoaderCache() {
		if (static::$mappingLoaded) {
			return;
		}

		/** @var $phpCodeCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
		$phpCodeCache = $GLOBALS['typo3CacheManager']->getCache('cache_core');
		// Create autoload cache file if it does not exist yet
		if ($phpCodeCache->has(static::getClassLoaderCacheIdentifier())) {
			static::$classNameToFileMapping = $phpCodeCache->requireOnce(static::getClassLoaderCacheIdentifier());
		}

		static::$mappingLoaded = TRUE;
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
			static::$classLoaderCacheIdentifier = 'ClassLoader_' . sha1((TYPO3_version . PATH_site . 'ClassLoader') . '2');
		}
		return static::$classLoaderCacheIdentifier;
	}

	/**
	 * @param string $alias
	 * @return mixed
	 */
	static public function getClassNameForAlias($alias) {
		return $alias;
	}
}


?>