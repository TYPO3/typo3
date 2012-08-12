<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <susanne.moog@typo3.org>
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
 * Service class for managing multiple step processes (dependencies for example)
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Service_Management implements t3lib_Singleton {

	/**
	 * @var Tx_Extensionmanager_Domain_Model_DownloadQueue
	 */
	protected $downloadQueue;

	/**
	 * @var Tx_Extensionmanager_Utility_Dependency
	 */
	protected $dependencyUtility;

	/**
	 * @var Tx_Extensionmanager_Utility_Install
	 */
	protected $installUtility;

	/**
	 * @var Tx_Extensionmanager_Utility_List
	 */
	protected $listUtility;

	/**
	 * @param Tx_Extensionmanager_Domain_Model_DownloadQueue $downloadQueue
	 * @return void
	 */
	public function injectDownloadQueue(Tx_Extensionmanager_Domain_Model_DownloadQueue $downloadQueue) {
		$this->downloadQueue = $downloadQueue;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_Dependency $dependencyUtility
	 * @return void
	 */
	public function injectDependencyUtility(Tx_Extensionmanager_Utility_Dependency $dependencyUtility) {
		$this->dependencyUtility = $dependencyUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_Install $installUtility
	 * @return void
	 */
	public function injectInstallUtility(Tx_Extensionmanager_Utility_Install $installUtility) {
		$this->installUtility = $installUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_List $listUtility
	 * @return void
	 */
	public function injectListUtility(Tx_Extensionmanager_Utility_List $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * @var Tx_Extensionmanager_Utility_Download
	 */
	protected $downloadUtility;

	/**
	 * @param Tx_Extensionmanager_Utility_Download $downloadUtility
	 * @return void
	 */
	public function injectDownloadUtility(Tx_Extensionmanager_Utility_Download $downloadUtility) {
		$this->downloadUtility = $downloadUtility;
	}

	/**
	 * @param string $extensionKey
	 * @return void
	 */
	public function markExtensionForInstallation($extensionKey) {
		$this->downloadQueue->addExtensionToInstallQueue($extensionKey);
	}

	/**
	 * Mark an extension for download
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return void
	 */
	public function markExtensionForDownload(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		$this->downloadQueue->addExtensionToQueue($extension);
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
	}

	/**
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return void
	 */
	public function markExtensionForUpdate(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		$this->downloadQueue->addExtensionToQueue($extension, 'update');
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
	}

	/**
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return array
	 */
	public function resolveDependenciesAndInstall(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
		$this->downloadQueue->addExtensionToQueue($extension);

		$queue = $this->downloadQueue->getExtensionQueue();
		$downloadedDependencies = array();
		$updatedDependencies = array();
		$installedDependencies = array();

		if (array_key_exists('download', $queue)) {
			$downloadedDependencies = $this->downloadDependencies($queue['download']);
		}

		if (array_key_exists('update', $queue)) {
			$this->downloadDependencies($queue['update']);
			$updatedDependencies = $this->uninstallDependenciesToBeUpdated($queue['update']);
		}

			// add extension at the end of the download queue
		$this->downloadQueue->addExtensionToInstallQueue($extension->getExtensionKey());

		$installQueue = $this->downloadQueue->getExtensionInstallStorage();
		if (count($installQueue) > 0) {
			$installedDependencies = $this->installDependencies($installQueue);
		}

		return array_merge($downloadedDependencies, $updatedDependencies, $installedDependencies);
	}

	/**
	 * Uninstall extensions that will be updated
	 * This is not strictly necessary but cleaner all in all
	 *
	 * @param array<Tx_Extensionmanager_Domain_Model_Extension> $updateQueue
	 * @return array
	 */
	protected function uninstallDependenciesToBeUpdated(array $updateQueue) {
		$resolvedDependencies = array();
		foreach ($updateQueue as $extensionToUpdate) {
			$this->installUtility->uninstall($extensionToUpdate->getExtensionKey());
			$resolvedDependencies['updated'][$extensionToUpdate->getExtensionKey()] = $extensionToUpdate;
		}
		return $resolvedDependencies;
	}

	/**
	 * Install dependent extensions
	 *
	 * @param array $installQueue
	 * @return array
	 */
	protected function installDependencies(array $installQueue) {
		$resolvedDependencies = array();
		foreach ($installQueue as $extensionToInstall) {
			$this->installUtility->install($extensionToInstall);
			$resolvedDependencies['installed'][$extensionToInstall] = $extensionToInstall;
		}
		return $resolvedDependencies;
	}

	/**
	 * Download dependencies
	 * expects an array of extension objects to download
	 *
	 * @param array<Tx_Extensionmanager_Domain_Model_Extension> $downloadQueue
	 * @return array
	 */
	protected function downloadDependencies(array $downloadQueue) {
		$resolvedDependencies = array();
		foreach ($downloadQueue as $extensionToDownload) {
			$this->downloadUtility->download($extensionToDownload);
			$this->downloadQueue->removeExtensionFromQueue($extensionToDownload);
			$resolvedDependencies['downloaded'][$extensionToDownload->getExtensionKey()] = $extensionToDownload;
			$this->markExtensionForInstallation($extensionToDownload->getExtensionKey());
		}
		return $resolvedDependencies;
	}

	/**
	 * Get and resolve dependencies
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return array
	 */
	public function getAndResolveDependencies(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
		$installQueue = $this->downloadQueue->getExtensionInstallStorage();
		if (is_array($installQueue) && count($installQueue) > 0) {
			$installQueue = array('install' => $installQueue);
		}
		return array_merge($this->downloadQueue->getExtensionQueue(), $installQueue);
	}
}

?>