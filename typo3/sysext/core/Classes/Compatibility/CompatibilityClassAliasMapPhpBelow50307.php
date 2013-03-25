<?php
namespace TYPO3\CMS\Core\Compatibility;

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
class CompatibilityClassAliasMapPhpBelow50307 extends \TYPO3\CMS\Core\Core\ClassAliasMap {

	/**
	 * @return array
	 */
	static public function loadAliasToClassNameMappingFromExtensions() {
		$aliasToClassNameMapping = parent::loadAliasToClassNameMappingFromExtensions();
		foreach ($aliasToClassNameMapping as $aliasClassName => $originalClassName) {
			static::$aliasToClassNameMapping[$lowercasedAliasClassName = strtolower($aliasClassName)] = $originalClassName;
			static::$classNameToAliasMapping[strtolower($originalClassName)][$lowercasedAliasClassName] = $aliasClassName;
		}
	}

	static public function getAliasesForClassNames() {
		return static::$aliasToClassNameMapping;
	}

}