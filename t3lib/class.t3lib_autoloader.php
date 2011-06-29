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
	protected static $autoloadCacheIdentifier = '';

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
		self::$autoloadCacheIdentifier = TYPO3_MODE === 'FE' ? 't3lib_autoload_FE' : 't3lib_autoload_BE';
		self::loadCoreAndExtensionRegistry();
		return spl_autoload_register('t3lib_autoloader::autoload', TRUE, TRUE);
	}

	/**
	 * Uninstalls TYPO3 autoloader. This function is for the sake of completeness.
	 * It is never called by the TYPO3 core.
	 *
	 * @return boolean TRUE in case of success
	 */
	public static function unregisterAutoloader() {
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
		$phpCodeCache = $GLOBALS['typo3CacheManager']->getCache('cache_phpcode');

			// Create autoloader cache file if it does not exist yet
		if (!$phpCodeCache->has(self::$autoloadCacheIdentifier)) {
			$classRegistry = self::createCoreAndExtensionRegistry();
			self::updateRegistryCacheEntry($classRegistry);
		}

			// Require calculated cache file
		$mappingArray = $phpCodeCache->requireOnce(self::$autoloadCacheIdentifier);

			// This can only happen if the autoloader was already registered
			// in the same call once, the requireOnce of the cache file then
			// does not give the cached array back. In this case we just read
			// all cache entries manually again.
			// This should only happen in unit tests
		if (!is_array($mappingArray)) {
			$mappingArray = self::createCoreAndExtensionRegistry();
		}

		self::$classNameToFileMapping = $mappingArray;
	}

	/**
	 * Get the full path to a class by looking it up in the registry.
	 * If not found, returns NULL.
	 *
	 * @param string $className Class name to find source file of
	 * @return mixed If String: Full name of the file where $className is declared, NULL if no entry is found
	 */
	protected static function getClassPathByRegistryLookup($className) {
		$classPath = NULL;
		$classNameLower = strtolower($className);
		if (!array_key_exists($classNameLower, self::$classNameToFileMapping)) {
			self::attemptToLoadRegistryWithNamingConventionForGivenClassName($className);
		}
		if (array_key_exists($classNameLower, self::$classNameToFileMapping)) {
			$classPath = self::$classNameToFileMapping[$classNameLower];
		}
		return $classPath;
	}

	/**
	 * Find all ext_autoload files and merge with core_autoload.
	 *
	 * @return void
	 */
	protected static function createCoreAndExtensionRegistry() {
		$classRegistry = require(PATH_t3lib . 'core_autoload.php');
			// At this point localconf.php was already initialized
			// we have a current extList and extMgm is also known
		$loadedExtensions = array_unique(t3lib_div::trimExplode(',', t3lib_extMgm::getEnabledExtensionList(), TRUE));
		foreach ($loadedExtensions as $extensionKey) {
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
				if (file_exists($classFilePathAndName)) {
					self::$classNameToFileMapping[strtolower($className)] = $classFilePathAndName;
					self::updateRegistryCacheEntry(self::$classNameToFileMapping);
				}
			} catch (BadFunctionCallException $exception) {
					// Catch the exception and do nothing to give
					// other registered autoloaders a chance to find the file
			}
		}
	}

	/**
	 * Set or update autoloader cache entry
	 *
	 * @param array $registry Current registry entries
	 * @return void
	 */
	protected static function updateRegistryCacheEntry(array $registry) {
		$cachedFileContent = 'return array(';
		foreach ($registry as $className => $classLocation) {
			$cachedFileContent .= LF . '\'' . $className . '\' => \'' . $classLocation . '\',';
		}
		$cachedFileContent .= LF . ');';
		$GLOBALS['typo3CacheManager']->getCache('cache_phpcode')->set(
			self::$autoloadCacheIdentifier,
			$cachedFileContent,
			array('t3lib_autoloader')
		);
	}
}
?>