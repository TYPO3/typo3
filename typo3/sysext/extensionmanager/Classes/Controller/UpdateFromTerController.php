<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog, <typo3@susannemoog.de>
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
 * Controller for actions relating to update of full extension list from TER
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class UpdateFromTerController extends \TYPO3\CMS\Extensionmanager\Controller\AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
	 */
	protected $repositoryHelper;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
	 */
	protected $repositoryRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 */
	protected $listUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 */
	protected $extensionRepository;

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
	 * Dependency injection of the Repository Helper Utility
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper
	 * @return void
	 */
	public function injectRepositoryHelper(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper) {
		$this->repositoryHelper = $repositoryHelper;
	}

	/**
	 * Dependency injection of repository repository
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository $repositoryRepository
	 * @return void
	 */
	public function injectRepositoryRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository $repositoryRepository) {
		$this->repositoryRepository = $repositoryRepository;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
	 * @return void
	 */
	public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility) {
		$this->listUtility = $listUtility;
	}

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
?>