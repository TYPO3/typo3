<?php
namespace TYPO3\CMS\Recycler\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Julian Kleinhans <typo3@kj187.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

// This checks permissions and exits if the users has no permission for entry.
/**
 * Module 'Recycler' for the 'recycler' extension.
 */
class RecyclerModuleController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	protected $relativePath;

	protected $pageRecord = array();

	protected $isAccessibleForCurrentUser = FALSE;

	protected $allowDelete = FALSE;

	protected $recordsPageLimit = 50;

	/**
	 * Initializes the Module
	 *
	 * @return void
	 */
	public function initializeAction() {

		// Add state provider
		// $this->doc->setExtDirectStateProvider();
		$GLOBALS['TBE_TEMPLATE']->setExtDirectStateProvider();

		$this->pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$this->isAccessibleForCurrentUser = $this->id && is_array($this->pageRecord) || !$this->id && $this->isCurrentUserAdmin();

		//don't access in workspace
		if ($GLOBALS['BE_USER']->workspace !== 0) {
			$this->isAccessibleForCurrentUser = FALSE;
		}

		//read configuration
		$modTS = $GLOBALS['BE_USER']->getTSConfig('mod.recycler');
		if ($this->isCurrentUserAdmin()) {
			$this->allowDelete = TRUE;
		} else {
			$this->allowDelete = $modTS['properties']['allowDelete'] == '1';
		}

		if (isset($modTS['properties']['recordsPageLimit']) && intval($modTS['properties']['recordsPageLimit']) > 0) {
			$this->recordsPageLimit = intval($modTS['properties']['recordsPageLimit']);
		}
	}

	/**
	 * Renders the content of the module.
	 *
	 * @return void
	 */
	public function indexAction() {
		if (!$this->isAccessibleForCurrentUser) {
			$this->forward('noAccess');
		}

		// Load Ext JS:
		// $this->pageRenderer->enableExtJSQuickTips();

		// Integrate dynamic JavaScript such as configuration or lables:
        // $this->pageRenderer->addInlineSettingArray('Recycler', $this->getJavaScriptConfiguration());
		// $this->pageRenderer->addInlineLanguageLabelArray($this->getJavaScriptLabels());

		// Load Recycler JavaScript:
		// Load Plugins
		/*
			$uxPath = $this->doc->backpath . '../t3lib/js/extjs/ux/';
			$this->pageRenderer->addJsFile($uxPath . 'Ext.grid.RowExpander.js');
			$this->pageRenderer->addJsFile($uxPath . 'Ext.app.SearchField.js');
			$this->pageRenderer->addJsFile($uxPath . 'Ext.ux.FitToParent.js');
		*/
		// Load main script

		// $this->pageRecord
		// Renders the module page
		/*
			$content = $this->doc->render($GLOBALS['LANG']->getLL('title'), $content);
			$this->content = NULL;
			$this->doc = NULL;
			echo $content;

			<f:translate key="backendUserAdministration">Backend User Administration</f:translate>
		*/
		$this->view->assign('title', $GLOBALS['LANG']->getLL('title'));
		$this->view->assign('content', $this->content);
		$this->view->assign('description', $GLOBALS['LANG']->getLL('description'));
	}

	/**
	 * Render view for users without access
	 */
	public function noAccessAction() {

	}

	/**
	 * Determines whether the current user is admin.
	 *
	 * @return boolean Whether the current user is admin
	 */
	protected function isCurrentUserAdmin() {
		return (bool) $GLOBALS['BE_USER']->user['admin'];
	}

	/**
	 * Gets the JavaScript configuration for the Ext JS interface.
	 *
	 * @return array The JavaScript configuration
	 */
	protected function getJavaScriptConfiguration() {
		$configuration = array(
			'pagingSize' => $this->recordsPageLimit,
			'showDepthMenu' => 1,
			'startUid' => $this->id,
			'tableDefault' => 'pages',
			'renderTo' => 'recyclerContent',
			'isSSL' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL'),
			'ajaxController' => $this->doc->backPath . 'ajax.php?ajaxID=RecyclerAjaxController::init',
			'deleteDisable' => $this->allowDelete ? 0 : 1,
			'depthSelection' => $this->getDataFromSession('depthSelection', 0),
			'tableSelection' => $this->getDataFromSession('tableSelection', 'pages'),
			'States' => $GLOBALS['BE_USER']->uc['moduleData']['web_recycler']['States']
		);
		return $configuration;
	}

	/**
	 * Gets the labels to be used in JavaScript in the Ext JS interface.
	 *
	 * @return array The labels to be used in JavaScript
	 */
	protected function getJavaScriptLabels() {
		$coreLabels = array(
			'title' => $GLOBALS['LANG']->getLL('title'),
			'path' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.path'),
			'table' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.table'),
			'depth' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_perm.xlf:Depth'),
			'depth_0' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
			'depth_1' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
			'depth_2' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
			'depth_3' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
			'depth_4' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_4'),
			'depth_infi' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
		);
		$extensionLabels = $this->getJavaScriptLabelsFromLocallang('js.', 'label_');
		$javaScriptLabels = array_merge($coreLabels, $extensionLabels);
		return $javaScriptLabels;
	}

	/**
	 * Gets labels to be used in JavaScript fetched from the current locallang file.
	 *
	 * @param string $selectionPrefix: Prefix to select the correct labels (default: 'js.')
	 * @param string $stripFromSelectionName: Sub-prefix to be removed from label names in the result (default: '')
	 * @return array Lables to be used in JavaScript of the current locallang file
	 * @todo Check, whether this method can be moved in a generic way to $GLOBALS['LANG']
	 */
	protected function getJavaScriptLabelsFromLocallang($selectionPrefix = 'js.', $stripFromSelectionName = '') {
		$extraction = array();
		$labels = array_merge((array) $GLOBALS['LOCAL_LANG']['default'], (array) $GLOBALS['LOCAL_LANG'][$GLOBALS['LANG']->lang]);
		// Regular expression to strip the selection prefix and possibly something from the label name:
		$labelPattern = '#^' . preg_quote($selectionPrefix, '#') . '(' . preg_quote($stripFromSelectionName, '#') . ')?#';
		// Iterate through all locallang lables:
		foreach ($labels as $label => $value) {
			if (strpos($label, $selectionPrefix) === 0) {
				$key = preg_replace($labelPattern, '', $label);
				$extraction[$key] = $value;
			}
		}
		return $extraction;
	}

	/**
	 * Gets data from the session of the current backend user.
	 *
	 * @param string $identifier: The identifier to be used to get the data
	 * @param string $default: The default date to be used if nothing was found in the session
	 * @return string The accordant data in the session of the current backend user
	 */
	protected function getDataFromSession($identifier, $default = NULL) {
		$sessionData = &$GLOBALS['BE_USER']->uc['tx_recycler'];
		if (isset($sessionData[$identifier]) && $sessionData[$identifier]) {
			$data = $sessionData[$identifier];
		} else {
			$data = $default;
		}
		return $data;
	}

}

?>