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
 * - the core of TYPO3
 * - all extensions with an ext_autoload.php file
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 * @author Martin Kutschker <masi@typo3.org>
 * @author Oliver Hader <oliver@typo3.org>
 * @author Sebastian Kurfürst <sebastian@typo3.org>
 */
class t3lib_autoloader {

	/**
	 * Class name to file mapping. Key: class name. Value: fully qualified file name.
	 *
	 * @var array
	 */
	protected static $classNameToFileMapping = array();

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
	public static function loadCoreAndExtensionRegistry() {
		$phpCodeCache = $GLOBALS['typo3CacheManager']->getCache('cache_phpcode');
		$autoloadCacheIdentifier = TYPO3_MODE === 'FE' ? 't3lib_autoload_FE' : 't3lib_autoload_BE';

			// Create autoloader cache file if it does not exist yet
		if (!$phpCodeCache->has($autoloadCacheIdentifier)) {
			$classRegistry = self::createCoreAndExtensionRegistry();
			$cachedFileContent = 'return array(';
			foreach ($classRegistry as $className => $classLocation) {
				$cachedFileContent .= chr(10) . '\'' . $className . '\' => \'' . $classLocation . '\',';
			}
			$cachedFileContent .= chr(10) . ');';
			$phpCodeCache->set($autoloadCacheIdentifier, $cachedFileContent, array('t3lib_autoloader'));
		}

			// Require calculated cache file
		$mappingArray = $phpCodeCache->requireOnce($autoloadCacheIdentifier);

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
	 * @param string $className Class name
	 * @return string Full name of the file where $className is declared, or NULL if no entry found in registry.
	 */
	protected static function getClassPathByRegistryLookup($className) {
		$className = strtolower($className);
		if (array_key_exists($className, self::$classNameToFileMapping)) {
			return self::$classNameToFileMapping[$className];
		} else {
			return NULL;
		}
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
}
?>