<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 * @package Extension Manager
 * @subpackage Controller
 */
class Tx_Extensionmanager_Controller_UpdateFromTerController extends Tx_Extensionmanager_Controller_AbstractController {

	/**
	 * @var Tx_Extensionmanager_Utility_Repository_Helper
	 */
	protected $repositoryHelper;

	/**
	 * @var Tx_Extensionmanager_Domain_Repository_RepositoryRepository
	 */
	protected $repositoryRepository;

	/**
	 * @var Tx_Extensionmanager_Utility_List
	 */
	protected $listUtility;

	/**
	 * @var Tx_Extensionmanager_Domain_Repository_ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * Dependency injection of the Extension Repository
	 *
	 * @param Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository
	 * @return void
	 */
	public function injectExtensionRepository(Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * Dependency injection of the Repository Helper Utility
	 *
	 * @param Tx_Extensionmanager_Utility_Repository_Helper $repositoryHelper
	 * @return void
	 */
	public function injectRepositoryHelper(Tx_Extensionmanager_Utility_Repository_Helper $repositoryHelper) {
		$this->repositoryHelper = $repositoryHelper;
	}

	/**
	 * Dependency injection of repository repository
	 *
	 * @param Tx_Extensionmanager_Domain_Repository_RepositoryRepository $repositoryRepository
	 * @return void
	 */
	public function injectRepositoryRepository(Tx_Extensionmanager_Domain_Repository_RepositoryRepository $repositoryRepository) {
		$this->repositoryRepository = $repositoryRepository;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_List $listUtility
	 * @return void
	 */
	public function injectListUtility(Tx_Extensionmanager_Utility_List $listUtility) {
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

		/** @var $repository Tx_Extensionmanager_Domain_Model_Repository */
		$repository = $this->repositoryRepository->findOneByUid((int)$this->settings['repositoryUid']);
		if ($repository->getLastUpdate()->getTimestamp() < ($GLOBALS['EXEC_TIME'] - 24 * 60 * 60) || $forceUpdateCheck) {
			try {
				$updated = $this->repositoryHelper->updateExtList();
			} catch (Tx_Extensionmanager_Exception_ExtensionManager $e) {
				$errorMessage = $e->getMessage();
			}
		}
		$this->view->assign('updated', $updated)
			->assign('repository', $repository)
			->assign('errorMessage', $errorMessage);
	}

}
?>