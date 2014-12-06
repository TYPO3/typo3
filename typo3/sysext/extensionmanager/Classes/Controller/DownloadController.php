<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * Controller for actions related to the TER download of an extension
 *
 * @author Susanne Moog, <typo3@susannemoog.de>
 */
class DownloadController extends AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 * @inject
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
	 * @inject
	 */
	protected $fileHandlingUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService
	 * @inject
	 */
	protected $managementService;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 * @inject
	 */
	protected $installUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DownloadUtility
	 * @inject
	 */
	protected $downloadUtility;

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
	 * Install an extension from TER action
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @param string $downloadPath
	 */
	public function installFromTerAction(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension, $downloadPath) {
		list($result, $errorMessages) = $this->installFromTer($extension, $downloadPath);
		$this->view
			->assign('result', $result)
			->assign('extension', $extension)
			->assign('unresolvedDependencies', $errorMessages);
	}

	/**
	 * Check extension dependencies with special dependencies
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @throws \Exception
	 */
	public function installExtensionWithoutSystemDependencyCheckAction(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$this->managementService->setSkipSystemDependencyCheck(TRUE);
		$this->forward('installFromTer', NULL, NULL, array('extension' => $extension, 'downloadPath' => 'Local'));
	}

	/**
	 * Action for installing a distribution -
	 * redirects directly to configuration after installing
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return void
	 */
	public function installDistributionAction(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('impexp')) {
			$this->forward('distributions', 'List');
		}
		list($result, $errorMessages) = $this->installFromTer($extension);
		if ($errorMessages) {
			foreach ($errorMessages as $extensionKey => $messages) {
				foreach ($messages as $message) {
					$this->addFlashMessage(
						$message['message'],
						\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
							'distribution.error.headline',
							'extensionmanager',
							array($extensionKey)
						),
						\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
					);
				}
			}

			// Redirect back to distributions list action
			$this->redirect(
				'distributions',
				'List'
			);
		} else {
			// FlashMessage that extension is installed
			$this->addFlashMessage(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('distribution.welcome.message', 'extensionmanager')
					. ' <strong>' . $extension->getExtensionKey() . '</strong>',
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('distribution.welcome.headline', 'extensionmanager')
			);

			// Redirect to show action
			$this->redirect(
				'show',
				'Distribution',
				NULL,
				array('extension' => $extension)
			);
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
		$hasErrors = FALSE;
		$errorMessage = '';

		$extensionKey = $this->request->getArgument('extension');
		$highestTerVersionExtension = $this->extensionRepository->findHighestAvailableVersion($extensionKey);
		try {
			$this->managementService->downloadMainExtension($highestTerVersionExtension);
		} catch (\Exception $e) {
			$hasErrors = TRUE;
			$errorMessage = $e->getMessage();
		}
		$this->view->assign('extension', $highestTerVersionExtension)
			->assign('hasErrors', $hasErrors)
			->assign('errorMessage', $errorMessage);
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
		/** @var Extension[] $updatableVersions */
		$updatableVersions = $this->extensionRepository->findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $version);
		foreach ($updatableVersions as $updatableVersion) {
			$updateComments[$updatableVersion->getVersion()] = $updatableVersion->getUpdateComment();
		}
		$this->view->assign('updateComments', $updateComments)->assign('extensionKey', $extensionKey);
	}

	/**
	 * Install an action from TER
	 * Downloads the extension, resolves dependencies and installs it
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @param string $downloadPath
	 * @return array
	 */
	protected function installFromTer(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension, $downloadPath = 'Local') {
		$result = FALSE;
		$errorMessages = array();
		try {
			$this->downloadUtility->setDownloadPath($downloadPath);
			if (($result = $this->managementService->installExtension($extension)) === FALSE) {
				$errorMessages = $this->managementService->getDependencyErrors();
			}
		} catch (\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException $e) {
			$errorMessages = array(
				$extension->getExtensionKey() => array(
					array(
						'code' => $e->getCode(),
						'message' => $e->getMessage(),
					)
				),
			);
		}

		return array($result, $errorMessages);
	}
}
