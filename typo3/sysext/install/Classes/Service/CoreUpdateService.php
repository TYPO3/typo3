<?php
namespace TYPO3\CMS\Install\Service;

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
 *
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Core update service.
 * This service handles core updates, all the nasty details are encapsulated
 * here. The single public methods 'depend' on each other, for example a new
 * core has to be downloaded before it can be unpacked.
 *
 * Each method returns only TRUE of FALSE indicating if it was successful or
 * not. Detailed information can be fetched with getMessages() and will return
 * a list of status messages of the previous operation.
 */
class CoreUpdateService {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Install\Service\CoreVersionService
	 * @inject
	 */
	protected $coreVersionService;

	/**
	 * @var array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	protected $messages = array();

	/**
	 * Absolute path to download location
	 *
	 * @var string
	 */
	protected $downloadTargetPath;

	/**
	 * Absolute path to the current core files
	 *
	 * @var string
	 */
	protected $currentCoreLocation;

	/**
	 * Base URI for TYPO3 downloads
	 *
	 * @var string
	 */
	protected $downloadBaseUri;

	/**
	 * Initialize update paths
	 */
	public function initializeObject() {
		$this->setDownloadTargetPath(PATH_site . 'typo3temp/core-update/');
		$this->currentCoreLocation = $this->discoverCurrentCoreLocation();
		$this->downloadBaseUri = $this->coreVersionService->getDownloadBaseUri();
	}

	/**
	 * Check if this installation wants to enable the core updater
	 *
	 * @return boolean
	 */
	public function isCoreUpdateEnabled() {
		$coreUpdateDisabled = getenv('TYPO3_DISABLE_CORE_UPDATER') ?: (getenv('REDIRECT_TYPO3_DISABLE_CORE_UPDATER') ?: FALSE);
		return !$coreUpdateDisabled;
	}

	/**
	 * In future implementations we might implement some smarter logic here
	 *
	 * @return string
	 */
	protected function discoverCurrentCoreLocation() {
		return PATH_site . 'typo3_src';
	}

	/**
	 * Create download location in case the folder does not exist
	 * @todo move this to folder structure
	 *
	 * @param string $downloadTargetPath
	 */
	protected function setDownloadTargetPath($downloadTargetPath) {
		if (!is_dir($downloadTargetPath)) {
			GeneralUtility::mkdir_deep($downloadTargetPath);
		}
		$this->downloadTargetPath = $downloadTargetPath;
	}

