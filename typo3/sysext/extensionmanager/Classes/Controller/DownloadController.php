<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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
 * Controller for actions related to the TER download of an extension
 *
 * @author Susanne Moog, <typo3@susannemoog.de>
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
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DownloadUtility
	 */
	protected $downloadUtility;

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
	 * @param \TYPO3\CMS\Extensionmanager\Utility\DownloadUtility $downloadUtility
	 * @return void
	 */
	public function injectDownloadUtility(\TYPO3\CMS\Extensionmanager\Utility\DownloadUtility $downloadUtility) {
		$this->downloadUtility = $downloadUtility;
	}

	/**
	 * Check extension dependencies
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @throws \Exception
	 */
	public function checkDependenciesAction(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$message = '';
		$title = '';
		$hasDependencies = FALSE;
		$hasErrors = FALSE;
		try {
			$dependencyTypes = $this->managementService->getAndResolveDependencies($extension);
			if (count($dependencyTypes) > 0) {
				$hasDependencies = TRUE;
				$message = $this->translate('downloadExtension.dependencies.headline');
				foreach ($dependencyTypes as $dependencyType => $dependencies) {
					$extensions = '';
					foreach ($dependencies as $extensionKey => $dependency) {
						$extensions .= htmlspecialchars($extensionKey) . '<br />';
					}
					$message .= $this->translate('downloadExtension.dependencies.typeHeadline',
						array(
							$this->translate('downloadExtension.dependencyType.' . $dependencyType),
							$extensions
						)
					);
				}
				$title = $this->translate('downloadExtension.dependencies.resolveAutomatically');
			}
			$this->view->assign('dependencies', $dependencyTypes);
		} catch (\Exception $e) {
			$hasErrors = TRUE;
			$title = $this->translate('downloadExtension.dependencies.errorTitle');
			$message = $e->getMessage();
		}
		$this->view->assign('extension', $extension)
				->assign('hasDependencies', $hasDependencies)
				->assign('hasErrors', $hasErrors)
				->assign('message', $message)
				->assign('title', $title);
	}

	/**
	 * Install an extension from TER
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @param string $downloadPath
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 */
	public function installFromTerAction(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension, $downloadPath) {
		$result = FALSE;
		$errorMessage = '';
		try {
			$this->downloadUtility->setDownloadPath($downloadPath);
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
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extension->getExtensionKey())) {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::unloadExtension($extension->getExtensionKey());
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