<?php
namespace TYPO3\CMS\Backend\Domain\Repository\Module;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <typo3@susannemoog.de>
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
 * Repository for backend module menu
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class BackendModuleRepository implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Backend\Module\ModuleStorage $moduleMenu
	 */
	protected $moduleMenu;

	/**
	 * Constructs the module menu and gets the Singleton instance of the menu
	 */
	public function __construct() {
		$this->moduleMenu = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleStorage');
	}

	/**
	 * Finds a module menu entry by name
	 *
	 * @param string $name
	 * @return \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule|boolean
	 */
	public function findByModuleName($name) {
		$entries = $this->moduleMenu->getEntries();
		$entry = $this->findByModuleNameInGivenEntries($name, $entries);
		return $entry;
	}

	/**
	 * Finds a module menu entry by name in a given storage
	 *
	 * @param string $name
	 * @param \SplObjectStorage $entries
	 * @return \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule|bool
	 */
	public function findByModuleNameInGivenEntries($name, \SplObjectStorage $entries) {
		foreach ($entries as $entry) {
			if ($entry->getName() === $name) {
				return $entry;
			}
			$children = $entry->getChildren();
			if (count($children) > 0) {
				$childRecord = $this->findByModuleNameInGivenEntries($name, $children);
				if ($childRecord !== FALSE) {
					return $childRecord;
				}
			}
		}
		return FALSE;
	}

}


?>