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
 * Controller for extension listings (TER or local extensions)
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
	 * @var Tx_Extensionmanager_Utility_List
	 */
	protected $listUtility;

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
	 * @param Tx_Extensionmanager_Utility_List $listUtility
	 * @return void
	 */
	public function injectListUtility(Tx_Extensionmanager_Utility_List $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * @param t3lib_PageRenderer $pageRenderer
	 * @return void
	 */
	public function injectPageRenderer(t3lib_PageRenderer $pageRenderer) {
		$this->pageRenderer = $pageRenderer;
	}

	/**
	 * Shows list of extensions present in the system
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->pageRenderer->addJsFile('../t3lib/js/extjs/notifications.js');
		$availableExtensions = $this->listUtility->getAvailableExtensions();
		$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensions($availableExtensions);
		$availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation(
			$availableAndInstalledExtensions
		);
		$this->view->assign('extensions', $availableAndInstalledExtensions);
	}

	/**
	 * Shows extensions from TER
	 * Either all extensions or depending on a search param
	 *
	 * @return void
	 */
	public function terAction() {
		$this->pageRenderer->addJsFile('../t3lib/js/extjs/notifications.js');
		$search = $this->getSearchParam();

		$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();

		if (is_string($search) && !empty($search)) {
			$extensions = $this->extensionRepository->findByTitleOrAuthorNameOrExtensionKey($search);
		} else {
			$extensions = $this->extensionRepository->findAll();
		}
		$this->view
			->assign('extensions', $extensions)
			->assign('search', $search)
			->assign('availableAndInstalled', $availableAndInstalledExtensions);
	}

	/**
	 * Shows all versions of a specific extension
	 *
	 * @return void
	 */
	public function showAllVersionsAction() {
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/notifications.js');
		$extensions = array();
		$extensionKey = '';
		if (
			$this->request->hasArgument('allVersions') &&
			$this->request->getArgument('allVersions') == 1 &&
			$this->request->hasArgument('extensionKey') &&
			is_string($this->request->getArgument('extensionKey'))
		) {
			$extensionKey = $this->request->getArgument('extensionKey');
			$extensions = $this->extensionRepository->findByExtensionKeyOrderedByVersion($extensionKey);
		} else {
			$this->redirect('ter');
		}
		$this->view
			->assign('extensions', $extensions)
			->assign('extensionKey', $extensionKey);
	}

	/**
	 * Gets the search parameter either from the url or out
	 * of the session if present
	 *
	 * @return string
	 */
	public function getSearchParam() {
		$search = '';
		if ($this->request->hasArgument('search') && is_string($this->request->getArgument('search'))) {
			$search = $this->request->getArgument('search');
		}
		return $search;
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