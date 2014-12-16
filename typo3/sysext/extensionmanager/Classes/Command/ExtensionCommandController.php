<?php
namespace TYPO3\CMS\Extensionmanager\Command;

/*
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
		$service = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class);
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
		$service = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class);
		$service->uninstall($extensionKey);
	}

	/**
	 * Emits packages may have changed signal
	 */
	protected function emitPackagesMayHaveChangedSignal() {
		$this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
	}

}
