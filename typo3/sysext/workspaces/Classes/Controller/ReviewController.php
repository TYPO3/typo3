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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
		$wsService = GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		$this->view->assign('showGrid', !($GLOBALS['BE_USER']->workspace === 0 && !$GLOBALS['BE_USER']->isAdmin()));
		$this->view->assign('showAllWorkspaceTab', TRUE);
		$this->view->assign('pageUid', GeneralUtility::_GP('id'));
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
			if (strlen(GeneralUtility::_GP('workspace'))) {
				$switchWs = (int)GeneralUtility::_GP('workspace');
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
		$this->pageRenderer->addInlineSetting('Workspaces', 'isLiveWorkspace', (int)$GLOBALS['BE_USER']->workspace === 0 ? TRUE : FALSE);
		$this->pageRenderer->addInlineSetting('Workspaces', 'workspaceTabs', $this->prepareWorkspaceTabs($wsList, $activeWorkspace));
		$this->pageRenderer->addInlineSetting('Workspaces', 'activeWorkspaceId', $activeWorkspace);
		$this->pageRenderer->addInlineSetting('Workspaces', 'PATH_typo3', GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . TYPO3_mainDir);
		$this->view->assign('performWorkspaceSwitch', $performWorkspaceSwitch);
		$this->view->assign('workspaceList', $wsList);
		$this->view->assign('activeWorkspaceUid', $activeWorkspace);
		$this->view->assign('activeWorkspaceTitle', \TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($activeWorkspace));
		$this->view->assign('showPreviewLink', $wsService->canCreatePreviewLink(GeneralUtility::_GP('id'), $activeWorkspace));
		$GLOBALS['BE_USER']->setAndSaveSessionData('tx_workspace_activeWorkspace', $activeWorkspace);
	}

	/**
	 * Renders the review module user dependent.
	 * The module will show all records of all workspaces.
	 *
	 * @return void
	 */
	public function fullIndexAction() {
		$wsService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		$wsList = $wsService->getAvailableWorkspaces();

		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$activeWorkspace = $GLOBALS['BE_USER']->workspace;
			$wsCur = array($activeWorkspace => TRUE);
			$wsList = array_intersect_key($wsList, $wsCur);
		}

		$this->pageRenderer->addInlineSetting('Workspaces', 'workspaceTabs', $this->prepareWorkspaceTabs($wsList, \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES));
		$this->pageRenderer->addInlineSetting('Workspaces', 'activeWorkspaceId', \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES);
		$this->pageRenderer->addInlineSetting('Workspaces', 'PATH_typo3', GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . TYPO3_mainDir);
		$this->view->assign('pageUid', GeneralUtility::_GP('id'));
		$this->view->assign('showGrid', TRUE);
		$this->view->assign('showLegend', TRUE);
		$this->view->assign('showAllWorkspaceTab', TRUE);
		$this->view->assign('workspaceList', $wsList);
		$this->view->assign('activeWorkspaceUid', \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES);
		$this->view->assign('showPreviewLink', FALSE);
		$GLOBALS['BE_USER']->setAndSaveSessionData('tx_workspace_activeWorkspace', \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES);
		// set flag for javascript
		$this->pageRenderer->addInlineSetting('Workspaces', 'allView', '1');
	}

	/**
	 * Renders the review module for a single page. This is used within the
	 * workspace-preview frame.
	 *
	 * @return void
	 */
	public function singleIndexAction() {
		$wsService = GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		$wsList = $wsService->getAvailableWorkspaces();
		$activeWorkspace = $GLOBALS['BE_USER']->workspace;
		$wsCur = array($activeWorkspace => TRUE);
		$wsList = array_intersect_key($wsList, $wsCur);
		$this->view->assign('pageUid', GeneralUtility::_GP('id'));
		$this->view->assign('showGrid', TRUE);
		$this->view->assign('showAllWorkspaceTab', FALSE);
		$this->view->assign('workspaceList', $wsList);
		$this->view->assign('backendDomain', GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
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
			$flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:warning.oldStyleWorkspaceInUser'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
			/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
			$flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
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
		$this->pageRenderer->addJsFile($this->backPath . 'sysext/backend/Resources/Public/JavaScript/flashmessages.js');
		$this->pageRenderer->addJsFile($this->backPath . 'js/extjs/ux/Ext.grid.RowExpander.js');
		$this->pageRenderer->addJsFile($this->backPath . 'js/extjs/ux/Ext.app.SearchField.js');
		$this->pageRenderer->addJsFile($this->backPath . 'js/extjs/ux/Ext.ux.FitToParent.js');
		$resourcePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/JavaScript/';

		// @todo Integrate additional stylesheet resources
		$this->pageRenderer->addCssFile($resourcePath . 'gridfilters/css/GridFilters.css');
		$this->pageRenderer->addCssFile($resourcePath . 'gridfilters/css/RangeMenu.css');

		$filters = array(
			$resourcePath. 'gridfilters/menu/RangeMenu.js',
			$resourcePath. 'gridfilters/menu/ListMenu.js',
			$resourcePath .'gridfilters/GridFilters.js',
			$resourcePath . 'gridfilters/filter/Filter.js',
			$resourcePath . 'gridfilters/filter/StringFilter.js',
			$resourcePath . 'gridfilters/filter/DateFilter.js',
			$resourcePath . 'gridfilters/filter/ListFilter.js',
			$resourcePath . 'gridfilters/filter/NumericFilter.js',
			$resourcePath . 'gridfilters/filter/BooleanFilter.js',
			$resourcePath . 'gridfilters/filter/BooleanFilter.js',
		);

		$custom = $this->getAdditionalResourceService()->getJavaScriptResources();

		$resources = array(
			$resourcePath . 'Component/RowDetailTemplate.js',
			$resourcePath . 'Component/RowExpander.js',
			$resourcePath . 'Component/TabPanel.js',
			$resourcePath . 'Store/mainstore.js',
			$resourcePath . 'configuration.js',
			$resourcePath . 'helpers.js',
			$resourcePath . 'actions.js',
			$resourcePath . 'component.js',
			$resourcePath . 'toolbar.js',
			$resourcePath . 'grid.js',
			$resourcePath . 'workspaces.js'
		);

		$javaScriptFiles = array_merge($filters, $custom, $resources);

		foreach ($javaScriptFiles as $javaScriptFile) {
			$this->pageRenderer->addJsFile($javaScriptFile);
		}
		$this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('record_history'));
	}

	/**
	 * Prepares available workspace tabs.
	 *
	 * @param array $workspaceList
	 * @param int $activeWorkspace
	 * @return array
	 */
	protected function prepareWorkspaceTabs(array $workspaceList, $activeWorkspace) {
		$tabs = array();

		if ($activeWorkspace !== \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES) {
			$tabs[] = array(
				'title' => $workspaceList[$activeWorkspace],
				'itemId' => 'workspace-' . $activeWorkspace,
				'workspaceId' => $activeWorkspace,
				'triggerUrl' => $this->getModuleUri($activeWorkspace),
			);
		}

		$tabs[] = array(
			'title' => 'All workspaces',
			'itemId' => 'workspace-' . \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES,
			'workspaceId' => \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES,
			'triggerUrl' => $this->getModuleUri(\TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES),
		);

		foreach ($workspaceList as $workspaceId => $workspaceTitle) {
			if ($workspaceId === $activeWorkspace) {
				continue;
			}
			$tabs[] = array(
				'title' => $workspaceTitle,
				'itemId' => 'workspace-' . $workspaceId,
				'workspaceId' => $workspaceId,
				'triggerUrl' => $this->getModuleUri($workspaceId),
			);
		}

		return $tabs;
	}

	/**
	 * Gets the module URI.
	 *
	 * @param int $workspaceId
	 * @return string
	 */
	protected function getModuleUri($workspaceId) {
		$parameters = array(
			'id' => (int)$this->pageId,
			'workspace' => (int)$workspaceId,
		);
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_WorkspacesWorkspaces', $parameters);
	}

}
