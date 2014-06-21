<?php
namespace TYPO3\CMS\Extensionmanager\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Claus Due <claus@namelesscoder.net>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * CommandController for working with extension management through CLI/scheduler
 *
 * @author Claus Due <claus@namelesscoder.net>
 */
class ExtensionCommandController extends CommandController {

	/**
	 * @var bool
	 */
	protected $requestAdminPermissions = TRUE;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * Installs an extension by key
	 *
	 * The extension files must be present in one of the
	 * recognised extension folder paths in TYPO3.
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	public function installCommand($extensionKey) {
		$this->emitPackagesMayHaveChangedSignal();

		/** @var $service \TYPO3\CMS\Extensionmanager\Utility\InstallUtility */
		$service = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility');
		$service->install($extensionKey);
	}

	/**
	 * Uninstalls an extension by key
	 *
	 * The extension files must be present in one of the
	 * recognised extension folder paths in TYPO3.
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	public function uninstallCommand($extensionKey) {
		/** @var $service \TYPO3\CMS\Extensionmanager\Utility\InstallUtility */
		$service = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility');
		$service->uninstall($extensionKey);
	}

	/**
	 * Emits packages may have changed signal
	 */
	protected function emitPackagesMayHaveChangedSignal() {
		$this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
	}
}
