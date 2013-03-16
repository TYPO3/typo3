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
 */
class ExtensionManagementService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue
	 */
	protected $downloadQueue;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility
	 */
	protected $dependencyUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 */
	protected $installUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 */
	protected $listUtility;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue $downloadQueue
	 * @return void
	 */
	public function injectDownloadQueue(\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue $downloadQueue) {
		$this->downloadQueue = $downloadQueue;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility
	 * @return void
	 */
	public function injectDependencyUtility(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility) {
		$this->dependencyUtility = $dependencyUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility
	 * @return void
	 */
	public function injectInstallUtility(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility) {
		$this->installUtility = $installUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
	 * @return void
	 */
	public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DownloadUtility
	 */
	protected $downloadUtility;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\DownloadUtility $downloadUtility
	 * @return void
	 */
	public function injectDownloadUtility(\TYPO3\CMS\Extensionmanager\Utility\DownloadUtility $downloadUtility) {
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
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return void
	 */
	public function markExtensionForDownload(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$this->downloadQueue->addExtensionToQueue($extension);
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return void
	 */
	public function markExtensionForUpdate(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$this->downloadQueue->addExtensionToQueue($extension, 'update');
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension|array $extension
	 * @return array
	 */
	public function resolveDependenciesAndInstall($extension) {
		if (!is_array($extension) && !$extension instanceof \TYPO3\CMS\Extensionmanager\Domain\Model\Extension) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Extension must be array or object.', 1350891642);
		}
		$this->dependencyUtility->buildExtensionDependenciesTree($extension);
		if ($extension instanceof \TYPO3\CMS\Extensionmanager\Domain\Model\Extension) {
			// We have a TER Extension, which should be downloaded first.
			$this->downloadQueue->addExtensionToQueue($extension);
			$extensionKey = $extension->getExtensionKey();
		} else {
			$extensionKey = $extension['key'];
		}
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
		$this->downloadQueue->addExtensionToInstallQueue($extensionKey);
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
		foreach ($installQueue as $extensionKey => $extensionDetails) {
			$this->installUtility->install($extensionDetails);
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

}


?>