	/**
	 * Get messages of previous method call
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function getMessages() {
		return $this->messages;
	}

	/**
	 * Wrapper method for CoreVersionService
	 *
	 * @return boolean TRUE on success
	 */
	public function updateVersionMatrix() {
		$success = TRUE;
		try {
			$this->coreVersionService->updateVersionMatrix();
		} catch (\TYPO3\CMS\Install\Service\Exception\RemoteFetchException $e) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Version matrix could not be fetched from get.typo3.org');
			$message->setMessage(
				'Current version specification could not be fetched from http://get.typo3.org/json.'
				. ' This is probably a network issue, please fix it.'
			);
			$this->messages = array($message);
		}
		return $success;
	}

	/**
	 * Check if an update is possible at all
	 *
	 * @return boolean TRUE on success
	 */
	public function checkPreConditions() {
		$success = TRUE;
		$messages = array();

		/** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
		$statusUtility = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\StatusUtility');

		// Folder structure test: Update can be done only if folder structure returns no errors
		/** @var $folderStructureFacade \TYPO3\CMS\Install\FolderStructure\StructureFacade */
		$folderStructureFacade = $this->objectManager->get('TYPO3\\CMS\\Install\\FolderStructure\\DefaultFactory')->getStructure();
		$folderStructureErrors = $statusUtility->filterBySeverity($folderStructureFacade->getStatus(), 'error');
		$folderStructureWarnings = $statusUtility->filterBySeverity($folderStructureFacade->getStatus(), 'warning');
		if (count($folderStructureErrors) > 0 || count($folderStructureWarnings) > 0) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Automatic core update not possible: Folder structure has errors or warnings');
			$message->setMessage(
				'To perform an update, the folder structure of this TYPO3 CMS instance must'
				. ' stick to the conventions, or the update process could lead to unexpected'
				. ' results and may be hazardous to your system'
			);
			$messages[] = $message;
		}

		// No core update on windows
		if (TYPO3_OS === 'WIN') {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Automatic core update not possible: Update not supported on Windows OS');
			$messages[] = $message;
		}

		if ($success) {
			// Explicit write check to document root
			$file = PATH_site . uniqid('install-core-update-test-');
			$result = @touch($file);
			if (!$result) {
				$success = FALSE;
				/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('Automatic core update not possible: No write access to document root');
				$message->setMessage('Could not write a file in path "' . PATH_site . '"!');
				$messages[] = $message;
			} else {
				unlink($file);
			}

			// Explicit write check to upper directory of current core location
			$coreLocation = @realPath($this->currentCoreLocation . '/../');
			$file = $coreLocation . '/' . uniqid('install-core-update-test-');
			$result = @touch($file);
			if (!$result) {
				$success = FALSE;
				/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('Automatic core update not possible: No write access to core location');
				$message->setMessage(
					'New core should be installed in "' . $coreLocation . '", but this directory is not writable!'
				);
				$messages[] = $message;
			} else {
				unlink($file);
			}
		}

		if ($success && !$this->coreVersionService->isInstalledVersionAReleasedVersion()) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Automatic core update not possible: You are running a development version of TYPO3');
			$message->setMessage(
				'Your current version is specified as ' . $this->coreVersionService->getInstalledVersion() . '.'
				. ' This is a development version and can not be updated automatically. If this is a "git"'
				. ' checkout, please update using git directly.'
			);
			$messages[] = $message;
		}

		$this->messages = $messages;
		return $success;
	}

	/**
	 * Download the specified version
	 *
	 * @param string $version A version to download
	 * @return boolean TRUE on success
	 */
	public function downloadVersion($version) {
		$downloadUri = $this->downloadBaseUri . $version;
		$fileLocation = $this->getDownloadTarGzTargetPath($version);

		$messages = array();
		$success = TRUE;

		if (@file_exists($fileLocation)) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Core download exists in download location: ' . \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($this->downloadTargetPath));
			$messages[] = $message;
		} else {
			$fileContent = GeneralUtility::getUrl($downloadUri);
			if (!$fileContent) {
				$success = FALSE;
				/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('Download not successful');
				$messages[] = $message;
			} else {
				$fileStoreResult = file_put_contents($fileLocation, $fileContent);
				if (!$fileStoreResult) {
					$success = FALSE;
					/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
					$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
					$message->setTitle('Unable to store download content');
					$messages[] = $message;
				}
			}
		}
		$this->messages = $messages;
		return $success;
	}

	/**
	 * Verify checksum of downloaded version
	 *
	 * @param string $version A downloaded version to check
	 * @return boolean TRUE on success
	 */
	public function verifyFileChecksum($version) {
		$fileLocation = $this->getDownloadTarGzTargetPath($version);
		$expectedChecksum = $this->coreVersionService->getTarGzSha1OfVersion($version);

		$messages = array();
		$success = TRUE;

		if (!file_exists($fileLocation)) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Downloaded core not found');
			$messages[] = $message;
		} else {
			$actualChecksum = sha1_file($fileLocation);
			if ($actualChecksum !== $expectedChecksum) {
				$success = FALSE;
				/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('New core checksum mismatch');
				$message->setMessage(
					'The official TYPO3 CMS version system on https://get.typo3.org expects a sha1 checksum of '
					. $expectedChecksum . ' from the content of the downloaded new core version ' . $version . '.'
					. ' The actual checksum is ' . $actualChecksum . '. The update is stopped. This may be a'
					. ' failed download, an attack, or an issue with the typo3.org infrastructure.'
				);
				$messages[] = $message;
			}
		}
		$this->messages = $messages;
		return $success;
	}

	/**
	 * Unpack a downloaded core
	 *
	 * @param string $version A version to unpack
	 * @return boolean TRUE on success
	 */
	public function unpackVersion($version) {
		$fileLocation = $this->downloadTargetPath . $version . '.tar.gz';

		$messages = array();
		$success = TRUE;

		if (!@is_file($fileLocation)) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Downloaded core not found');
			$messages[] = $message;
		} elseif (@file_exists($this->downloadTargetPath . 'typo3_src-' . $version)) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Unpacked core exists in download location: ' . \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($this->downloadTargetPath));
			$messages[] = $message;
		} else {
			$unpackCommand = 'tar xf ' . escapeshellarg($fileLocation) . ' -C ' . escapeshellarg($this->downloadTargetPath) . ' 2>&1';
			exec($unpackCommand, $output, $errorCode);
			if ($errorCode) {
				$success = FALSE;
				/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('Unpacking core not successful');
				$messages[] = $message;
			} else {
				$removePackedFileResult = unlink($fileLocation);
				if (!$removePackedFileResult) {
					$success = FALSE;
					/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
					$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
					$message->setTitle('Removing packed core not successful');
					$messages[] = $message;
				}
			}
		}
		$this->messages = $messages;
		return $success;
	}

	/**
	 * Move an unpacked core to its final destination
	 *
	 * @param string $version A version to move
	 * @return boolean TRUE on success
	 */
	public function moveVersion($version) {
		$downloadedCoreLocation = $this->downloadTargetPath . 'typo3_src-' . $version;
		$newCoreLocation = @realPath($this->currentCoreLocation . '/../') . '/typo3_src-' . $version;

		$messages = array();
		$success = TRUE;

		if (!@is_dir($downloadedCoreLocation)) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Unpacked core not found');
			$messages[] = $message;
		} elseif (@is_dir($newCoreLocation)) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Another core source directory already exists in path ' . $newCoreLocation);
			$messages[] = $message;
		} else {
			$moveResult = rename($downloadedCoreLocation, $newCoreLocation);
			if (!$moveResult) {
				$success = FALSE;
				/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('Moving core to ' . $newCoreLocation . ' failed');
				$messages[] = $message;
			}
		}

		$this->messages = $messages;
		return $success;
	}

	/**
	 * Activate a core version
	 *
	 * @param string $version A version to activate
	 * @return boolean TRUE on success
	 */
	public function activateVersion($version) {
		$newCoreLocation = @realPath($this->currentCoreLocation . '/../') . '/typo3_src-' . $version;

		$messages = array();
		$success = TRUE;

		if (!is_dir($newCoreLocation)) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('New core not found');
			$messages[] = $message;
		} elseif (!is_link($this->currentCoreLocation)) {
			$success = FALSE;
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('TYPO3 source directory (typo3_src) is not a link');
			$messages[] = $message;
		} else {
			$unlinkResult = unlink($this->currentCoreLocation);
			if (!$unlinkResult) {
				$success = FALSE;
				/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('Removing old symlink failed');
				$messages[] = $message;
			} else {
				$symlinkResult = symlink($newCoreLocation, $this->currentCoreLocation);
				if (!$symlinkResult) {
					$success = FALSE;
					/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
					$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
					$message->setTitle('Linking new core failed');
					$messages[] = $message;
				}
			}
		}

		$this->messages = $messages;
		return $success;
	}

	/**
	 * Absolute path of downloaded .tar.gz
	 *
	 * @param string $version A version number
	 * @return string
	 */
	protected function getDownloadTarGzTargetPath($version) {
		return $this->downloadTargetPath . $version . '.tar.gz';
	}
}