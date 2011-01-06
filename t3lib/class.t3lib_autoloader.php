<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2010 Dmitry Dulepov <dmitry@typo3.org>
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
 * Contains TYPO3 autoloader
 *
 * $Id$
 *
 * @author	Dmitry Dulepov	<dmitry@typo3.org>
 * @author	Martin Kutschker <masi@typo3.org>
 * @author	Oliver Hader <oliver@typo3.org>
 * @author	Sebastian Kurfürst <sebastian@typo3.org>
 */

/**
 * This class contains TYPO3 autoloader for classes.
 * It handles:
 * - the core of TYPO3
 * - all extensions with an ext_autoload.php file
 */
class t3lib_autoloader {

	/**
	 * Class name to file mapping. Key: class name. Value: fully qualified file name.
	 *
	 * @var array
	 */
	protected static $classNameToFileMapping = array();

	/**
	 * Associative array which sets for each extension which was attempted to load if it has an autoload configuration
	 *
	 * Key: extension key
	 * Value: TRUE, if extension has an ext_autoload.php and this is already part of $classNameToFileMapping
	 *		  FALSE, if extension has no ext_autoload.php
	 *
	 * @var array
	 */
	protected static $extensionHasAutoloadConfiguration = array();

	/**
	 * The autoloader is static, thus we do not allow instances of this class.
	 */
	private function __construct() {
	}

	/**
	 * Installs TYPO3 autoloader, and loads the autoload registry for the core.
	 *
	 * @return	boolean	true in case of success
	 */
	static public function registerAutoloader() {
		self::loadCoreRegistry();
		self::$extensionHasAutoloadConfiguration = array();
		return spl_autoload_register('t3lib_autoloader::autoload');
	}

	/**
	 * Uninstalls TYPO3 autoloader. This function is for the sake of completeness.
	 * It is never called by the TYPO3 core.
	 *
	 * @return	boolean	true in case of success
	 */
	static public function unregisterAutoloader() {
		return spl_autoload_unregister('t3lib_autoloader::autoload');
	}

	/**
	 * Autoload function for TYPO3.
	 *
	 * This method looks up class names in the registry
	 * (which contains extensions and core files)
	 *
	 * @param	string	$className	Class name
	 * @return	void
	 */
	static public function autoload($className) {
		$classPath = false;

			// use core and extension registry
		$classPath = self::getClassPathByRegistryLookup($className);

		if ($classPath && file_exists($classPath)) {
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
	 * Load the core registry into $classNameToFileMapping, effectively overriding
	 * the whole contents of $classNameToFileMapping.
	 *
	 * @return void
	 */
	static protected function loadCoreRegistry() {
		self::$classNameToFileMapping = require(PATH_t3lib . 'core_autoload.php');
	}

	/**
	 * Get the full path to a class by looking it up in the registry. If not found, returns NULL.
	 *
	 * @param	string	$className	Class name
	 * @return	string	full name of the file where $className is declared, or NULL if no entry found in registry.
	 */
	static protected function getClassPathByRegistryLookup($className) {
		$className = strtolower($className);
		if (!array_key_exists($className, self::$classNameToFileMapping)) {
			self::attemptToLoadRegistryForGivenClassName($className);
		}
		if (array_key_exists($className, self::$classNameToFileMapping)) {
			return self::$classNameToFileMapping[$className];
		} else {
			return NULL;
		}
	}

	/**
	 * Try to load the entries for a given class name into the registry.
	 *
	 * First, figures out the extension the class belongs to.
	 * Then, tries to load the ext_autoload.php file inside the extension directory, and adds its contents to the $classNameToFileMapping.
	 *
	 * @param	string	$className	Class Name
	 */
	static protected function attemptToLoadRegistryForGivenClassName($className) {
		$classNameParts = explode('_', $className);
		$extensionPrefix = array_shift($classNameParts) . '_' . array_shift($classNameParts);
		$extensionKey = t3lib_extMgm::getExtensionKeyByPrefix($extensionPrefix);

		if (!$extensionKey || array_key_exists($extensionKey, self::$extensionHasAutoloadConfiguration)) {
				// extension key could not be determined or we already tried to load the extension's autoload configuration
			return;
		}
		$possibleAutoloadConfigurationFileName = t3lib_extMgm::extPath($extensionKey) . 'ext_autoload.php';
		if (file_exists($possibleAutoloadConfigurationFileName)) {
			self::$extensionHasAutoloadConfiguration[$extensionKey] = TRUE;
			$extensionClassNameToFileMapping = require($possibleAutoloadConfigurationFileName);
			self::$classNameToFileMapping = array_merge($extensionClassNameToFileMapping, self::$classNameToFileMapping);
		} else {
			self::$extensionHasAutoloadConfiguration[$extensionKey] = FALSE;
		}
	}
}
?>
