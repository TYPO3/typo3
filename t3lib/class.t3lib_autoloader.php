<?php
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
 * This class contains TYPO3 autoloader for classes.
 * It handles:
 * - The core of TYPO3
 * - All extensions with an ext_autoload.php file
 * - All extensions that stick to the 'extbase' like naming convention
 * - Resolves registered XCLASSes
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 * @author Martin Kutschker <masi@typo3.org>
 * @author Oliver Hader <oliver@typo3.org>
 * @author Sebastian Kurfürst <sebastian@typo3.org>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class t3lib_autoloader {

	/**
	 * Class name to file mapping. Key: class name. Value: fully qualified file name.
	 *
	 * @var array
	 */
	protected static $classNameToFileMapping = array();

	/**
	 * Name of cache entry identifier in autoload cache
	 *
	 * @var string
	 */
	protected static $autoloadCacheIdentifier = NULL;

	/**
	 * Track if the cache file written to disk should be updated.
	 * This is set to TRUE if during script run new classes are
	 * found (for example due to new requested extbase classes)
	 * and is used in unregisterAutoloader() to decide whether or not
	 * the cache file should be re-written.
	 *
	 * @var bool True if mapping changed
	 */
	protected static $cacheUpdateRequired = FALSE;

	/**
	 * The autoloader is static, thus we do not allow instances of this class.
	 */
	private function __construct() {
	}

	/**
	 * Installs TYPO3 autoloader, and loads the autoload registry for the core.
	 *
	 * @return boolean TRUE in case of success
	 */
	public static function registerAutoloader() {
		self::loadCoreAndExtensionRegistry();
		return spl_autoload_register('t3lib_autoloader::autoload', TRUE, TRUE);
	}

	/**
	 * Unload TYPO3 autoloader and write any additional classes
	 * found during the script run to the cache file.
	 *
	 * This method is called during shutdown of the framework.
	 *
	 * @return boolean TRUE in case of success
	 */
	public static function unregisterAutoloader() {
		if (self::$cacheUpdateRequired) {
			self::updateRegistryCacheEntry(self::$classNameToFileMapping);
			self::$cacheUpdateRequired = FALSE;
		}
		self::$classNameToFileMapping = array();

		return spl_autoload_unregister('t3lib_autoloader::autoload');
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
	public static function autoload($className) {
			// Use core and extension registry
		$classPath = self::getClassPathByRegistryLookup($className);

		if ($classPath) {
				// Include the required file that holds the class
			t3lib_div::requireFile($classPath);
		} else {
			try {
					// Regular expression for a valid classname taken from
					// http://www.php.net/manual/en/language.oop5.basic.php
				if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $className)) {
					spl_autoload($className);
				}
			} catch (LogicException $exception) {
			}
		}
	}

	/**
	 * Load registry from cache file if available or search
	 * for all loaded extensions and create a cache file
	 *
	 * @return void
	 */
	protected static function loadCoreAndExtensionRegistry() {
		/** @var $phpCodeCache t3lib_cache_frontend_PhpFrontend */
		$phpCodeCache = $GLOBALS['typo3CacheManager']->getCache('cache_phpcode');

			// Create autoload cache file if it does not exist yet
		if ($phpCodeCache->has(self::getAutoloadCacheIdentifier())) {
			$classRegistry = $phpCodeCache->requireOnce(self::getAutoloadCacheIdentifier());
		} else {
			self::$cacheUpdateRequired = TRUE;
			$classRegistry = self::lowerCaseClassRegistry(self::createCoreAndExtensionRegistry());
		}

			// This can only happen if the autoloader was already registered
			// in the same call once, the requireOnce of the cache file then
			// does not give the cached array back. In this case we just read
			// all cache entries manually again.
			// This can happen in unit tests and if the cache backend was
			// switched to NullBackend for example to simplify development
		if (!is_array($classRegistry)) {
			self::$cacheUpdateRequired = TRUE;
			$classRegistry = self::lowerCaseClassRegistry(self::createCoreAndExtensionRegistry());
		}

		self::$classNameToFileMapping = $classRegistry;
	}

	/**
	 * Get the full path to a class by looking it up in the registry.
	 * If not found, returns NULL.
	 *
	 * Warning: This method is public as it is needed by t3lib_div::makeInstance(),
	 * but it is _not_ part of the public API and should not be used in own extensions!
	 *
	 * @param string $className Class name to find source file of
	 * @return mixed If String: Full name of the file where $className is declared, NULL if no entry is found
	 * @internal
	 */
	public static function getClassPathByRegistryLookup($className) {
		$classPath = NULL;

			// To handle namespaced class names, get rid of the first backslash
			// and replace the remaining ones with underscore. This will simulate
			// a 'usual' "extbase" structure like 'Tx_ExtensionName_Foo_bar'
		if (strpos($className, '\\') === 0 ) {
			$className = str_replace('\\', '_', ltrim($className, '\\'));
		}

		$classNameLower = t3lib_div::strtolower($className);

			// Try to resolve extbase naming scheme if class is not already in cache file
		if (!array_key_exists($classNameLower, self::$classNameToFileMapping)) {
			self::attemptToLoadRegistryWithNamingConventionForGivenClassName($className);
		}

			// Look up class name in cache file
		if (array_key_exists($classNameLower, self::$classNameToFileMapping)) {
			$classPath = self::$classNameToFileMapping[$classNameLower];
		}

		if (
			$classPath === NULL
			&& substr($classNameLower, 0, 3) === 'ux_'
			&& !array_key_exists($classNameLower, self::$classNameToFileMapping)
		) {
			self::$cacheUpdateRequired = TRUE;
			self::$classNameToFileMapping[$classNameLower] = NULL;
		}

		return $classPath;
	}

	/**
	 * Find all ext_autoload files and merge with core_autoload.
	 *
	 * @return array
	 */
	protected static function createCoreAndExtensionRegistry() {
		$classRegistry = require(PATH_t3lib . 'core_autoload.php');
			// At this point during bootstrap the local configuration is initialized,
			// extMgm is ready to get the list of enabled extensions
		foreach (t3lib_extMgm::getLoadedExtensionListArray() as $extensionKey) {
			$extensionAutoloadFile = t3lib_extMgm::extPath($extensionKey, 'ext_autoload.php');
			if (file_exists($extensionAutoloadFile)) {
				$classRegistry = array_merge($classRegistry, require($extensionAutoloadFile));
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
	protected static function attemptToLoadRegistryWithNamingConventionForGivenClassName($className) {
		$classNameParts = explode('_', $className, 3);
		$extensionKey = t3lib_div::camelCaseToLowerCaseUnderscored($classNameParts[1]);
		if ($extensionKey) {
			try {
					// This will throw a BadFunctionCallException if the extension is not loaded
				$extensionPath = t3lib_extMgm::extPath($extensionKey);
				$classFilePathAndName = $extensionPath . 'Classes/' . strtr($classNameParts[2], '_', '/') . '.php';
				self::addClassToCache($classFilePathAndName, $className);
			} catch (BadFunctionCallException $exception) {
					// Catch the exception and do nothing to give
					// other registered autoloaders a chance to find the file
			}
		}
	}

	/**
	 * Adds a single class to autoloader cache.
	 *
	 * @static
	 * @param string $classFilePathAndName Physical path of file containing $className
	 * @param string $className Class name
	 * @return void
	 */
	protected static function addClassToCache($classFilePathAndName, $className) {
		if (file_exists($classFilePathAndName)) {
			self::$cacheUpdateRequired = TRUE;
			self::$classNameToFileMapping[t3lib_div::strtolower($className)] = $classFilePathAndName;
		}
	}

	/**
	 * Set or update autoloader cache entry.
	 * It is expected that all class names (keys) are already lowercased!
	 *
	 * @param array $registry Current registry entries
	 * @return void
	 */
	protected static function updateRegistryCacheEntry(array $registry) {
		$cachedFileContent = 'return array(';
		foreach ($registry as $className => $classLocation) {
			$nullOrLocation = is_string($classLocation) ? '\'' . $classLocation . '\',' : 'NULL,';
			$cachedFileContent .= LF . '\'' . $className . '\' => ' . $nullOrLocation;
		}
		$cachedFileContent .= LF . ');';
		$GLOBALS['typo3CacheManager']->getCache('cache_phpcode')->set(
			self::getAutoloadCacheIdentifier(),
			$cachedFileContent,
			array('t3lib_autoloader', 'core')
		);
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
	protected static function getAutoloadCacheIdentifier() {
		if (is_null(self::$autoloadCacheIdentifier)) {
			self::$autoloadCacheIdentifier = 'autoload_' . sha1(TYPO3_version . PATH_site . 'autoload');
		}
		return self::$autoloadCacheIdentifier;
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
	protected static function lowerCaseClassRegistry($registry) {
		$lowerCasedClassRegistry = array();
		foreach ($registry as $className => $classFile) {
			$lowerCasedClassRegistry[t3lib_div::strtolower($className)] = $classFile;
		}
		return $lowerCasedClassRegistry;
	}
}
?>