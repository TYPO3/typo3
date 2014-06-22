<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

/**
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
/**
 * Controller for actions relating to update of full extension list from TER
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class UpdateFromTerController extends AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
	 * @inject
	 */
	protected $repositoryHelper;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
	 * @inject
	 */
	protected $repositoryRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 * @inject
	 */
	protected $listUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 * @inject
	 */
	protected $extensionRepository;

	/**
	 * Update extension list from TER
	 *
	 * @param boolean $forceUpdateCheck
	 * @return void
	 */
	public function updateExtensionListFromTerAction($forceUpdateCheck = FALSE) {
		$updated = FALSE;
		$errorMessage = '';

		if ($this->extensionRepository->countAll() === 0 || $forceUpdateCheck) {
			try {
				$updated = $this->repositoryHelper->updateExtList();
			} catch (\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException $e) {
				$errorMessage = $e->getMessage();
			}
		}
		/** @var $repository \TYPO3\CMS\Extensionmanager\Domain\Model\Repository */
		$repository = $this->repositoryRepository->findByUid((int)$this->settings['repositoryUid']);
		$this->view->assign('updated', $updated)
				->assign('repository', $repository)
				->assign('errorMessage', $errorMessage);
	}
}
