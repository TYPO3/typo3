<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Ritter (steffen@typo3.org)
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

class Tx_Workspaces_Controller_ReviewController extends Tx_Workspaces_Controller_AbstractController {

	/**
	 * Renders the review module user dependent with all workspaces.
	 * The module will show all records of one workspace.
	 *
	 * @return void
	 */
	public function indexAction() {
		$wsService = t3lib_div::makeInstance('tx_Workspaces_Service_Workspaces');
		$this->view->assign('showGrid', !($GLOBALS['BE_USER']->workspace === 0 && !$GLOBALS['BE_USER']->isAdmin()));
		$this->view->assign('showAllWorkspaceTab', $GLOBALS['BE_USER']->isAdmin());
		$this->view->assign('pageUid', t3lib_div::_GP('id'));
		$this->view->assign('showLegend', !($GLOBALS['BE_USER']->workspace === 0 && !$GLOBALS['BE_USER']->isAdmin()));

		$wsList = $wsService->getAvailableWorkspaces();
		$activeWorkspace = $GLOBALS['BE_USER']->workspace;

		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$wsCur = array($activeWorkspace => true);
			$wsList = array_intersect_key($wsList, $wsCur);
		} else {
			$wsList = $wsService->getAvailableWorkspaces();
			if (strlen(t3lib_div::_GP('workspace'))) {
				$switchWs = (int) t3lib_div::_GP('workspace');
				if (in_array($switchWs, array_keys($wsList))) {
					$activeWorkspace = $switchWs;
				} elseif ($switchWs == tx_Workspaces_Service_Workspaces::SELECT_ALL_WORKSPACES) {
					$activeWorkspace = tx_Workspaces_Service_Workspaces::SELECT_ALL_WORKSPACES;
				}
			}
		}
		$this->view->assign('workspaceList', $wsList);
		$this->view->assign('activeWorkspaceUid', $activeWorkspace);
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
			$wsService = t3lib_div::makeInstance('tx_Workspaces_Service_Workspaces');
			$this->view->assign('pageUid', t3lib_div::_GP('id'));
			$this->view->assign('showGrid', true);
			$this->view->assign('showLegend', true);
			$this->view->assign('showAllWorkspaceTab', $GLOBALS['BE_USER']->isAdmin());
			$this->view->assign('workspaceList', $wsService->getAvailableWorkspaces());
			$this->view->assign('activeWorkspaceUid', tx_Workspaces_Service_Workspaces::SELECT_ALL_WORKSPACES);
			$GLOBALS['BE_USER']->setAndSaveSessionData('tx_workspace_activeWorkspace', tx_Workspaces_Service_Workspaces::SELECT_ALL_WORKSPACES);
		}
	}

	/**
	 * Renders the review module for a single page. This is used within the
	 * workspace-preview frame.
	 *
	 * @return void
	 */
	public function singleIndexAction() {

		$wsService = t3lib_div::makeInstance('tx_Workspaces_Service_Workspaces');
		$wsList = $wsService->getAvailableWorkspaces();
		$activeWorkspace = $GLOBALS['BE_USER']->workspace;

		$wsCur = array($activeWorkspace => true);
		$wsList = array_intersect_key($wsList, $wsCur);

		$this->view->assign('pageUid', t3lib_div::_GP('id'));
		$this->view->assign('showGrid', true);
		$this->view->assign('showAllWorkspaceTab', false);
		$this->view->assign('workspaceList', $wsList);
	}


	/**
	 * Preparation before the actual actions are fired, loads resources
	 * and initialized global objects like the pageRenderer
	 *
	 * @return void
	 */
	public function initializeAction() {
		parent::initializeAction();
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();
		$this->pageRenderer->enableExtJsDebug();

			// Load  JavaScript:
		$this->pageRenderer->addExtDirectCode();
		$this->pageRenderer->addJsFile($this->backPath . 'ajax.php?ajaxID=ExtDirect::getAPI&namespace=TYPO3.Workspaces', NULL, FALSE);

		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/flashmessages.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.grid.RowExpander.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.app.SearchField.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.ux.FitToParent.js');

		$resourcePath = t3lib_extMgm::extRelPath('workspaces') . 'Resources/Public/JavaScript/';

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

			'configuration.js',
			'helpers.js',
			'actions.js',
			'component.js',
			'toolbar.js',
			'grid.js',
			'workspaces.js',
		);

		foreach ($jsFiles as $jsFile) {
			$this->pageRenderer->addJsFile($resourcePath . $jsFile);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Controller/ReviewController.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Controller/ReviewController.php']);
}

?>