<?php
namespace TYPO3\CMS\Frontend\ContentObject\Menu;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Factory for menu content objects. Allows overriding the default
 * types like 'GMENU' with an own implementation (only one possible)
 * and new types can be registered.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class MenuContentObjectFactory implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Register of TypoScript keys to according render class
	 *
	 * @var array
	 */
	protected $menuTypeToClassMapping = array(
		'GMENU' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\GraphicalMenuContentObject',
		'TMENU' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\TextMenuContentObject',
		'IMGMENU' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\ImageMenuContentObject',
		'JSMENU' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\JavaScriptMenuContentObject',
	);

	/**
	 * Gets a typo script string like 'TMENU' and returns an object of this type
	 *
	 * @param string $type
	 * @return object Menu object
	 * @throws Exception\NoSuchMenuTypeException
	 */
	public function getMenuObjectByType($type = '') {
		$uppercasedClassname = strtoupper($type);
		if (array_key_exists($uppercasedClassname, $this->menuTypeToClassMapping)) {
			$object = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->menuTypeToClassMapping[$uppercasedClassname]);
		} else {
			throw new Exception\NoSuchMenuTypeException(
				'Menu type ' . (string)$type . ' has no implementing class.',
				1363278130
			);
		}
		return $object;
	}

	/**
	 * Register new menu type or override existing type
	 *
	 * @param string $type Menu type to be used in TypoScript
	 * @param string $className Class rendering the menu
	 * @throws \InvalidArgumentException
	 */
	public function registerMenuType($type, $className) {
		if (!is_string($type) || !is_string($className)) {
			throw new \InvalidArgumentException(
				'type and className must be strings',
				1363429303
			);
		}
		$this->menuTypeToClassMapping[strtoupper($type)] = $className;
	}
}

?>