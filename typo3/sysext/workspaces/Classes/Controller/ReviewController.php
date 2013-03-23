<?php
namespace TYPO3\CMS\Workspaces\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Ritter (steffen@typo3.org)
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
 * Review controller.
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class ReviewController extends \TYPO3\CMS\Workspaces\Controller\AbstractController {

	/**
	 * Renders the review module user dependent with all workspaces.
	 * The module will show all records of one workspace.
	 *
	 * @return void
	 */
	public function indexAction() {
		$wsService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		$this->view->assign('showGrid', !($GLOBALS['BE_USER']->workspace === 0 && !$GLOBALS['BE_USER']->isAdmin()));
		$this->view->assign('showAllWorkspaceTab', $GLOBALS['BE_USER']->isAdmin());
		$this->view->assign('pageUid', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
		$this->view->assign('showLegend', !($GLOBALS['BE_USER']->workspace === 0 && !$GLOBALS['BE_USER']->isAdmin()));
		$wsList = $wsService->getAvailableWorkspaces();
		$activeWorkspace = $GLOBALS['BE_USER']->workspace;
		$performWorkspaceSwitch = FALSE;
		// Only admins see multiple tabs, we decided to use it this
		// way for usability reasons. Regular users might be confused
		// by switching workspaces with the tabs in a module.
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$wsCur = array($activeWorkspace => TRUE);
			$wsList = array_intersect_key($wsList, $wsCur);
		} else {
			if (strlen(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('workspace'))) {
				$switchWs = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('workspace');
				if (in_array($switchWs, array_keys($wsList)) && $activeWorkspace != $switchWs) {
					$activeWorkspace = $switchWs;
					$GLOBALS['BE_USER']->setWorkspace($activeWorkspace);
					$performWorkspaceSwitch = TRUE;
					\TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal('updatePageTree');
				} elseif ($switchWs == \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES) {
					$this->redirect('fullIndex');
				}
			}
		}
		$this->pageRenderer->addInlineSetting('Workspaces', 'isLiveWorkspace', $GLOBALS['BE_USER']->workspace == 0 ? TRUE : FALSE);
		$this->view->assign('performWorkspaceSwitch', $performWorkspaceSwitch);
		$this->view->assign('workspaceList', $wsList);
		$this->view->assign('activeWorkspaceUid', $activeWorkspace);
		$this->view->assign('activeWorkspaceTitle', \TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($activeWorkspace));
		$this->view->assign('showPreviewLink', $wsService->canCreatePreviewLink(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), $activeWorkspace));
		$GLOBALS['BE_USER']->setAndSaveSessionData('tx_workspace_activeWorkspace', $activeWorkspace);
	}

	/**
	 * Renders the review module for admins.
	 * The module will show all records of all workspaces.
	 *
	 * @return void
	 */
	public function fullIndexAction() {
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$this->redirect('index');
		} else {
			$wsService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
			$this->view->assign('pageUid', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
			$this->view->assign('showGrid', TRUE);
			$this->view->assign('showLegend', TRUE);
			$this->view->assign('showAllWorkspaceTab', $GLOBALS['BE_USER']->isAdmin());
			$this->view->assign('workspaceList', $wsService->getAvailableWorkspaces());
			$this->view->assign('activeWorkspaceUid', \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES);
			$this->view->assign('showPreviewLink', FALSE);
			$GLOBALS['BE_USER']->setAndSaveSessionData('tx_workspace_activeWorkspace', \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES);
			// set flag for javascript
			$this->pageRenderer->addInlineSetting('Workspaces', 'allView', '1');
		}
	}

	/**
	 * Renders the review module for a single page. This is used within the
	 * workspace-preview frame.
	 *
	 * @return void
	 */
	public function singleIndexAction() {
		$wsService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		$wsList = $wsService->getAvailableWorkspaces();
		$activeWorkspace = $GLOBALS['BE_USER']->workspace;
		$wsCur = array($activeWorkspace => TRUE);
		$wsList = array_intersect_key($wsList, $wsCur);
		$this->view->assign('pageUid', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
		$this->view->assign('showGrid', TRUE);
		$this->view->assign('showAllWorkspaceTab', FALSE);
		$this->view->assign('workspaceList', $wsList);
		$this->view->assign('backendDomain', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
		$this->pageRenderer->addInlineSetting('Workspaces', 'singleView', '1');
	}

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * @return void
	 */
	protected function initializeAction() {
		parent::initializeAction();
		$this->template->setExtDirectStateProvider();
		if (\TYPO3\CMS\Workspaces\Service\WorkspaceService::isOldStyleWorkspaceUsed()) {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:warning.oldStyleWorkspaceInUser'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
			/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
			$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
			/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
			$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
			$defaultFlashMessageQueue->enqueue($flashMessage);
		}
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();
		$states = $GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['States'];
		$this->pageRenderer->addInlineSetting('Workspaces', 'States', $states);
		// Load  JavaScript:
		$this->pageRenderer->addExtDirectCode(array(
			'TYPO3.Workspaces'
		));
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/flashmessages.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.grid.RowExpander.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.app.SearchField.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.ux.FitToParent.js');
		$resourcePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/JavaScript/';
		$this->pageRenderer->addCssFile($resourcePath . 'gridfilters/css/GridFilters.css');
		$this->pageRenderer->addCssFile($resourcePath . 'gridfilters/css/RangeMenu.css');
		$jsFiles = array(
			'gridfilters/menu/RangeMenu.js',
			'gridfilters/menu/ListMenu.js',
			'gridfilters/GridFilters.js',
			'gridfilters/filter/Filter.js',
			'gridfilters/filter/StringFilter.js',
			'gridfilters/filter/DateFilter.js',
			'gridfilters/filter/ListFilter.js',
			'gridfilters/filter/NumericFilter.js',
			'gridfilters/filter/BooleanFilter.js',
			'gridfilters/filter/BooleanFilter.js',
			'Store/mainstore.js',
			'configuration.js',
			'helpers.js',
			'actions.js',
			'component.js',
			'toolbar.js',
			'grid.js',
			'workspaces.js'
		);
		foreach ($jsFiles as $jsFile) {
			$this->pageRenderer->addJsFile($resourcePath . $jsFile);
		}
	}

}


?>