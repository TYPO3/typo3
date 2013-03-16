<?php
namespace TYPO3\CMS\Install\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
 * Dispatches update wizard tasks.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class UpdateDispatcherService implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * @var \TYPO3\CMS\Install\Installer
	 */
	protected $installer;

	/**
	 * @param \TYPO3\CMS\Install\Installer $installer
	 */
	public function __construct(\TYPO3\CMS\Install\Installer $installer) {
		$this->getDatabase()->store_lastBuiltQuery = TRUE;
		$this->installer = $installer;
	}

	/**
	 * Dispatches updates that are also used to initialize TYPO3.
	 *
	 * @return void
	 */
	public function dispatchInitializeUpdates() {
		$queries = array();
		$messages = array();

		foreach ($this->findInitializeUpdates() as $initializeUpdate) {
			$initializeUpdate->performUpdate($queries, $messages);
		}
	}

	/**
	 * Gets class names extending \TYPO3\CMS\Install\Updates\InitializeUpdate
	 *
	 * @return array|\TYPO3\CMS\Install\Updates\AbstractUpdate[]|\TYPO3\CMS\Install\Updates\InstallerProcessInterface[]
	 */
	protected function findInitializeUpdates() {
		$initializeUpdates = array();

		foreach ($this->findAllUpdates() as $update) {
			if ($update instanceof \TYPO3\CMS\Install\Updates\InstallerProcessInterface) {
				$initializeUpdates[] = $update;
			}
		}

		return $initializeUpdates;
	}

	/**
	 * Gets all registered updates.
	 *
	 * @return array|\TYPO3\CMS\Install\Updates\AbstractUpdate[]
	 */
	protected function findAllUpdates() {
		$updates = array();

		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $identifier => $className) {
			$update = $this->createUpdateObject($identifier, $className);

			if ($update !== NULL) {
				$updates[] = $update;
			}
		}

		return $updates;
	}

	/**
	 * Creates an update object.
	 *
	 * @param string $identifier
	 * @param string $className
	 * @return \TYPO3\CMS\Install\Updates\AbstractUpdate
	 * @throws \RuntimeException
	 */
	protected function createUpdateObject($identifier, $className) {
		$updateObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($className);

		if (!$updateObject instanceof \TYPO3\CMS\Install\Updates\AbstractUpdate) {
			throw new \RuntimeException(
				'Update class ' . $className . ' must implement \TYPO3\CMS\Install\Updates\AbstractUpdate',
				1346336675
			);
		}

		/** @var $updateObject \TYPO3\CMS\Install\Updates\AbstractUpdate */
		$updateObject->setIdentifier($identifier);
		$updateObject->versionNumber = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
		$updateObject->pObj = $this->installer;

		if (isset($this->installer->INSTALL['update'][$identifier])) {
			$updateObject->userInput = $this->installer->INSTALL['update'][$identifier];
		}

		return $updateObject;
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabase() {
		return $GLOBALS['TYPO3_DB'];
	}
}

?>