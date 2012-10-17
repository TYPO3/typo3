<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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
class ListController extends \TYPO3\CMS\Extensionmanager\Controller\AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 */
	protected $listUtility;

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
	 * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
	 * @return void
	 */
	public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
	 * @return void
	 */
	public function injectPageRenderer(\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer) {
		$this->pageRenderer = $pageRenderer;
	}

	/**
	 * Shows list of extensions present in the system
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->pageRenderer->addJsFile('../t3lib/js/extjs/notifications.js');
		$this->pageRenderer->addInlineLanguageLabelFile('EXT:extensionmanager/Resources/Private/Language/locallang.xlf');
		$this->pageRenderer->addJsFile(\TYPO3\CMS\Core\Extension\ExtensionManager::extRelPath('lang') . 'res/js/be/typo3lang.js');
		$availableExtensions = $this->listUtility->getAvailableExtensions();
		$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensions($availableExtensions);
		$availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation($availableAndInstalledExtensions);
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
		$this->view->assign('extensions', $extensions)->assign('search', $search)->assign('availableAndInstalled', $availableAndInstalledExtensions);
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
		if ($this->request->hasArgument('allVersions') && $this->request->getArgument('allVersions') == 1 && $this->request->hasArgument('extensionKey') && is_string($this->request->getArgument('extensionKey'))) {
			$extensionKey = $this->request->getArgument('extensionKey');
			$extensions = $this->extensionRepository->findByExtensionKeyOrderedByVersion($extensionKey);
		} else {
			$this->redirect('ter');
		}
		$this->view->assign('extensions', $extensions)->assign('extensionKey', $extensionKey);
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
	 * @return \TYPO3\CMS\Backend\Template\DocumentTemplate $doc
	 */
	public function getDocInstance() {
		if (!isset($GLOBALS['SOBE']->doc)) {
			$GLOBALS['SOBE']->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
			$GLOBALS['SOBE']->doc->backPath = $GLOBALS['BACK_PATH'];
		}
		return $GLOBALS['SOBE']->doc;
	}

}


?>