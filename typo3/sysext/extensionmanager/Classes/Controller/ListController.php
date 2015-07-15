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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility;
use TYPO3\CMS\Extensionmanager\Utility\Repository\Helper;

/**
 * Controller for extension listings (TER or local extensions)
 */
class ListController extends AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 * @inject
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 * @inject
	 */
	protected $listUtility;

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 * @inject
	 */
	protected $pageRenderer;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility
	 * @inject
	 */
	protected $dependencyUtility;

	/**
	 * Add the needed JavaScript files for all actions
	 */
	public function initializeAction() {
		$this->pageRenderer->addInlineLanguageLabelFile('EXT:extensionmanager/Resources/Private/Language/locallang.xlf');
	}

	/**
	 * Shows list of extensions present in the system
	 *
	 * @return void
	 */
	public function indexAction() {
		$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
		$this->view->assign('extensions', $availableAndInstalledExtensions);
		$this->handleTriggerArguments();
	}

	/**
	 * Shows a list of unresolved dependency errors with the possibility to bypass the dependency check
	 *
	 * @param string $extensionKey
	 * @throws ExtensionManagerException
	 * @return void
	 */
	public function unresolvedDependenciesAction($extensionKey) {
		$availableExtensions = $this->listUtility->getAvailableExtensions();
		if (isset($availableExtensions[$extensionKey])) {
			$extensionArray = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation(
				array(
					$extensionKey => $availableExtensions[$extensionKey]
				)
			);
			/** @var ExtensionModelUtility $extensionModelUtility */
			$extensionModelUtility = $this->objectManager->get(ExtensionModelUtility::class);
			$extension = $extensionModelUtility->mapExtensionArrayToModel($extensionArray[$extensionKey]);
		} else {
			throw new ExtensionManagerException('Extension ' . $extensionKey . ' is not available', 1402421007);
		}
		$this->dependencyUtility->checkDependencies($extension);
		$this->view->assign('extension', $extension);
		$this->view->assign('unresolvedDependencies', $this->dependencyUtility->getDependencyErrors());
	}

	/**
	 * Shows extensions from TER
	 * Either all extensions or depending on a search param
	 *
	 * @param string $search
	 * @return void
	 */
	public function terAction($search = '') {
		if (!empty($search)) {
			$extensions = $this->extensionRepository->findByTitleOrAuthorNameOrExtensionKey($search);
		} else {
			$extensions = $this->extensionRepository->findAll();
		}
		$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
		$this->view->assign('extensions', $extensions)
				->assign('search', $search)
				->assign('availableAndInstalled', $availableAndInstalledExtensions);
	}

	/**
	 * Action for listing all possible distributions
	 *
	 * @return void
	 */
	public function distributionsAction() {
		$importExportInstalled = ExtensionManagementUtility::isLoaded('impexp');
		if ($importExportInstalled) {
			try {
				/** @var $repositoryHelper Helper */
				$repositoryHelper = $this->objectManager->get(Helper::class);
				// Check if a TER update has been done at all, if not, fetch it directly
				// Repository needs an update, but not because of the extension hash has changed
				$isExtListUpdateNecessary = $repositoryHelper->isExtListUpdateNecessary();
				if ($isExtListUpdateNecessary > 0 && ($isExtListUpdateNecessary & $repositoryHelper::PROBLEM_EXTENSION_HASH_CHANGED) === 0) {
					$repositoryHelper->updateExtList();
				}
			} catch (ExtensionManagerException $e) {
				$this->addFlashMessage($e->getMessage(), $e->getCode(), FlashMessage::ERROR);
			}

			$officialDistributions = $this->extensionRepository->findAllOfficialDistributions();
			$this->view->assign('officialDistributions', $officialDistributions);

			$communityDistributions = $this->extensionRepository->findAllCommunityDistributions();
			$this->view->assign('communityDistributions', $communityDistributions);
		}
		$this->view->assign('enableDistributionsView', $importExportInstalled);
	}

	/**
	 * Shows all versions of a specific extension
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	public function showAllVersionsAction($extensionKey) {
		$currentVersion = $this->extensionRepository->findOneByCurrentVersionByExtensionKey($extensionKey);
		$extensions = $this->extensionRepository->findByExtensionKeyOrderedByVersion($extensionKey);

		$this->view->assignMultiple(
			array(
				'extensionKey' => $extensionKey,
				'currentVersion' => $currentVersion,
				'extensions' => $extensions
			)
		);
	}

}
