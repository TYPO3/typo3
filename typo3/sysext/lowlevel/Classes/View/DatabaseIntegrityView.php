<?php
namespace TYPO3\CMS\Lowlevel\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Script class for the DB int module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class DatabaseIntegrityView {

	/**
	 * @todo Define visibility
	 */
	public $MCONF = array();

	/**
	 * @todo Define visibility
	 */
	public $MOD_MENU = array();

	/**
	 * @todo Define visibility
	 */
	public $MOD_SETTINGS = array();

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * @todo Define visibility
	 */
	public $menu;

	protected $formName = 'queryform';

	/**
	 * Initialization
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		global $LANG, $BACK_PATH;
		$this->MCONF = $GLOBALS['MCONF'];
		$this->menuConfig();
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/dbint.html');
		$this->doc->form = '<form action="" method="post" name="' . $this->formName . '">';
		// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL) {
				window.location.href = URL;
			}
		</script>
		';
		$this->doc->table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist" style="width:400px!important;">
			<colgroup><col width="24"><col width="300"><col width="76"></colgroup>';
		$this->doc->tableLayout = array(
			'0' => array(
				'defCol' => array('<td class="t3-row-header"><img src="' . $this->doc->backPath . 'clear.gif" width="10" height="1" alt="" /></td><td valign="top" class="t3-row-header"><strong>', '</strong></td>')
			),
			'defRow' => array(
				'0' => array('<td valign="top">', '</td>'),
				'1' => array('<td valign="top">', '</td>'),
				'defCol' => array('<td><img src="' . $this->doc->backPath . 'clear.gif" width="15" height="1" alt="" /></td><td valign="top">', '</td>')
			)
		);
	}

	/**
	 * Configure menu
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function menuConfig() {
		global $LANG;
		// MENU-ITEMS:
		// If array, then it's a selector box menu
		// If empty string it's just a variable, that'll be saved.
		// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'function' => array(
				0 => $GLOBALS['LANG']->getLL('menu', TRUE),
				'records' => $GLOBALS['LANG']->getLL('recordStatistics', TRUE),
				'relations' => $GLOBALS['LANG']->getLL('databaseRelations', TRUE),
				'search' => $GLOBALS['LANG']->getLL('fullSearch', TRUE),
				'filesearch' => $GLOBALS['LANG']->getLL('findFilename', TRUE),
				'refindex' => $GLOBALS['LANG']->getLL('manageRefIndex', TRUE)
			),
			'search' => array(
				'raw' => $GLOBALS['LANG']->getLL('rawSearch', TRUE),
				'query' => $GLOBALS['LANG']->getLL('advancedQuery', TRUE)
			),
			'search_query_smallparts' => '',
			'search_result_labels' => '',
			'labels_noprefix' => '',
			'options_sortlabel' => '',
			'show_deleted' => '',
			'queryConfig' => '',
			// Current query
			'queryTable' => '',
			// Current table
			'queryFields' => '',
			// Current tableFields
			'queryLimit' => '',
			// Current limit
			'queryOrder' => '',
			// Current Order field
			'queryOrderDesc' => '',
			// Current Order field descending flag
			'queryOrder2' => '',
			// Current Order2 field
			'queryOrder2Desc' => '',
			// Current Order2 field descending flag
			'queryGroup' => '',
			// Current Group field
			'storeArray' => '',
			// Used to store the available Query config memory banks
			'storeQueryConfigs' => '',
			// Used to store the available Query configs in memory
			'search_query_makeQuery' => array(
				'all' => $GLOBALS['LANG']->getLL('selectRecords', TRUE),
				'count' => $GLOBALS['LANG']->getLL('countResults', TRUE),
				'explain' => $GLOBALS['LANG']->getLL('explainQuery', TRUE),
				'csv' => $GLOBALS['LANG']->getLL('csvExport', TRUE)
			),
			'sword' => ''
		);
		// CLEAN SETTINGS
		$OLD_MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, '', $this->MCONF['name'], 'ses');
		$this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->MCONF['name'], 'ses');
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('queryConfig')) {
			$qA = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('queryConfig');
			$this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, array('queryConfig' => serialize($qA)), $this->MCONF['name'], 'ses');
		}
		$addConditionCheck = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('qG_ins');
		foreach ($OLD_MOD_SETTINGS as $key => $val) {
			if (substr($key, 0, 5) == 'query' && $this->MOD_SETTINGS[$key] != $val && $key != 'queryLimit' && $key != 'use_listview') {
				$setLimitToStart = 1;
				if ($key == 'queryTable' && !$addConditionCheck) {
					$this->MOD_SETTINGS['queryConfig'] = '';
				}
			}
			if ($key == 'queryTable' && $this->MOD_SETTINGS[$key] != $val) {
				$this->MOD_SETTINGS['queryFields'] = '';
			}
		}
		if ($setLimitToStart) {
			$currentLimit = explode(',', $this->MOD_SETTINGS['queryLimit']);
			if ($currentLimit[1]) {
				$this->MOD_SETTINGS['queryLimit'] = '0,' . $currentLimit[1];
			} else {
				$this->MOD_SETTINGS['queryLimit'] = '0';
			}
			$this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, $this->MOD_SETTINGS, $this->MCONF['name'], 'ses');
		}
	}

	/**
	 * Main
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Content creation
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu']) {
			$this->menu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		}
		switch ($this->MOD_SETTINGS['function']) {
		case 'search':
			$this->func_search();
			break;
		case 'records':
			$this->func_records();
			break;
		case 'relations':
			$this->func_relations();
			break;
		case 'filesearch':
			$this->func_filesearch();
			break;
		case 'refindex':
			$this->func_refindex();
			break;
		default:
			$this->func_default();
			break;
		}
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => $this->getFuncMenu(),
			'CONTENT' => $this->content
		);
		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render($GLOBALS['LANG']->getLL('title'), $this->content);
	}

	/**
	 * Print content
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'shortcut' => ''
		);
		// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function,search,search_query_makeQuery', $this->MCONF['name']);
		}
		return $buttons;
	}

	/**
	 * Create the function menu
	 *
	 * @return string HTML of the function menu
	 */
	protected function getFuncMenu() {
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu']) {
			$funcMenu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		}
		return $funcMenu;
	}

	/**
	 * Creates the overview menu.
	 *
	 * @return void
	 */
	protected function func_default() {
		$availableModFuncs = array('records', 'relations', 'search', 'filesearch', 'refindex');
		$content = '<dl class="t3-overview-list">';
		foreach ($availableModFuncs as $modFunc) {
			$functionUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('tools_dbint') . '&SET[function]=' . $modFunc;
			$title = $GLOBALS['LANG']->getLL($modFunc);
			$description = $GLOBALS['LANG']->getLL($modFunc . '_description');
			$icon = '<img src="' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'MOD:tools_dbint/db.gif', '', 1) . '" width="16" height="16" title="' . $title . '" alt="' . $title . '" />';
			$content .= '
				<dt><a href="' . htmlspecialchars($functionUrl) . '">' . $icon . $title . '</a></dt>
				<dd>' . $description . '</dd>
			';
		}
		$content .= '</dl>';
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->section('', $content, FALSE, TRUE);
	}

	/****************************
	 *
	 * Functionality implementation
	 *
	 ****************************/
	/**
	 * Check and update reference index!
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function func_refindex() {
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('manageRefIndex', TRUE));
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_update') || \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_check')) {
			$testOnly = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_check') ? TRUE : FALSE;
			// Call the functionality
			$refIndexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
			list($headerContent, $bodyContent) = $refIndexObj->updateIndex($testOnly);
			// Output content:
			$this->content .= $this->doc->section('', str_replace(LF, '<br/>', $bodyContent), FALSE, TRUE);
		}
		// Output content:
		$content = '<p>' . $GLOBALS['LANG']->getLL('referenceIndex_description') . '</p><br />';
		$content .= '<input type="submit" name="_check" value="' . $GLOBALS['LANG']->getLL('referenceIndex_buttonCheck') . '" /> <input type="submit" name="_update" value="' . $GLOBALS['LANG']->getLL('referenceIndex_buttonUpdate') . '" /><br /><br />';
		$this->content .= $this->doc->section('', $content, FALSE, TRUE);
		// Command Line Interface
		$content = '';
		$content .= '<p>' . $GLOBALS['LANG']->getLL('checkScript') . '</p>';
		$content .= '<h4>' . $GLOBALS['LANG']->getLL('checkScript_check_description') . '</h4>' . '<code>php ' . PATH_typo3 . 'cli_dispatch.phpsh lowlevel_refindex -c</code><br />';
		$content .= '<h4>' . $GLOBALS['LANG']->getLL('checkScript_update_description') . '</h4>' . '<code>php ' . PATH_typo3 . 'cli_dispatch.phpsh lowlevel_refindex -e</code><br /><br />';
		$content .= '<div class="typo3-message message-information"><div class="message-body">' . $GLOBALS['LANG']->getLL('checkScript_information') . '</div></div>';
		$content .= '<p>' . $GLOBALS['LANG']->getLL('checkScript_moreDetails') . '<br /><a href="' . $GLOBALS['BACK_PATH'] . 'sysext/lowlevel/HOWTO_clean_up_TYPO3_installations.txt" target="_new">' . PATH_typo3 . 'sysext/lowlevel/HOWTO_clean_up_TYPO3_installations.txt</a></p>';
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('checkScript_headline'), $content, FALSE, TRUE);
	}

	/**
	 * Search (Full / Advanced)
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function func_search() {
		global $LANG;
		$fullsearch = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\QueryView');
		$fullsearch->setFormName($this->formName);
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('search'));
		$this->content .= $this->doc->spacer(5);
		$menu2 = '';
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu']) {
			$menu2 = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(0, 'SET[search]', $this->MOD_SETTINGS['search'], $this->MOD_MENU['search']);
		}
		if ($this->MOD_SETTINGS['search'] == 'query' && !$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu']) {
			$menu2 .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(0, 'SET[search_query_makeQuery]', $this->MOD_SETTINGS['search_query_makeQuery'], $this->MOD_MENU['search_query_makeQuery']) . '<br />';
		}
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopCheckboxes'] && $this->MOD_SETTINGS['search'] == 'query') {
			$menu2 .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[search_query_smallparts]', $this->MOD_SETTINGS['search_query_smallparts'], '', '', 'id="checkSearch_query_smallparts"') . '&nbsp;<label for="checkSearch_query_smallparts">' . $GLOBALS['LANG']->getLL('showSQL') . '</label><br />';
			$menu2 .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[search_result_labels]', $this->MOD_SETTINGS['search_result_labels'], '', '', 'id="checkSearch_result_labels"') . '&nbsp;<label for="checkSearch_result_labels">' . $GLOBALS['LANG']->getLL('useFormattedStrings') . '</label><br />';
			$menu2 .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[labels_noprefix]', $this->MOD_SETTINGS['labels_noprefix'], '', '', 'id="checkLabels_noprefix"') . '&nbsp;<label for="checkLabels_noprefix">' . $GLOBALS['LANG']->getLL('dontUseOrigValues') . '</label><br />';
			$menu2 .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[options_sortlabel]', $this->MOD_SETTINGS['options_sortlabel'], '', '', 'id="checkOptions_sortlabel"') . '&nbsp;<label for="checkOptions_sortlabel">' . $GLOBALS['LANG']->getLL('sortOptions') . '</label><br />';
			$menu2 .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[show_deleted]', $this->MOD_SETTINGS['show_deleted'], '', '', 'id="checkShow_deleted"') . '&nbsp;<label for="checkShow_deleted">' . $GLOBALS['LANG']->getLL('showDeleted') . '</label>';
		}
		$this->content .= $this->doc->section('', $menu2) . $this->doc->spacer(10);
		switch ($this->MOD_SETTINGS['search']) {
		case 'query':
			$this->content .= $fullsearch->queryMaker();
			break;
		case 'raw':

		default:
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('searchOptions'), $fullsearch->form(), FALSE, TRUE);
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('result'), $fullsearch->search(), FALSE, TRUE);
			break;
		}
	}

	/**
	 * Records overview
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function func_records() {
		/** @var $admin \TYPO3\CMS\Core\Integrity\DatabaseIntegrityCheck */
		$admin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Integrity\\DatabaseIntegrityCheck');
		$admin->genTree_makeHTML = 0;
		$admin->backPath = $GLOBALS['BACK_PATH'];
		$admin->genTree(0, '');
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('records'));
		// Pages stat
		$codeArr = array();
		$codeArr['tableheader'] = array('', $GLOBALS['LANG']->getLL('count'));
		$i++;
		$codeArr[$i][] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', array());
		$codeArr[$i][] = $GLOBALS['LANG']->getLL('total_pages');
		$codeArr[$i][] = count($admin->page_idArray);
		$i++;
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
			$codeArr[$i][] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', array('hidden' => 1));
			$codeArr[$i][] = $GLOBALS['LANG']->getLL('hidden_pages');
			$codeArr[$i][] = $admin->recStats['hidden'];
			$i++;
		}
		$codeArr[$i][] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', array('deleted' => 1));
		$codeArr[$i][] = $GLOBALS['LANG']->getLL('deleted_pages');
		$codeArr[$i][] = count($admin->recStats['deleted']['pages']);
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('pages'), $this->doc->table($codeArr), FALSE, TRUE);
		// Doktype
		$codeArr = array();
		$codeArr['tableheader'] = array($GLOBALS['LANG']->getLL('doktype_value'), $GLOBALS['LANG']->getLL('count'));
		$doktype = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];
		if (is_array($doktype)) {
			foreach ($doktype as $n => $setup) {
				if ($setup[1] != '--div--') {
					$codeArr[$n][] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', array('doktype' => $setup[1]));
					$codeArr[$n][] = $GLOBALS['LANG']->sL($setup[0]) . ' (' . $setup[1] . ')';
					$codeArr[$n][] = intval($admin->recStats['doktype'][$setup[1]]);
				}
			}
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('doktype'), $this->doc->table($codeArr), FALSE, TRUE);
		}
		// Tables and lost records
		$id_list = '-1,0,' . implode(',', array_keys($admin->page_idArray));
		$id_list = rtrim($id_list, ',');
		$admin->lostRecords($id_list);
		if ($admin->fixLostRecord(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('fixLostRecords_table'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('fixLostRecords_uid'))) {
			$admin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Integrity\\DatabaseIntegrityCheck');
			$admin->backPath = $BACK_PATH;
			$admin->genTree(0, '');
			$id_list = '-1,0,' . implode(',', array_keys($admin->page_idArray));
			$id_list = rtrim($id_list, ',');
			$admin->lostRecords($id_list);
		}
		$this->doc->table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist" style="width:700px!important;">';
		$codeArr = array();
		$codeArr['tableheader'] = array(
			$GLOBALS['LANG']->getLL('label'),
			$GLOBALS['LANG']->getLL('tablename'),
			$GLOBALS['LANG']->getLL('total_lost'),
			''
		);
		$countArr = $admin->countRecords($id_list);
		if (is_array($GLOBALS['TCA'])) {
			foreach ($GLOBALS['TCA'] as $t => $value) {
				if ($GLOBALS['TCA'][$t]['ctrl']['hideTable']) {
					continue;
				}
				$codeArr[$t][] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($t, array());
				$codeArr[$t][] = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$t]['ctrl']['title']);
				$codeArr[$t][] = $t;
				if ($t === 'pages' && $admin->lostPagesList !== '') {
					$lostRecordCount = count(explode(',', $admin->lostPagesList));
				} else {
					$lostRecordCount = count($admin->lRecords[$t]);
				}
				if ($countArr['all'][$t]) {
					$theNumberOfRe = intval($countArr['non_deleted'][$t]) . '/' . $lostRecordCount;
				} else {
					$theNumberOfRe = '';
				}
				$codeArr[$t][] = $theNumberOfRe;
				$lr = '';
				if (is_array($admin->lRecords[$t])) {
					foreach ($admin->lRecords[$t] as $data) {
						if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($admin->lostPagesList, $data[pid])) {
							$lr .= '<nobr><strong><a href="' . htmlspecialchars((\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('tools_dbint') . '&SET[function]=records&fixLostRecords_table=' . $t . '&fixLostRecords_uid=' . $data['uid'])) . '"><img src="' . $BACK_PATH . 'gfx/required_h.gif" width="10" hspace="3" height="10" border="0" align="top" title="' . $GLOBALS['LANG']->getLL('fixLostRecord') . '"></a>uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) . '</strong></nobr><br>';
						} else {
							$lr .= '<nobr><img src="' . $BACK_PATH . 'clear.gif" width="16" height="1" border="0"><font color="Gray">uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) . '</font></nobr><br>';
						}
					}
				}
				$codeArr[$t][] = $lr;
			}
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('tables'), $this->doc->table($codeArr), FALSE, TRUE);
		}
	}

	/**
	 * Show list references
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function func_relations() {
		global $LANG, $BACK_PATH;
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('relations'));
		$admin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Integrity\\DatabaseIntegrityCheck');
		$admin->genTree_makeHTML = 0;
		$admin->backPath = $BACK_PATH;
		$fkey_arrays = $admin->getGroupFields('');
		$admin->selectNonEmptyRecordsWithFkeys($fkey_arrays);
		$fileTest = $admin->testFileRefs();
		$code = '';
		if (is_array($fileTest['noReferences'])) {
			foreach ($fileTest['noReferences'] as $val) {
				$code .= '<nobr>' . $val[0] . '/<strong>' . $val[1] . '</strong></nobr><br>';
			}
		} else {
			$code = $GLOBALS['LANG']->getLL('no_files_found');
		}
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('files_no_ref'), $code, FALSE, TRUE);
		$code = '';
		if (is_array($fileTest['moreReferences'])) {
			foreach ($fileTest['moreReferences'] as $val) {
				$code .= '<nobr>' . $val[0] . '/<strong>' . $val[1] . '</strong>: ' . $val[2] . ' ' . $GLOBALS['LANG']->getLL('references') . '</nobr><br>' . $val[3] . '<br><br>';
			}
		} else {
			$code = $GLOBALS['LANG']->getLL('no_files_found');
		}
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('files_many_ref'), $code, FALSE, TRUE);
		$code = '';
		if (is_array($fileTest['noFile'])) {
			ksort($fileTest['noFile']);
			foreach ($fileTest['noFile'] as $val) {
				$code .= '<nobr>' . $val[0] . '/<strong>' . $val[1] . '</strong> ' . $GLOBALS['LANG']->getLL('isMissing') . ' </nobr><br>' . $GLOBALS['LANG']->getLL('referencedFrom') . $val[2] . '<br><br>';
			}
		} else {
			$code = $GLOBALS['LANG']->getLL('no_files_found');
		}
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('files_no_file'), $code, FALSE, TRUE);
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('select_db'), $admin->testDBRefs($admin->checkSelectDBRefs), FALSE, TRUE);
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('group_db'), $admin->testDBRefs($admin->checkGroupDBRefs), FALSE, TRUE);
	}

	/**
	 * Searching for files with a specific pattern
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function func_filesearch() {
		$pattern = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pattern');
		$pcontent = $GLOBALS['LANG']->getLL('enterRegexPattern') . ' <input type="text" name="pattern" value="' . htmlspecialchars(($pattern ? $pattern : $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'])) . '"> <input type="submit" name="' . $GLOBALS['LANG']->getLL('SearchButton') . '">';
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('findFilename'));
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('pattern'), $pcontent, FALSE, TRUE);
		if (strcmp($pattern, '')) {
			$dirs = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs(PATH_site);
			$lines = array();
			$depth = 10;
			foreach ($dirs as $key => $value) {
				$matching_files = array();
				$info = '';
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList('typo3,typo3conf,tslib,media,t3lib', $value)) {
					$info = $this->findFile(PATH_site . $value . '/', $pattern, $matching_files, $depth);
				}
				if (is_array($info)) {
					$lines[] = '<hr><strong>' . $value . '/</strong> ' . $GLOBALS['LANG']->getLL('beingChecked');
					$lines[] = $GLOBALS['LANG']->getLL('directories') . ' ' . $info[0];
					if ($info[2]) {
						$lines[] = '<span class="typo3-red">' . $GLOBALS['LANG']->getLL('directoriesTooDeep') . ' ' . $depth . '</span>';
					}
					$lines[] = $GLOBALS['LANG']->getLL('files') . ' ' . $info[1];
					$lines[] = $GLOBALS['LANG']->getLL('matchingFiles') . '<br><nobr><span class="typo3-red">' . implode('<br>', $matching_files) . '</span></nobr>';
				} else {
					$lines[] = $GLOBALS['TBE_TEMPLATE']->dfw('<hr><strong>' . $value . '/</strong> ' . $GLOBALS['LANG']->getLL('notChecked'));
				}
			}
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('searchingForFilenames'), implode('<br>', $lines), FALSE, TRUE);
		}
	}

	/**
	 * Searching for filename pattern recursively in the specified dir.
	 *
	 * @param string $basedir Base directory
	 * @param string $pattern Match pattern
	 * @param array $matching_files Array of matching files, passed by reference
	 * @param integer $depth Depth to recurse
	 * @return array Array with various information about the search result
	 * @see func_filesearch()
	 * @todo Define visibility
	 */
	public function findFile($basedir, $pattern, &$matching_files, $depth) {
		$files_searched = 0;
		$dirs_searched = 0;
		$dirs_error = 0;
		// Traverse files:
		$files = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir($basedir, '', 1);
		if (is_array($files)) {
			$files_searched += count($files);
			// Escape the regexp. Note: we cannot use preg_quote here because it will escape more than we need!
			$regExpPattern = str_replace('/', '\\/', $pattern);
			foreach ($files as $value) {
				if (preg_match('/' . $regExpPattern . '/i', basename($value))) {
					$matching_files[] = substr($value, strlen(PATH_site));
				}
			}
		}
		// Traverse subdirs
		if ($depth > 0) {
			$dirs = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs($basedir);
			if (is_array($dirs)) {
				$dirs_searched += count($dirs);
				foreach ($dirs as $value) {
					$inf = $this->findFile($basedir . $value . '/', $pattern, $matching_files, $depth - 1);
					$dirs_searched += $inf[0];
					$files_searched += $inf[1];
					$dirs_error = $inf[2];
				}
			}
		} else {
			$dirs = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs($basedir);
			if (is_array($dirs) && count($dirs)) {
				// Means error - there were further subdirs!
				$dirs_error = 1;
			}
		}
		return array($dirs_searched, $files_searched, $dirs_error);
	}

}


?>