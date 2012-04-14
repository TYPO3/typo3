<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012
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
 * action controller.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Controller
 */
class Tx_Extensionmanager_Controller_ListController extends Tx_Extensionmanager_Controller_AbstractController {


	/**
	 * @var Tx_Extensionmanager_Domain_Repository_ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * Dependency injection of the Extension Repository
	 * @param Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository
	 * @return void
-	 */
	public function injectExtensionRepository(Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	public function indexAction() {
		/** @var $listUtility Tx_Extensionmanager_Utility_List */
		$listUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_List');
		$availableExtensions = $listUtility->getAvailableExtensions();
		$availableAndInstalledExtensions = $listUtility->getAvailableAndInstalledExtensions($availableExtensions);
		$availableAndInstalledExtensions = $listUtility->enrichExtensionsWithEmConfInformation($availableAndInstalledExtensions);
		$this->view->assign('extensions', $availableAndInstalledExtensions);
	}

	public function terAction() {
		$search = '';
		if ($this->request->hasArgument('search') && is_string($this->request->getArgument('search'))) {
			$search = $this->request->getArgument('search');
		}

			// is a search param present in the session?
		if(empty($search)) {
			$moduleData = json_decode($GLOBALS['BE_USER']->getModuleData(get_class($this)));
			if (isset($moduleData->search)) {
				$search = $moduleData->search;
			}
		}

		if(is_string($search) && !empty($search)) {
			$extensions = $this->extensionRepository->findByTitleOrAuthorNameOrExtensionKey($search);
			$GLOBALS['BE_USER']->pushModuleData(
				get_class($this),
				json_encode(
					array('search' => $search)
				)
			);
		} else {
			$extensions = $this->extensionRepository->findAll();
			$GLOBALS['BE_USER']->pushModuleData(
				get_class($this),
				json_encode(
					array('search' => '')
				)
			);
		}
		$this->view->assign('extensions', $extensions);
	}

	/**
	* Gets instance of template if exists or create a new one.
	* Saves instance in viewHelperVariableContainer
	*
	* @return template $doc
	*/
	public function getDocInstance() {
		if (!isset($GLOBALS['SOBE']->doc)) {
			$GLOBALS['SOBE']->doc = t3lib_div::makeInstance('template');
			$GLOBALS['SOBE']->doc->backPath = $GLOBALS['BACK_PATH'];
		}
		return $GLOBALS['SOBE']->doc;
	}
}
?>
