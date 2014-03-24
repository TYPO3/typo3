<?php
namespace TYPO3\CMS\Extensionmanager\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog, <susanne.moog@typo3.org>
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
/**
 * Service class for managing multiple step processes (dependencies for example)
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class ExtensionManagementService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue
	 * @inject
	 */
	protected $downloadQueue;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility
	 * @inject
	 */
	protected $dependencyUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 * @inject
	 */
	protected $installUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility
	 * @inject
	 */
	protected $extensionModelUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 * @inject
	 */
	protected $listUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DownloadUtility
	 * @inject
	 */
	protected $downloadUtility;

	/**
	 * @param string $extensionKey
	 * @return void
	 */
	public function markExtensionForInstallation($extensionKey) {
		// We have to check for dependencies of the extension first, before marking it for installation
		// because this extension might have dependencies, which need to be installed first
		$this->dependencyUtility->buildExtensionDependenciesTree(
			$this->extensionModelUtility->mapExtensionArrayToModel(
				$this->installUtility->enrichExtensionWithDetails($extensionKey)
			)
		);
		$this->downloadQueue->addExtensionToInstallQueue($extensionKey);
	}

	/**
	 * Mark an extension for copy
	 *
	 * @param string $extensionKey
	 * @param string $sourceFolder
	 * @return void
	 */
	public function markExtensionForCopy($extensionKey, $sourceFolder) {
		$this->downloadQueue->addExtensionToCopyQueue($extensionKey, $sourceFolder);
	}

	/**
	 * Mark an extension for download
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return void
	 */
	public function markExtensionForDownload(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		// We have to check for dependencies of the extension first, before marking it for download
		// because this extension might have dependencies, which need to be downloaded and installed first
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
		$this->downloadQueue->addExtensionToQueue($extension);
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return void
	 */
	public function markExtensionForUpdate(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		// We have to check for dependencies of the extension first, before marking it for download
		// because this extension might have dependencies, which need to be downloaded and installed first
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
		$this->downloadQueue->addExtensionToQueue($extension, 'update');
	}

	/**
	 * Resolve an extensions dependencies (download, copy and install dependent
	 * extensions) and install the extension
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return array
	 */
	public function resolveDependenciesAndInstall(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$this->downloadMainExtension($extension);
		$extensionKey = $extension->getExtensionKey();
		$this->setInExtensionRepository($extensionKey);
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);

		$updatedDependencies = array();
		$installedDependencies = array();
		$queue = $this->downloadQueue->getExtensionQueue();
		$copyQueue = $this->downloadQueue->getExtensionCopyStorage();

		if (count($copyQueue) > 0) {
			$this->copyDependencies($copyQueue);
		}
		$downloadedDependencies = array();
		if (array_key_exists('download', $queue)) {
			$downloadedDependencies = $this->downloadDependencies($queue['download']);
		}
		if (array_key_exists('update', $queue)) {
			$this->downloadDependencies($queue['update']);
			$updatedDependencies = $this->uninstallDependenciesToBeUpdated($queue['update']);
		}
		// add extension at the end of the download queue
		$this->downloadQueue->addExtensionToInstallQueue($extensionKey);
		$installQueue = $this->downloadQueue->getExtensionInstallStorage();
		if (count($installQueue) > 0) {
			$installedDependencies = $this->installDependencies($installQueue);
		}
		return array_merge($downloadedDependencies, $updatedDependencies, $installedDependencies);
	}

	/**
	 * Sets the path to the repository in an extension
	 * (Initialisation/Extensions) depending on the extension
	 * that is currently installed
	 *
	 * @param string $extensionKey
	 */
	protected function setInExtensionRepository($extensionKey) {
		$paths = \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::returnInstallPaths();
		$path = $paths[$this->downloadUtility->getDownloadPath()];
		$localExtensionStorage = $path . $extensionKey . '/Initialisation/Extensions/';
		$this->dependencyUtility->setLocalExtensionStorage($localExtensionStorage);
	}

	/**
	 * Copies locally provided extensions to typo3conf/ext
	 *
	 * @param array $copyQueue
	 * @return void
	 */
	protected function copyDependencies(array $copyQueue) {
		$installPaths = \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::returnAllowedInstallPaths();
		foreach ($copyQueue as $extensionKey => $sourceFolder) {
			$destination = $installPaths['Local'] . $extensionKey;
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($destination);
			\TYPO3\CMS\Core\Utility\GeneralUtility::copyDirectory($sourceFolder . $extensionKey, $destination);
			$this->markExtensionForInstallation($extensionKey);
			$this->downloadQueue->removeExtensionFromCopyQueue($extensionKey);
		}
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
		if (!empty($installQueue)) {
			$this->emitWillInstallExtensions($installQueue);
		}
		$resolvedDependencies = array();
		foreach ($installQueue as $extensionKey => $extensionDetails) {
			$this->installUtility->install($extensionDetails);
			$this->emitHasInstalledExtension($extensionDetails);
			if (!is_array($resolvedDependencies['installed'])) {
				$resolvedDependencies['installed'] = array();
			}
			$resolvedDependencies['installed'][$extensionKey] = $extensionDetails;
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
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return array
	 */
	public function getAndResolveDependencies(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
		$installQueue = $this->downloadQueue->getExtensionInstallStorage();
		if (is_array($installQueue) && count($installQueue) > 0) {
			$installQueue = array('install' => $installQueue);
		}
		return array_merge($this->downloadQueue->getExtensionQueue(), $installQueue);
	}

	/**
	 * Downloads the extension the user wants to install
	 * This is separated from downloading the dependencies
	 * as an extension is able to provide it's own dependencies
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return void
	 */
	public function downloadMainExtension(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		// The extension object has a uid if the extension is not present in the system
		// or an update of a present extension is triggered.
		if ($extension->getUid()) {
			$this->downloadUtility->download($extension);
		}
	}

	/**
	 * @param array $installQueue
	 */
	protected function emitWillInstallExtensions(array $installQueue) {
		$this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'willInstallExtensions', array($installQueue));
	}

	/**
	 * @param string $extensionKey
	 */
	protected function emitHasInstalledExtension($extensionKey) {
		$this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'hasInstalledExtensions', array($extensionKey));
	}

	/**
	 * Get the SignalSlot dispatcher
	 *
	 * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected function getSignalSlotDispatcher() {
		if (!isset($this->signalSlotDispatcher)) {
			$this->signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
				->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
		}
		return $this->signalSlotDispatcher;
	}

}
