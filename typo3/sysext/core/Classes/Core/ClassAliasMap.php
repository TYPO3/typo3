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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ClassAliasMap
 * @package TYPO3\CMS\Core\Core
 * @internal
 */
class ClassAliasMap implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	static protected $aliasToClassNameMapping = array();

	/**
	 * @var array
	 */
	static protected $classNameToAliasMapping = array();

	/**
	 *
	 */
	static public function loadClassAliasMapCache() {
		/** @var $coreCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
		$coreCache = $GLOBALS['typo3CacheManager']->getCache('cache_core');
		if ($coreCache->has('ClassAliasMapIsLoaded')) {
			return;
		}
		/** @var $classesCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
		$classesCache = $GLOBALS['typo3CacheManager']->getCache('cache_classes');
		$aliasToClassNameMapping = self::loadAliasToClassNameMappingFromExtensions();
		foreach ($aliasToClassNameMapping as $aliasClassName => $originalClassName) {
			$classesCache->set(
				str_replace('\\', '_', strtolower($aliasClassName)),
				'\\' . __CLASS__ . '::setAliasForClassName(\'' .  $aliasClassName . '\', \'' . $originalClassName . '\');'
			);
		}
		$coreCache->set('ClassAliasMapIsLoaded', 'return TRUE;');
	}

	/**
	 * @return array
	 */
	static public function loadAliasToClassNameMappingFromExtensions() {
		$aliasToClassNameMapping = array();
		foreach (ExtensionManagementUtility::getLoadedExtensionListArray() as $extensionKey) {
			try {
				$extensionClassAliasMap = ExtensionManagementUtility::extPath($extensionKey, 'Migrations/Code/ClassAliasMap.php');
				if (@file_exists($extensionClassAliasMap)) {
					$aliasToClassNameMapping = array_merge($aliasToClassNameMapping, require $extensionClassAliasMap);
				}
			}
			catch (\BadFunctionCallException $e) {
			}
		}
		return $aliasToClassNameMapping;
	}

	/**
	 * @param string $aliasClassName
	 * @param string $originalClassName
	 * @return bool true on success or false on failure
	 * @internal
	 */
	static public function setAliasForClassName($aliasClassName, $originalClassName) {
		static::$aliasToClassNameMapping[$lowercasedAliasClassName = strtolower($aliasClassName)] = $originalClassName;
		static::$classNameToAliasMapping[strtolower($originalClassName)][$lowercasedAliasClassName] = $aliasClassName;
		return class_alias($originalClassName, $aliasClassName);
	}

	/**
	 * @param string $alias
	 * @return mixed
	 */
	static public function getClassNameForAlias($alias) {
		$lookUpClassName = strtolower($alias);
		return isset(static::$aliasToClassNameMapping[$lookUpClassName]) ? static::$aliasToClassNameMapping[$lookUpClassName] : $alias;
	}


	/**
	 * @param string $className
	 * @return array
	 */
	static public function getAliasesForClassName($className) {
		$lookUpClassName = strtolower($className);
		return isset(static::$classNameToAliasMapping[$lookUpClassName]) ? static::$classNameToAliasMapping[$lookUpClassName] : array($className);
	}

}