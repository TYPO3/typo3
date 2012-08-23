<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog <typo3@susannemoog.de>
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
 * Controller for actions related to the TER download of an extension
 *
 * @author Susanne Moog, <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Controller
 */
class DownloadController extends \TYPO3\CMS\Extensionmanager\Controller\AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
	 */
	protected $fileHandlingUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService
	 */
	protected $managementService;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 */
	protected $installUtility;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility
	 * @return void
	 */
	public function injectInstallUtility(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility) {
		$this->installUtility = $installUtility;
	}

	/**
	 * Dependency injection of the Extension Repository
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
	 * @return void
	 */
	public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
	 * @return void
	 */
	public function injectFileHandlingUtility(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility) {
		$this->fileHandlingUtility = $fileHandlingUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService
	 * @return void
	 */
	public function injectManagementService(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService) {
		$this->managementService = $managementService;
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
	 * Check extension dependencies
	 *
	 * @throws Exception
	 * @return void
	 */
	public function checkDependenciesAction() {
		if (!$this->request->hasArgument('extension')) {
			throw new \Exception('Required argument extension not set.', 1334433342);
		}
		$extensionUid = $this->request->getArgument('extension');
		/** @var $extension \TYPO3\CMS\Extensionmanager\Domain\Model\Extension */
		$extension = $this->extensionRepository->findByUid(intval($extensionUid));
		$dependencyTypes = $this->managementService->getAndResolveDependencies($extension);
		$message = '';
		if (count($dependencyTypes) > 0) {
			// @todo translate and beautify
			$message = 'The following dependencies have to be resolved before installation:<br /><br />';
			foreach ($dependencyTypes as $dependencyType => $dependencies) {
				$message .= ('<h3>Extensions marked for ' . $dependencyType) . ':</h3>';
				foreach ($dependencies as $extensionKey => $dependency) {
					$message .= $extensionKey . '<br />';
				}
				$message .= 'Shall these dependencies be resolved automatically?';
			}
		}
		$this->view->assign('dependencies', $dependencyTypes)->assign('extension', $extension)->assign('message', $message);
	}

	/**
	 * Install an extension from TER
	 *
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function installFromTerAction() {
		$result = FALSE;
		$errorMessage = '';
		try {
			if (!$this->request->hasArgument('extension')) {
				throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Required argument extension not set.', 1334433342);
			}
			$extensionUid = $this->request->getArgument('extension');
			if ($this->request->hasArgument('downloadPath')) {
				$this->downloadUtility->setDownloadPath($this->request->getArgument('downloadPath'));
			}
			/** @var $extension \TYPO3\CMS\Extensionmanager\Domain\Model\Extension */
			$extension = $this->extensionRepository->findByUid(intval($extensionUid));
			$this->prepareExtensionForImport($extension);
			$result = $this->managementService->resolveDependenciesAndInstall($extension);
		} catch (\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException $e) {
			$errorMessage = $e->getMessage();
		}
		$this->view->assign('result', $result)->assign('extension', $extension)->assign('errorMessage', $errorMessage);
	}

	/**
	 * Prepares an extension for import from TER
	 * Uninstalls the extension if it is already loaded (case: update)
	 * and reloads the caches.
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return void
	 */
	protected function prepareExtensionForImport(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		if (\TYPO3\CMS\Core\Extension\ExtensionManager::isLoaded($extension->getExtensionKey())) {
			\TYPO3\CMS\Core\Extension\ExtensionManager::unloadExtension($extension->getExtensionKey());
			$this->installUtility->reloadCaches();
		}
	}

	/**
	 * Update an extension. Makes no sanity check but directly searches highest
	 * available version from TER and updates. Update check is done by the list
	 * already. This method should only be called if we are sure that there is
	 * an update.
	 *
	 * @return void
	 */
	protected function updateExtensionAction() {
		$extensionKey = $this->request->getArgument('extension');
		/** @var $highestTerVersionExtension \TYPO3\CMS\Extensionmanager\Domain\Model\Extension */
		$highestTerVersionExtension = $this->extensionRepository->findHighestAvailableVersion($extensionKey);
		$this->prepareExtensionForImport($highestTerVersionExtension);
		$result = $this->managementService->resolveDependenciesAndInstall($highestTerVersionExtension);
		$this->view->assign('result', $result)->assign('extension', $highestTerVersionExtension);
	}

	/**
	 * Show update comments for extensions that can be updated.
	 * Fetches update comments for all versions between the current
	 * installed and the highest version.
	 *
	 * @return void
	 */
	protected function updateCommentForUpdatableVersionsAction() {
		$extensionKey = $this->request->getArgument('extension');
		$version = $this->request->getArgument('integerVersion');
		$updateComments = array();
		/** @var $updatableVersion \TYPO3\CMS\Extensionmanager\Domain\Model\Extension */
		$updatableVersions = $this->extensionRepository->findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $version);
		foreach ($updatableVersions as $updatableVersion) {
			$updateComments[$updatableVersion->getVersion()] = $updatableVersion->getUpdateComment();
		}
		$this->view->assign('updateComments', $updateComments)->assign('extensionKey', $extensionKey);
	}

}


?>