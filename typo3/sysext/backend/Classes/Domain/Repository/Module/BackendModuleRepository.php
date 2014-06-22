<?php
namespace TYPO3\CMS\Backend\Domain\Repository\Module;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
