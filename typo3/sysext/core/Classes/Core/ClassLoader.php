<?php
namespace TYPO3\CMS\Core\Core;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class ClassLoader {

	/**
	 * Contains the class loaders class name
	 *
	 * @var string
	 */
	static protected $className = __CLASS__;

	/**
	 * Classes cache.
	 *
	 * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
	 */
	static protected $classesCache;

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
		return spl_autoload_unregister(static::$className . '::autoload');
	}

	/**
	 * Autoload function for TYPO3.
	 *
	 * This method looks up class names in the registry
	 * (which contains extensions and core files)
	 *
	 * @param string $className Class name
	 * @return bool
	 */
	static public function autoload($className) {
		$className = ltrim($className, '\\');
		$classLoaded = FALSE;
		$classCacheEntryIdentifier = str_replace('\\', '_', strtolower($className));
		if (static::$classesCache->has($classCacheEntryIdentifier)) {
			$classLoaded = (bool) static::$classesCache->requireOnce($classCacheEntryIdentifier);
			if (!$classLoaded) {
				static::$classesCache->remove($classCacheEntryIdentifier);
			}
		}
		if (!$classLoaded) {
			$classFilePathAndName = static::getClassPathByNamingConventionForGivenClassName($className);
			if ($classFilePathAndName && file_exists($classFilePathAndName)) {
				if (!static::$classesCache->has($classCacheEntryIdentifier)) {
					static::addClassToCache($classFilePathAndName, $classCacheEntryIdentifier);
				}
				if (static::$classesCache->has($classCacheEntryIdentifier)) {
					$classLoaded = (bool) static::$classesCache->requireOnce($classCacheEntryIdentifier);
				}
			}
		}
		return $classLoaded;
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
		static::$classesCache = $GLOBALS['typo3CacheManager']->getCache('cache_classes');
		/** @var $coreCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
		$coreCache = $GLOBALS['typo3CacheManager']->getCache('cache_core');
		if ($coreCache->has('ClassLoaderCacheIsLoaded')) {
			return;
		}
		foreach (static::createCoreAndExtensionRegistry() as $className => $classFilePath) {
			$classCacheEntryIdentifier = str_replace('\\', '_', strtolower($className));
			if (!static::$classesCache->has($classCacheEntryIdentifier) && file_exists($classFilePath)) {
				static::addClassToCache($classFilePath, $classCacheEntryIdentifier);
			}
		}
		$coreCache->set('ClassLoaderCacheIsLoaded', 'return TRUE;');
	}

	/**
	 * @param string $alias
	 * @return mixed
	 */
	static public function getClassNameForAlias($alias) {
		return ClassAliasMap::getClassNameForAlias($alias);
	}


	/**
	 * @param string $className
	 * @return string
	 * @deprecated since 6.1, will be removed two versions later
	 */
	static public function getAliasForClassName($className) {
		$aliases = ClassAliasMap::getAliasesForClassName($className);
		return isset($aliases[0]) ? $aliases[0] : $className;
	}

	/**
	 * @param string $className
	 * @return array
	 */
	static public function getAliasesForClassName($className) {
		return ClassAliasMap::getAliasesForClassName($className);
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
	 * @return string
	 */
	static protected function getClassPathByNamingConventionForGivenClassName($className) {
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
				return $classFilePathAndName;
			} catch (\BadFunctionCallException $exception) {

			}
		}
	}

	/**
	 * Adds a single class to class loader cache.
	 *
	 * @static
	 * @param string $classFilePathAndName Physical path of file containing $className
	 * @param string $classCacheEntryIdentifier
	 * @return bool
	 */
	static protected function addClassToCache($classFilePathAndName, $classCacheEntryIdentifier) {
		/** @var $classesCacheBackend \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend */
		$classesCacheBackend = static::$classesCache->getBackend();
		$classesCacheBackend->setLinkToPhpFile(
			$classCacheEntryIdentifier,
			$classFilePathAndName
		);
	}

}


?>