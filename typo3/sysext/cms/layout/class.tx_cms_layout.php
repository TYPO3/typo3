<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Include file extending db_list.inc for use with the web_layout module
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  115: class tx_cms_layout extends recordList
 *
 *			  SECTION: Renderings
 *  180:	 function getTable($table,$id)
 *  240:	 function getTable_pages($id)
 *  378:	 function getTable_tt_content($id)
 *  754:	 function getTable_fe_users($id)
 *  780:	 function getTable_sys_note($id)
 *  873:	 function getTable_tt_board($id)
 *  955:	 function getTable_tt_address($id)
 *  985:	 function getTable_tt_links($id)
 * 1011:	 function getTable_tt_guest($id)
 * 1026:	 function getTable_tt_news($id)
 * 1047:	 function getTable_tt_calender($id)
 * 1097:	 function getTable_tt_products($id)
 *
 *			  SECTION: Generic listing of items
 * 1143:	 function makeOrdinaryList($table, $id, $fList, $icon=0, $addWhere='')
 * 1224:	 function dataFields($fieldArr,$table,$row,$out=array())
 * 1275:	 function headerFields($fieldArr,$table,$out=array())
 *
 *			  SECTION: Additional functions; Pages
 * 1317:	 function pages_getTree($theRows,$pid,$qWhere,$treeIcons,$depth)
 * 1350:	 function pages_drawItem($row,$fieldArr)
 *
 *			  SECTION: Additional functions; Content Elements
 * 1461:	 function tt_content_drawColHeader($colName,$editParams,$newParams)
 * 1513:	 function tt_content_drawHeader($row,$space=0,$disableMoveAndNewButtons=FALSE,$langMode=FALSE)
 * 1643:	 function tt_content_drawItem($row, $isRTE=FALSE)
 * 1806:	 function getNonTranslatedTTcontentUids($defLanguageCount,$id,$lP)
 * 1836:	 function newLanguageButton($defLanguageCount,$lP)
 * 1857:	 function infoGif($infoArr)
 * 1873:	 function newContentElementOnClick($id,$colPos,$sys_language)
 * 1891:	 function linkEditContent($str,$row)
 * 1909:	 function linkRTEbutton($row)
 * 1930:	 function languageSelector($id)
 * 1967:	 function getResult($result)
 *
 *			  SECTION: Additional functions; Message board items (tt_board)
 * 2036:	 function tt_board_getTree($theRows,$parent,$pid,$qWhere,$treeIcons)
 * 2071:	 function tt_board_drawItem($table,$row,$re)
 *
 *			  SECTION: Various helper functions
 * 2118:	 function numberOfRecords($table,$pid)
 * 2137:	 function renderText($input)
 * 2151:	 function getIcon($table,$row)
 * 2174:	 function getProcessedValue($table,$fieldList,$row,&$info)
 * 2194:	 function isDisabled($table,$row)
 * 2212:	 function wordWrapper($content,$max=50,$char=' -')
 * 2229:	 function noEditIcon($label='noEditItems')
 * 2238:	 function cleanTableNames()
 * 2274:	 function isRTEforField($table,$row,$field)
 * 2304:	 function getSpecConfForField($table,$row,$field)
 *
 *			  SECTION: External renderings
 * 2341:	 function getPageInfoBox($rec,$edit=0)
 * 2510:	 function getTableMenu($id)
 * 2575:	 function strip_tags($content, $fillEmptyContent=false)
 *
 * TOTAL FUNCTIONS: 43
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Child class for the Web > Page module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_cms_layout extends recordList {

	// External, static: For page statistics:
	var $stat_select_field = 'page_id'; // fieldname from sys_stat to select on.
	var $stat_codes = array(); // eg. 	"HITS_days:-1"

	// External, static: Flags of various kinds:
	var $pI_showUser = 0; // If true, users/groups are shown in the page info box.
	var $pI_showStat = 1; // If true, hit statistics are shown in the page info box.
	var $nextThree = 3; // The number of successive records to edit when showing content elements.
	var $pages_noEditColumns = 0; // If true, disables the edit-column icon for tt_content elements
	var $option_showBigButtons = 1; // If true, shows big buttons for editing page properties, moving, creating elements etc. in the columns view.
	var $option_newWizard = 1; // If true, new-wizards are linked to rather than the regular new-element list.
	var $ext_function = 0; // If set to "1", will link a big button to content element wizard.
	var $doEdit = 1; // If true, elements will have edit icons (probably this is whethere the user has permission to edit the page content). Set externally.
	var $agePrefixes = ' min| hrs| days| yrs'; // Age prefixes for displaying times. May be set externally to localized values.
	var $externalTables = array(); // Array of tables to be listed by the Web > Page module in addition to the default tables.
	var $descrTable; // "Pseudo" Description -table name
	var $defLangBinding = FALSE; // If set true, the language mode of tt_content elements will be rendered with hard binding between default language content elements and their translations!

	// External, static: Configuration of tt_content element display:
	var $tt_contentConfig = Array(
		'showInfo' => 1, // Boolean: Display info-marks or not
		'showCommands' => 1, // Boolean: Display up/down arrows and edit icons for tt_content records
		'single' => 1, // Boolean: If set, the content of column(s) $this->tt_contentConfig['showSingleCol'] is shown in the total width of the page
		'showAsGrid' => 0, // Boolean: If set, the content of columns is shown in grid
		'showSingleCol' => 0, // The column(s) to show if single mode (under each other)
		'languageCols' => 0,
		'languageMode' => 0,
		'languageColsPointer' => 0,
		'showHidden' => 1, // Displays hidden records as well
		'sys_language_uid' => 0, // Which language
		'cols' => '1,0,2,3' // The order of the rows: Default is left(1), Normal(0), right(2), margin(3)
	);

	// Internal, dynamic:
	var $allowedTableNames = array(); // Will contain a list of tables which can be listed by the user.
	var $activeTables = array(); // Contains icon/title of pages which are listed in the tables menu (see getTableMenu() function )
	var $tt_contentData = Array(
		'nextThree' => Array(),
		'prev' => Array(),
		'next' => Array()
	);
	var $CType_labels = array(); // Used to store labels for CTypes for tt_content elements
	var $itemLabels = array(); // Used to store labels for the various fields in tt_content elements


	/*****************************************
	 *
	 * Renderings
	 *
	 *****************************************/

	/**
	 * Adds the code of a single table
	 *
	 * @param	string		Table name
	 * @param	integer		Current page id
	 * @return	string		HTML for listing.
	 */
	function getTable($table, $id) {

		// Load full table definition:
		t3lib_div::loadTCA($table);

		if (isset($this->externalTables[$table])) {
			return $this->getExternalTables($id, $table);
		} else {
			// Branch out based on table name:
			// Notice: Most of these tables belongs to other extensions than 'cms'. Each of these tables can be rendered only if the extensions they belong to is loaded.
			switch ($table) {
				case 'pages':
					return $this->getTable_pages($id);
					break;
				case 'tt_content':
					return $this->getTable_tt_content($id);
					break;
				case 'fe_users':
					return $this->getTable_fe_users($id);
					break;
				case 'sys_note':
					return $this->getTable_sys_note($id);
					break;
				case 'tt_board':
					return $this->getTable_tt_board($id);
					break;
				case 'tt_address':
					return $this->getTable_tt_address($id);
					break;
				case 'tt_links':
					return $this->getTable_tt_links($id);
					break;
				case 'tt_guest':
					return $this->getTable_tt_guest($id);
					break;
				case 'tt_news':
					return $this->getTable_tt_news($id);
					break;
				case 'tt_calender':
					return $this->getTable_tt_calender($id);
					break;
				case 'tt_products':
					return $this->getTable_tt_products($id);
					break;
			}
		}
	}


	/**
	 * Renders an external table from page id
	 *
	 * @param	integer		Page id
	 * @param	string		name of the table
	 * @return	string		HTML for the listing
	 */
	function getExternalTables($id, $table) {

		$type = $GLOBALS['SOBE']->MOD_SETTINGS[$table];
		if (!isset($type)) {
			$type = 0;
		}

		$fList = $this->externalTables[$table][$type]['fList']; // eg. "name;title;email;company,image"
		// The columns are separeted by comma ','.
		// Values separated by semicolon ';' are shown in the same column.
		$icon = $this->externalTables[$table][$type]['icon'];
		$addWhere = $this->externalTables[$table][$type]['addWhere'];

		// Create listing
		$out = $this->makeOrdinaryList($table, $id, $fList, $icon, $addWhere);
		return $out;
	}


	/**
	 * Renders records from the pages table from page id
	 * (Used to get information about the page tree content by "Web>Info"!)
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_pages($id) {
		global $TCA;

		// Initializing:
		$out = '';
		$delClause = t3lib_BEfunc::deleteClause('pages') . ' AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1); // Select clause for pages:

		// Select current page:
		if (!$id) {
			$row = $GLOBALS['SOBE']->pageinfo; // The root has a pseudo record in pageinfo...
		} else {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid=' . intval($id) . $delClause);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			t3lib_BEfunc::workspaceOL('pages', $row);
		}

		// If there was found a page:
		if (is_array($row)) {

			// Select which fields to show:
			$pKey = $GLOBALS['SOBE']->MOD_SETTINGS['function'] == 'tx_cms_webinfo_hits' ? 'hits' : $GLOBALS['SOBE']->MOD_SETTINGS['pages'];
			switch ($pKey) {
				case 'hits':
					$this->fieldArray = explode(',', 'title,' . implode(',', $this->stat_codes));
					break;
				case 1:
					$this->cleanTableNames();
					$tableNames = $this->allowedTableNames;
					$this->fieldArray = explode(',', 'title,uid,' . implode(',', array_keys($tableNames)));
					break;
				case 2:
					$this->fieldArray = explode(',', 'title,uid,lastUpdated,newUntil,no_cache,cache_timeout,php_tree_stop,TSconfig,storage_pid,is_siteroot,fe_login_mode');
					break;
				default:
					$this->fieldArray = explode(',', 'title,uid,alias,starttime,endtime,fe_group,target,url,shortcut,shortcut_mode');
					break;
			}

			// Getting select-depth:
			$depth = intval($GLOBALS['SOBE']->MOD_SETTINGS['pages_levels']);

			// Half line is drawn
			$theData = Array();
			$theData['subject'] = $this->widthGif;
			$out .= $this->addelement(0, '', $theData);

			// Overriding a few things:
			$this->no_noWrap = 0;
			$this->oddColumnsCssClass = 'bgColor3-20';

			// Items
			$this->eCounter = $this->firstElementNumber;

			// Creating elements:
			list($flag, $code) = $this->fwd_rwd_nav();
			$out .= $code;
			$editUids = array();
			if ($flag) {

				// Getting children:
				$theRows = Array();
				$theRows = $this->pages_getTree($theRows, $row['uid'], $delClause . t3lib_BEfunc::versioningPlaceholderClause('pages'), '', $depth);
				if ($GLOBALS['BE_USER']->doesUserHaveAccess($row, 2)) {
					$editUids[] = $row['uid'];
				}
				$out .= $this->pages_drawItem($row, $this->fieldArray);

				// Traverse all pages selected:
				foreach ($theRows as $n => $sRow) {
					if ($GLOBALS['BE_USER']->doesUserHaveAccess($sRow, 2)) {
						$editUids[] = $sRow['uid'];
					}
					$out .= $this->pages_drawItem($sRow, $this->fieldArray);
				}
				$this->eCounter++;
			}

			// Header line is drawn
			$theData = Array();
			$editIdList = implode(',', $editUids);

			// Traverse fields (as set above) in order to create header values:
			foreach ($this->fieldArray as $field) {
				if ($editIdList && isset($TCA['pages']['columns'][$field]) && $field != 'uid' && !$this->pages_noEditColumns) {
					$params = '&edit[pages][' . $editIdList . ']=edit&columnsOnly=' . $field . '&disHelp=1';
					$iTitle = sprintf($GLOBALS['LANG']->getLL('editThisColumn'), rtrim(trim($GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('pages', $field))), ':'));
					$eI = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, '')) . '" title="' . htmlspecialchars($iTitle) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-open') .
							'</a>';
				} else {
					$eI = '';
				}
				switch ($field) {
					case 'title':
						$theData[$field] = '&nbsp;<strong>' . $GLOBALS['LANG']->sL($TCA['pages']['columns'][$field]['label']) . '</strong>' . $eI;
						break;
					case 'uid':
						$theData[$field] = '&nbsp;<strong>ID:</strong>';
						break;
					default:
						if (substr($field, 0, 6) == 'table_') {
							$f2 = substr($field, 6);
							if ($TCA[$f2]) {
								$theData[$field] = '&nbsp;' . t3lib_iconWorks::getSpriteIconForRecord($f2, array(), array('title' => $GLOBALS['LANG']->sL($TCA[$f2]['ctrl']['title'], 1)));
							}
						} elseif (substr($field, 0, 5) == 'HITS_') {
							$fParts = explode(':', substr($field, 5));
							switch ($fParts[0]) {
								case 'days':
									$timespan = mktime(0, 0, 0) + intval($fParts[1]) * 3600 * 24;
									$theData[$field] = '&nbsp;' . date('d', $timespan);
									break;
								default:
									$theData[$field] = '';
									break;
							}
						} else {
							$theData[$field] = '&nbsp;&nbsp;<strong>' . $GLOBALS['LANG']->sL($TCA['pages']['columns'][$field]['label'], 1) . '</strong>' . $eI;
						}
						break;
				}
			}

			// Start table:
			$this->oddColumnsCssClass = '';

			// CSH:
			$out = t3lib_BEfunc::cshItem($this->descrTable, 'func_' . $pKey, $GLOBALS['BACK_PATH']) .
					'
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-page-pages">
					' . $this->addelement(1, '', $theData, ' class="t3-row-header"', 20) .
					$out . '
				</table>';
		}
		$this->oddColumnsCssClass = '';
		return $out;
	}

	/**
	 * Returns the backend layout which should be used for this page.
	 *
	 * @param integer $id: Uid of the current page
	 * @return mixed Uid of the backend layout record or NULL if no layout should be used
	 */
	function getSelectedBackendLayoutUid($id) {
		$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('be_layout', 'pages', 'uid=' . $id);
		$backendLayoutUid = intval($page['be_layout']);
		if ($backendLayoutUid == -1) {
				// if it is set to "none" - don't use any
			$backendLayoutUid = NULL;
		} else if ($backendLayoutUid == 0) {
				// if it not set check the rootline for a layout on next level and use this
			$rootline = t3lib_BEfunc::BEgetRootLine($id);
			for ($i = count($rootline) - 2; $i > 0; $i--) {
				$backendLayoutUid = intval($rootline[$i]['be_layout_next_level']);
				if ($backendLayoutUid > 0) {
						// stop searching if a layout for "next level" is set
					break;
				} else if ($backendLayoutUid == -1){
						// if layout for "next level" is set to "none" - don't use any and stop searching
					$backendLayoutUid = NULL;
					break;
				}
			}
		}
			// if it is set to a positive value use this
		return $backendLayoutUid;
	}

	/**
	 * Renders Content Elements from the tt_content table from page id
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_tt_content($id) {
		global $TCA;

		$this->initializeLanguages();

		// Initialize:
		$RTE = $GLOBALS['BE_USER']->isRTE();
		$lMarg = 1;
		$showHidden = $this->tt_contentConfig['showHidden'] ? '' : t3lib_BEfunc::BEenableFields('tt_content');
		$pageTitleParamForAltDoc = '&recTitle=' . rawurlencode(t3lib_BEfunc::getRecordTitle('pages', t3lib_BEfunc::getRecordWSOL('pages', $id), TRUE));
		$GLOBALS['SOBE']->doc->getPageRenderer()->loadExtJs();
		$GLOBALS['SOBE']->doc->getPageRenderer()->addJsFile($GLOBALS['BACK_PATH'] . 'sysext/cms/layout/js/typo3pageModule.js');

		// Get labels for CTypes and tt_content element fields in general:
		$this->CType_labels = array();
		foreach ($TCA['tt_content']['columns']['CType']['config']['items'] as $val) {
			$this->CType_labels[$val[1]] = $GLOBALS['LANG']->sL($val[0]);
		}
		$this->itemLabels = array();
		foreach ($TCA['tt_content']['columns'] as $name => $val) {
			$this->itemLabels[$name] = $GLOBALS['LANG']->sL($val['label']);
		}

		// Select display mode:
		if (!$this->tt_contentConfig['single']) { // MULTIPLE column display mode, side by side:

			// Setting language list:
			$langList = $this->tt_contentConfig['sys_language_uid'];
			if ($this->tt_contentConfig['languageMode']) {
				if ($this->tt_contentConfig['languageColsPointer']) {
					$langList = '0,' . $this->tt_contentConfig['languageColsPointer'];
				} else {
					$langList = implode(',', array_keys($this->tt_contentConfig['languageCols']));
				}
				$languageColumn = array();
			}
			$langListArr = explode(',', $langList);
			$defLanguageCount = array();
			$defLangBinding = array();

			// For EACH languages... :
			foreach ($langListArr as $lP) { // If NOT languageMode, then we'll only be through this once.
				$showLanguage = $this->defLangBinding && $lP == 0 ? ' AND sys_language_uid IN (0,-1)' : ' AND sys_language_uid=' . $lP;
				$cList = explode(',', $this->tt_contentConfig['cols']);
				$content = array();
				$head = array();

				// For EACH column, render the content into a variable:
				foreach ($cList as $key) {
					if (!$lP) {
						$defLanguageCount[$key] = array();
					}

					// Select content elements from this column/language:
					$queryParts = $this->makeQueryArray('tt_content', $id, 'AND colPos=' . intval($key) . $showHidden . $showLanguage);
					$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);

					// If it turns out that there are not content elements in the column, then display a big button which links directly to the wizard script:
					if ($this->doEdit && $this->option_showBigButtons && !intval($key) && !$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
						$onClick = "window.location.href='db_new_content_el.php?id=" . $id . '&colPos=' . intval($key) . '&sys_language_uid=' . $lP . '&uid_pid=' . $id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . "';";
						$theNewButton = $GLOBALS['SOBE']->doc->t3Button($onClick, $GLOBALS['LANG']->getLL('newPageContent'));
						$content[$key] .= '<img src="clear.gif" width="1" height="5" alt="" /><br />' . $theNewButton;
					}

					// Traverse any selected elements and render their display code:
					$rowArr = $this->getResult($result);

					foreach ($rowArr as $rKey => $row) {

						if (is_array($row) && (int) $row['t3ver_state'] != 2) {
							$singleElementHTML = '';
							if (!$lP) {
								$defLanguageCount[$key][] = $row['uid'];
							}

							$editUidList .= $row['uid'] . ',';
							$singleElementHTML .= $this->tt_content_drawHeader($row, $this->tt_contentConfig['showInfo'] ? 15 : 5, $this->defLangBinding && $lP > 0, TRUE);

							$isRTE = $RTE && $this->isRTEforField('tt_content', $row, 'bodytext');
							$singleElementHTML .= '<div ' . ($row['_ORIG_uid'] ? ' class="ver-element"' : '') . '>' . $this->tt_content_drawItem($row, $isRTE) . '</div>';

							// NOTE: this is the end tag for <div class="t3-page-ce-body">
							// because of bad (historic) conception, starting tag has to be placed inside tt_content_drawHeader()
							$singleElementHTML .= '</div>';


							$statusHidden = ($this->isDisabled('tt_content', $row) ? ' t3-page-ce-hidden' : '');
							$singleElementHTML = '<div class="t3-page-ce' . $statusHidden . '">' . $singleElementHTML . '</div>';

							if ($this->defLangBinding && $this->tt_contentConfig['languageMode']) {
								$defLangBinding[$key][$lP][$row[($lP ? 'l18n_parent' : 'uid')]] = $singleElementHTML;
							} else {
								$content[$key] .= $singleElementHTML;
							}
						} else {
							unset($rowArr[$rKey]);
						}
					}

					// Add new-icon link, header:
					$newP = $this->newContentElementOnClick($id, $key, $lP);
					$colTitle = t3lib_BEfunc::getProcessedValue('tt_content', 'colPos', $key);

					$tcaItems = t3lib_div::callUserFunction('EXT:cms/classes/class.tx_cms_backendlayout.php:tx_cms_BackendLayout->getColPosListItemsParsed', $id, $this);
					foreach ($tcaItems as $item) {
						if ($item[1] == $key) {
							$colTitle = $GLOBALS['LANG']->sL($item[0]);
						}
					}
					$head[$key] .= $this->tt_content_drawColHeader($colTitle, ($this->doEdit && count($rowArr) ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc : ''), $newP);
					$editUidList = '';
				}

				// For EACH column, fit the rendered content into a table cell:
				$out = '';

				$backendLayoutUid = $this->getSelectedBackendLayoutUid($id);
				$backendLayoutRecord = t3lib_BEfunc::getRecord('be_layouts', intval($backendLayoutUid));
				$this->tt_contentConfig['showAsGrid'] = !empty($backendLayoutRecord['config']) && !$this->tt_contentConfig['languageMode'];

				if (!$this->tt_contentConfig['showAsGrid']) {
					foreach ($cList as $k => $key) {

						if (!$k) {
							$out .= '
								<td><img src="clear.gif" width="' . $lMarg . '" height="1" alt="" /></td>';
						} else {
							$out .= '
								<td><img src="clear.gif" width="4" height="1" alt="" /></td>
								<td bgcolor="#cfcfcf"><img src="clear.gif" width="1" height="1" alt="" /></td>
								<td><img src="clear.gif" width="4" height="1" alt="" /></td>';
						}
						$out .= '
								<td class="t3-page-column t3-page-column-' . $key . '">' . $head[$key] . $content[$key] . '</td>';

						// Storing content for use if languageMode is set:
						if ($this->tt_contentConfig['languageMode']) {
							$languageColumn[$key][$lP] = $head[$key] . $content[$key];
							if (!$this->defLangBinding) {
								$languageColumn[$key][$lP] .= '<br /><br />' . $this->newLanguageButton($this->getNonTranslatedTTcontentUids($defLanguageCount[$key], $id, $lP), $lP);
							}
						}
					}

					// Wrap the cells into a table row:
					$out = '
					<table border="0" cellpadding="0" cellspacing="0" class="t3-page-columns">
						<tr>' . $out . '
						</tr>
					</table>';

				} else {
					// GRID VIEW:

					// initialize TS parser to parse config to array
					$parser = t3lib_div::makeInstance('t3lib_TSparser');
					$parser->parse($backendLayoutRecord['config']);

					$grid .= '<div class="t3-gridContainer"><table border="0" cellspacing="1" cellpadding="4" width="80%" height="100%" class="t3-page-columns t3-gridTable">';

					// add colgroups
					$colCount = intval($parser->setup['be_layout.']['colCount']);
					$rowCount = intval($parser->setup['be_layout.']['rowCount']);

					$grid .= '<colgroup>';
					for ($i = 0; $i < $colCount; $i++) {
						$grid .= '<col style="width:' . (100 / $colCount) . '%"></col>';
					}
					$grid .= '</colgroup>';

					// cycle through rows
					for ($row = 1; $row <= $rowCount; $row++) {
						$rowConfig = $parser->setup['be_layout.']['rows.'][$row . '.'];
						if (!isset($rowConfig)) {
							continue;
						}

						$grid .= '<tr>';

						for ($col = 1; $col <= $colCount; $col++) {
							$columnConfig = $rowConfig['columns.'][$col . '.'];

							if (!isset($columnConfig)) {
								continue;
							}

							// which tt_content colPos should be displayed inside this cell
							$columnKey = intval($columnConfig['colPos']);

							// render the grid cell
							$grid .= '<td valign="top"' .
									(isset($columnConfig['colspan']) ? ' colspan="' . $columnConfig['colspan'] . '"' : '') .
									(isset($columnConfig['rowspan']) ? ' rowspan="' . $columnConfig['rowspan'] . '"' : '') .
									' class="t3-gridCell t3-page-column t3-page-column-' . $columnKey .
									(!isset($columnConfig['colPos']) ? ' t3-gridCell-unassigned' : '') .
									((isset($columnConfig['colPos']) && ! $head[$columnKey]) ? ' t3-gridCell-restricted' : '') .
									(isset($columnConfig['colspan']) ? ' t3-gridCell-width' . $columnConfig['colspan'] : '') .
									(isset($columnConfig['rowspan']) ? ' t3-gridCell-height' . $columnConfig['rowspan'] : '') . '">';

							// Draw the pre-generated header with edit and new buttons if a colPos is assigned.
							// If not, a new header without any buttons will be generated.
							if (isset($columnConfig['colPos']) && $head[$columnKey]) {
								$grid .= $head[$columnKey] . $content[$columnKey];
							} else if ($head[$columnKey]) {
								$grid .= $this->tt_content_drawColHeader($GLOBALS['LANG']->getLL('notAssigned'), '', '');
							} else {
								$grid .= $this->tt_content_drawColHeader($GLOBALS['LANG']->getLL('noAccess'), '', '');
							}

							$grid .= '</td>';
						}
						$grid .= '</tr>';
					}
					$out .= $grid . '</table></div>';
				}

				// CSH:
				$out .= t3lib_BEfunc::cshItem($this->descrTable, 'columns_multi', $GLOBALS['BACK_PATH']);
			}

			// If language mode, then make another presentation:
			// Notice that THIS presentation will override the value of $out! But it needs the code above to execute since $languageColumn is filled with content we need!
			if ($this->tt_contentConfig['languageMode']) {

				// Get language selector:
				$languageSelector = $this->languageSelector($id);

				// Reset out - we will make new content here:
				$out = '';
				// Separator between language columns (black thin line)
				$midSep = '
						<td><img src="clear.gif" width="4" height="1" alt="" /></td>
						<td bgcolor="black"><img src="clear.gif" width="1" height="1" alt="" /></td>
						<td><img src="clear.gif" width="4" height="1" alt="" /></td>';

				// Traverse languages found on the page and build up the table displaying them side by side:
				$cCont = array();
				$sCont = array();
				foreach ($langListArr as $lP) {

					// Header:
					$cCont[$lP] = '
						<td valign="top" align="center" class="bgColor6"><strong>' . htmlspecialchars($this->tt_contentConfig['languageCols'][$lP]) . '</strong></td>';

					// "View page" icon is added:
					$viewLink = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($this->id, $this->backPath, t3lib_BEfunc::BEgetRootLine($this->id), '', '', '&L=' . $lP)) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-view') .
							'</a>';

					// Language overlay page header:
					if ($lP) {

						list($lpRecord) = t3lib_BEfunc::getRecordsByField('pages_language_overlay', 'pid', $id, 'AND sys_language_uid=' . intval($lP));
						t3lib_BEfunc::workspaceOL('pages_language_overlay', $lpRecord);
						$params = '&edit[pages_language_overlay][' . $lpRecord['uid'] . ']=edit&overrideVals[pages_language_overlay][sys_language_uid]=' . $lP;
						$lPLabel = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon(t3lib_iconWorks::getSpriteIconForRecord('pages_language_overlay', $lpRecord), $lpRecord['uid']) .
								$viewLink .
								($GLOBALS['BE_USER']->check('tables_modify', 'pages_language_overlay') ? '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('edit', TRUE) . '">' .
										t3lib_iconWorks::getSpriteIcon('actions-document-open') .
										'</a>' : '') .
								htmlspecialchars(t3lib_div::fixed_lgd_cs($lpRecord['title'], 20));
					} else {
						$lPLabel = $viewLink;
					}
					$sCont[$lP] = '
						<td nowrap="nowrap">' . $lPLabel . '</td>';
				}
				// Add headers:
				$out .= '
					<tr class="bgColor5">' . implode($midSep, $cCont) . '
					</tr>';
				$out .= '
					<tr class="bgColor5">' . implode($midSep, $sCont) . '
					</tr>';

				// Traverse previously built content for the columns:
				foreach ($languageColumn as $cKey => $cCont) {
					$out .= '
					<tr>
						<td valign="top">' . implode('</td>' . $midSep . '
						<td valign="top">', $cCont) . '</td>
					</tr>';

					if ($this->defLangBinding) {
						// "defLangBinding" mode
						foreach ($defLanguageCount[$cKey] as $defUid) {
							$cCont = array();
							foreach ($langListArr as $lP) {
								$cCont[] = $defLangBinding[$cKey][$lP][$defUid] .
										'<br/>' . $this->newLanguageButton($this->getNonTranslatedTTcontentUids(array($defUid), $id, $lP), $lP);
							}
							$out .= '
							<tr>
								<td valign="top">' . implode('</td>' . $midSep . '
								<td valign="top">', $cCont) . '</td>
							</tr>';
						}

						// Create spacer:
						$cCont = array();
						foreach ($langListArr as $lP) {
							$cCont[] = '&nbsp;';
						}
						$out .= '
						<tr>
							<td valign="top">' . implode('</td>' . $midSep . '
							<td valign="top">', $cCont) . '</td>
						</tr>';
					}
				}

				// Finally, wrap it all in a table and add the language selector on top of it:
				$out = $languageSelector . '
					<table border="0" cellpadding="0" cellspacing="0" width="480" class="typo3-page-langMode">
						' . $out . '
					</table>';

				// CSH:
				$out .= t3lib_BEfunc::cshItem($this->descrTable, 'language_list', $GLOBALS['BACK_PATH']);
			}
		} else { // SINGLE column mode (columns shown beneath each other):
			#debug('single column');
			if ($this->tt_contentConfig['sys_language_uid'] == 0 || !$this->defLangBinding) {

				// Initialize:
				if ($this->defLangBinding && $this->tt_contentConfig['sys_language_uid'] == 0) {
					$showLanguage = ' AND sys_language_uid IN (0,-1)';
					$lP = 0;
				} else {
					$showLanguage = ' AND sys_language_uid=' . $this->tt_contentConfig['sys_language_uid'];
					$lP = $this->tt_contentConfig['sys_language_uid'];
				}

				$cList = explode(',', $this->tt_contentConfig['showSingleCol']);
				$content = array();
				$out = '';

				// Expand the table to some preset dimensions:
				$out .= '
					<tr>
						<td><img src="clear.gif" width="' . $lMarg . '" height="1" alt="" /></td>
						<td valign="top"><img src="clear.gif" width="150" height="1" alt="" /></td>
						<td><img src="clear.gif" width="10" height="1" alt="" /></td>
						<td valign="top"><img src="clear.gif" width="300" height="1" alt="" /></td>
					</tr>';

				// Traverse columns to display top-on-top
				foreach ($cList as $counter => $key) {

					// Select content elements:
					$queryParts = $this->makeQueryArray('tt_content', $id, 'AND colPos=' . intval($key) . $showHidden . $showLanguage);
					$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
					$c = 0;
					$rowArr = $this->getResult($result);
					$rowOut = '';

					// If it turns out that there are not content elements in the column, then display a big button which links directly to the wizard script:
					if ($this->doEdit && $this->option_showBigButtons && !intval($key) && !$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
						$onClick = "window.location.href='db_new_content_el.php?id=" . $id . '&colPos=' . intval($key) . '&sys_language_uid=' . $lP . '&uid_pid=' . $id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . "';";
						$theNewButton = $GLOBALS['SOBE']->doc->t3Button($onClick, $GLOBALS['LANG']->getLL('newPageContent'));
						$theNewButton = '<img src="clear.gif" width="1" height="5" alt="" /><br />' . $theNewButton;
					} else {
						$theNewButton = '';
					}

					// Traverse any selected elements:
					foreach ($rowArr as $rKey => $row) {

						if (is_array($row) && (int) $row['t3ver_state'] != 2) {

							$c++;
							$editUidList .= $row['uid'] . ',';
							$isRTE = $RTE && $this->isRTEforField('tt_content', $row, 'bodytext');

							// Create row output:
							$rowOut .= '
								<tr>
									<td></td>
									<td valign="top">' . $this->tt_content_drawHeader($row) . '</td>
									<td>&nbsp;</td>
									<td' . ($row['_ORIG_uid'] ? ' class="ver-element"' : '') . ' valign="top">' . $this->tt_content_drawItem($row, $isRTE) . '</td>
								</tr>';

							// If the element was not the last element, add a divider line:
							if ($c != $GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
								$rowOut .= '
								<tr>
									<td></td>
									<td colspan="3"><img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/stiblet_medium2.gif', 'width="468" height="1"') . ' class="c-divider" alt="" /></td>
								</tr>';
							}
						} else {
							unset($rowArr[$rKey]);
						}
					}

					// Add spacer between sections in the vertical list
					if ($counter) {
						$out .= '
							<tr>
								<td></td>
								<td colspan="3"><br /><br /><br /><br /></td>
							</tr>';
					}

					// Add section header:
					$newP = $this->newContentElementOnClick($id, $key, $this->tt_contentConfig['sys_language_uid']);
					$out .= '

						<!-- Column header: -->
						<tr>
							<td></td>
							<td valign="top" colspan="3">' .
							$this->tt_content_drawColHeader(t3lib_BEfunc::getProcessedValue('tt_content', 'colPos', $key), ($this->doEdit && count($rowArr) ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc : ''), $newP) .
							$theNewButton .
							'<br /></td>
						</tr>';

					// Finally, add the content from the records in this column:
					$out .= $rowOut;
				}

				// Finally, wrap all table rows in one, big table:
				$out = '
					<table border="0" cellpadding="0" cellspacing="0" width="400" class="typo3-page-columnsMode">
						' . $out . '
					</table>';

				// CSH:
				$out .= t3lib_BEfunc::cshItem($this->descrTable, 'columns_single', $GLOBALS['BACK_PATH']);
			} else {
				$out = '<br/><br/>' . $GLOBALS['SOBE']->doc->icons(1) . 'Sorry, you cannot view a single language in this localization mode (Default Language Binding is enabled)<br/><br/>';
			}
		}


		// Add the big buttons to page:
		if ($this->option_showBigButtons) {
			$bArray = array();

			if (!$GLOBALS['SOBE']->current_sys_language) {
				if ($this->ext_CALC_PERMS & 2) {
					$bArray[0] = $GLOBALS['SOBE']->doc->t3Button(t3lib_BEfunc::editOnClick('&edit[pages][' . $id . "]=edit", $this->backPath, ''), $GLOBALS['LANG']->getLL('editPageProperties'));
				}
			} else {
				if ($this->doEdit && $GLOBALS['BE_USER']->check('tables_modify', 'pages_language_overlay')) {
					list($languageOverlayRecord) = t3lib_BEfunc::getRecordsByField('pages_language_overlay', 'pid', $id, 'AND sys_language_uid=' . intval($GLOBALS['SOBE']->current_sys_language));
					$bArray[0] = $GLOBALS['SOBE']->doc->t3Button(t3lib_BEfunc::editOnClick('&edit[pages_language_overlay][' . $languageOverlayRecord['uid'] . "]=edit", $this->backPath, ''), $GLOBALS['LANG']->getLL('editPageProperties_curLang'));
				}
			}
			if ($this->ext_CALC_PERMS & 4 || $this->ext_CALC_PERMS & 2) {
				$bArray[1] = $GLOBALS['SOBE']->doc->t3Button("window.location.href='" . $this->backPath . "move_el.php?table=pages&uid=" . $id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . "';", $GLOBALS['LANG']->getLL('move_page'));
			}
			if ($this->ext_CALC_PERMS & 8) {
				$bArray[2] = $GLOBALS['SOBE']->doc->t3Button("window.location.href='" . $this->backPath . "db_new.php?id=" . $id . '&pagesOnly=1&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . "';", $GLOBALS['LANG']->getLL('newPage2'));
			}
			if ($this->doEdit && $this->ext_function == 1) {
				$bArray[3] = $GLOBALS['SOBE']->doc->t3Button("window.location.href='db_new_content_el.php?id=" . $id . '&sys_language_uid=' . $GLOBALS['SOBE']->current_sys_language . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . "';", $GLOBALS['LANG']->getLL('newPageContent2'));
			}
			$out = '
				<table border="0" cellpadding="4" cellspacing="0" class="typo3-page-buttons">
					<tr>
						<td>' . implode('</td>
						<td>', $bArray) . '</td>
						<td>' . t3lib_BEfunc::cshItem($this->descrTable, 'button_panel', $GLOBALS['BACK_PATH']) . '</td>
					</tr>
				</table>
				<br />
				' . $out;
		}

		// Return content:
		return $out;
	}

	/**
	 * Renders Frontend Users from the fe_users table from page id
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_fe_users($id) {

		$this->addElement_tdParams = array(
			'username' => ' nowrap="nowrap"',
			'usergroup' => ' nowrap="nowrap"',
			'name' => ' nowrap="nowrap"',
			'address' => ' nowrap="nowrap"',
			'zip' => ' nowrap="nowrap"',
			'city' => ' nowrap="nowrap"',
			'email' => ' nowrap="nowrap"',
			'telephone' => ' nowrap="nowrap"'
		);
		$fList = 'username,usergroup,name,email,telephone,address,zip,city';
		$out = $this->makeOrdinaryList('fe_users', $id, $fList, 1);
		$this->addElement_tdParams = array();
		return $out;
	}

	/**
	 * Renders records from the sys_notes table from page id
	 * NOTICE: Requires the sys_note extension to be loaded.
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_sys_note($id) {
		global $TCA;

		if (!t3lib_extMgm::isLoaded('sys_note')) {
			return '';
		}

		// INIT:
		$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$tree = $this->getTreeObject($id, intval($GLOBALS['SOBE']->MOD_SETTINGS['pages_levels']), $perms_clause);

		$this->itemLabels = array();
		foreach ($TCA['sys_note']['columns'] as $name => $val) {
			$this->itemLabels[$name] = $GLOBALS['LANG']->sL($val['label']);
		}

		// If page ids were found, select all sys_notes from the page ids:
		$out = '';
		if (count($tree->ids)) {
			$delClause = t3lib_BEfunc::deleteClause('sys_note') . t3lib_BEfunc::versioningPlaceholderClause('sys_note');
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_note', 'pid IN (' . implode(',', $tree->ids) . ') AND (personal=0 OR cruser=' . intval($GLOBALS['BE_USER']->user['uid']) . ')' . $delClause);
			$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);

			// If sys_notes were found, render them:
			if ($dbCount) {
				$this->fieldArray = explode(',', '__cmds__,info,note');

				// header line is drawn
				$theData = Array();
				$theData['__cmds__'] = '';
				$theData['info'] = '<strong>Info</strong><br /><img src="clear.gif" height="1" width="220" alt="" />';
				$theData['note'] = '<strong>Note</strong>';
				$out .= $this->addelement(1, '', $theData, ' class="t3-row-header"', 20);

				// half line is drawn
				$theData = Array();
				$theData['info'] = $this->widthGif;
				$out .= $this->addelement(0, '', $theData);

				$this->no_noWrap = 1;

				// Items
				$this->eCounter = $this->firstElementNumber;
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
					t3lib_BEfunc::workspaceOL('sys_note', $row);

					if (is_array($row)) {
						list($flag, $code) = $this->fwd_rwd_nav();
						$out .= $code;
						if ($flag) {
							$color = Array(
								0 => '', // No category
								1 => ' class="bgColor4"', // Instructions
								2 => ' class="bgColor2"', // Template
								3 => '', // Notes
								4 => ' class="bgColor5"' // To-do
							);
							$tdparams = $color[$row['category']];
							$info = Array();
							;
							$theData = Array();
							$this->getProcessedValue('sys_note', 'subject,category,author,email,personal', $row, $info);
							$cont = implode('<br />', $info);
							$head = '<strong>Page:</strong> ' . t3lib_BEfunc::getRecordPath($row['pid'], $perms_clause, 10) . '<br />';

							$theData['__cmds__'] = $this->getIcon('sys_note', $row);
							$theData['info'] = $head . $cont;
							$theData['note'] = nl2br($row['message']);

							$out .= $this->addelement(1, '', $theData, $tdparams, 20);


							// half line is drawn
							$theData = Array();
							$theData['info'] = $this->widthGif;
							$out .= $this->addelement(0, '', $theData);
						}
						$this->eCounter++;
					}
				}

				// Wrap it all in a table:
				$out = '
					<table border="0" cellpadding="1" cellspacing="2" width="480" class="typo3-page-sysnote">
						' . $out . '
					</table>';
			}
		}
		return $out;
	}

	/**
	 * Renders records from the tt_board table from page id
	 * NOTICE: Requires the tt_board extension to be loaded.
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_tt_board($id) {

		// Initialize:
		$delClause = t3lib_BEfunc::deleteClause('tt_board') . t3lib_BEfunc::versioningPlaceholderClause('tt_board');
		$queryParts = $this->makeQueryArray('tt_board', $id, 'AND parent=0');
		$this->setTotalItems($queryParts);
		$dbCount = 0;

		// If items were selected, make query:
		if ($this->totalItems) {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
			$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		}

		// If results came out of that, render the list:
		$out = '';
		if ($dbCount) {

			// Setting fields to display first:
			if ($GLOBALS['SOBE']->MOD_SETTINGS['tt_board'] == 'expand') {
				$this->fieldArray = explode(',', 'subject,author,date,age');
			} else {
				$this->fieldArray = explode(',', 'subject,author,date,age,replys');
			}

			// Header line is drawn
			$theData = Array();
			$theData['subject'] = '<strong>' . $GLOBALS['LANG']->getLL('tt_board_subject', 1) . '</strong>';
			$theData['author'] = '<strong>' . $GLOBALS['LANG']->getLL('tt_board_author', 1) . '</strong>';
			$theData['date'] = '<strong>' . $GLOBALS['LANG']->getLL('tt_board_date', 1) . '</strong>';
			$theData['age'] = '<strong>' . $GLOBALS['LANG']->getLL('tt_board_age', 1) . '</strong>';
			if ($GLOBALS['SOBE']->MOD_SETTINGS['tt_board'] != 'expand') {
				$theData['replys'] = '<strong>' . $GLOBALS['LANG']->getLL('tt_board_RE', 1) . '</strong>';
			}
			$out .= $this->addelement(1, '', $theData, ' class="t3-row-header"', 20);

			// half line is drawn
			$theData = Array();
			$theData['subject'] = $this->widthGif;
			$out .= $this->addelement(0, '', $theData);

			// Items
			$this->eCounter = $this->firstElementNumber;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				t3lib_BEfunc::workspaceOL('tt_board', $row);

				if (is_array($row)) {
					list($flag, $code) = $this->fwd_rwd_nav();
					$out .= $code;

					if ($flag) {

						$theRows = Array();
						$theRows = $this->tt_board_getTree($theRows, $row['uid'], $id, $delClause, '');
						$out .= $this->tt_board_drawItem('tt_board', $row, count($theRows));

						if ($GLOBALS['SOBE']->MOD_SETTINGS['tt_board'] == 'expand') {
							foreach ($theRows as $n => $sRow) {
								$out .= $this->tt_board_drawItem('tt_board', $sRow, 0);
							}
						}
					}
					$this->eCounter++;
				}
			}

			// Wrap it all in a table:
			$out = '
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-page-listTTboard">
					' . $out . '
				</table>';
		}

		return $out;
	}

	/**
	 * Renders address records from the tt_address table from page id
	 * NOTICE: Requires the tt_address extension to be loaded.
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_tt_address($id) {

		// Define fieldlist to show:
		switch ($GLOBALS['SOBE']->MOD_SETTINGS['tt_address']) {
			case 1:
				$icon = 0;
				$fList = 'name,address,zip,city,country';
				break;
			case 2:
				$icon = 1;
				$fList = 'name;title;email;company,image';
				break;
			default:
				$icon = 0;
				$fList = 'name,email,www,phone,fax,mobile';
				break;
		}

		// Create listing
		$out = $this->makeOrdinaryList('tt_address', $id, $fList, $icon);
		return $out;
	}

	/**
	 * Renders link records from the tt_links table from page id
	 * NOTICE: Requires the tt_links extension to be loaded.
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_tt_links($id) {

		// Define fieldlist to show:
		switch ($GLOBALS['SOBE']->MOD_SETTINGS['tt_links']) {
			case 1:
				$fList = 'title,hidden,url';
				break;
			case 2:
				$fList = 'title;url,note2';
				break;
			default:
				$fList = 'title;url,note';
				break;
		}

		$out = $this->makeOrdinaryList('tt_links', $id, $fList, 1);
		return $out;
	}

	/**
	 * Renders link records from the tt_links table from page id
	 * NOTICE: Requires the tt_links extension to be loaded.
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_tt_guest($id) {

		// Define fieldlist to show:
		$fList = 'title;cr_name;cr_email,note';
		$out = $this->makeOrdinaryList('tt_guest', $id, $fList, 1);
		return $out;
	}

	/**
	 * Renders news items from the tt_news table from page id
	 * NOTICE: Requires the tt_news extension to be loaded.
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_tt_news($id) {

		$this->addElement_tdParams = array(
			'title' => ' nowrap="nowrap"',
			'datetime' => ' nowrap="nowrap"',
			'starttime' => ' nowrap="nowrap"',
			'author' => ' nowrap="nowrap"'
		);
		$fList = 'title,author,author_email,datetime,starttime,category,image';
		$out = $this->makeOrdinaryList('tt_news', $id, $fList, 1);
		$this->addElement_tdParams = array();
		return $out;
	}

	/**
	 * Renders calender elements link records from the tt_calender table from page id
	 * NOTICE: Requires the tt_calender extension to be loaded.
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_tt_calender($id) {

		$type = $GLOBALS['SOBE']->MOD_SETTINGS['tt_calender'];
		switch ($type) {
			case 'date':
				// Date default
				$fList = 'date,title';
				$icon = 0;
				$out = $this->makeOrdinaryList('tt_calender', $id, $fList, $icon, ' AND type=0');
				return $out;
				break;
			case 'date_ext':
				// Date extended
				$fList = 'title;date;time;datetext;link,note';
				$icon = 1;
				$out = $this->makeOrdinaryList('tt_calender', $id, $fList, $icon, ' AND type=0');
				return $out;
				break;
			case 'todo':
				// Todo default
				$fList = 'title,complete,priority,date';
				$icon = 0;
				$out = $this->makeOrdinaryList('tt_calender', $id, $fList, $icon, ' AND type=1');
				return $out;
				break;
			case 'todo_ext':
				// Todo extended
				$fList = 'title;complete;priority;date;workgroup;responsible;category,note';
				$icon = 1;
				$out = $this->makeOrdinaryList('tt_calender', $id, $fList, $icon, ' AND type=1');
				return $out;
				break;
			default:
				// Overview, both todo and calender
				$fList = 'title,date,time,week';
				$icon = 1;
				$out = $this->makeOrdinaryList('tt_calender', $id, $fList, $icon, ' AND type=0');
				$out .= $this->makeOrdinaryList('tt_calender', $id, $fList, $icon, ' AND type=1');
				return $out;
				break;
		}
	}

	/**
	 * Renders shopping elements from the tt_products table from page id
	 * NOTICE: Requires the tt_products extension to be loaded.
	 *
	 * @param	integer		Page id
	 * @return	string		HTML for the listing
	 */
	function getTable_tt_products($id) {

		$type = $GLOBALS['SOBE']->MOD_SETTINGS['tt_products'];
		switch ($type) {
			case 'ext':
				$fList = 'title;itemnumber;price;price2;inStock;category,image,note';
				$icon = 1;
				$out = $this->makeOrdinaryList('tt_products', $id, $fList, $icon);
				break;
			default:
				$fList = 'title,itemnumber,price,category,image';
				$icon = 1;
				$out = $this->makeOrdinaryList('tt_products', $id, $fList, $icon);
				break;
		}

		return $out;
	}


	/**********************************
	 *
	 * Generic listing of items
	 *
	 **********************************/

	/**
	 * Creates a standard list of elements from a table.
	 *
	 * @param	string		Table name
	 * @param	integer		Page id.
	 * @param	string		Comma list of fields to display
	 * @param	boolean		If true, icon is shown
	 * @param	string		Additional WHERE-clauses.
	 * @return	string		HTML table
	 */
	function makeOrdinaryList($table, $id, $fList, $icon = 0, $addWhere = '') {
		global $TCA;

		// Initialize:
		$out = '';
		$queryParts = $this->makeQueryArray($table, $id, $addWhere);
		$this->setTotalItems($queryParts);
		$dbCount = 0;

		// Make query for records if there were any records found in the count operation:
		if ($this->totalItems) {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
			$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		}

		// If records were found, render the list:
		$out = '';
		if ($dbCount) {

			// Set fields
			$this->fieldArray = t3lib_div::trimExplode(',', '__cmds__,' . $fList, TRUE);

			// Header line is drawn
			$theData = array();
			$theData = $this->headerFields($this->fieldArray, $table, $theData);
			if ($this->doEdit) {
				$theData['__cmds__'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[' . $table . '][' . $this->id . ']=new', $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('new', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-new') .
						'</a>';
			}
			$out .= $this->addelement(1, '', $theData, ' class="c-headLine"', 15);

			// Render Items
			$this->eCounter = $this->firstElementNumber;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				t3lib_BEfunc::workspaceOL($table, $row);

				if (is_array($row)) {
					list($flag, $code) = $this->fwd_rwd_nav();
					$out .= $code;
					if ($flag) {
						$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
						$Nrow = array();

						// Setting icons/edit links:
						if ($icon) {
							$Nrow['__cmds__'] = $this->getIcon($table, $row);
						}
						if ($this->doEdit) {
							$Nrow['__cmds__'] .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('edit', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-document-open') .
									'</a>';
						} else {
							$Nrow['__cmds__'] .= $this->noEditIcon();
						}

						// Get values:
						$Nrow = $this->dataFields($this->fieldArray, $table, $row, $Nrow);
						$tdparams = $this->eCounter % 2 ? ' class="bgColor4"' : ' class="bgColor4-20"';
						$out .= $this->addelement(1, '', $Nrow, $tdparams);
					}
					$this->eCounter++;
				}
			}

			// Wrap it all in a table:
			$out = '

				<!--
					STANDARD LIST OF "' . $table . '"
				-->
				<table border="0" cellpadding="1" cellspacing="2" width="480" class="typo3-page-stdlist">
					' . $out . '
				</table>';
		}
		return $out;
	}

	/**
	 * Adds content to all data fields in $out array
	 *
	 * @param	array		Array of fields to display. Each field name has a special feature which is that the field name can be specified as more field names. Eg. "field1,field2;field3". Field 2 and 3 will be shown in the same cell of the table separated by <br /> while field1 will have its own cell.
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	array		Array to which the data is added
	 * @return	array		$out array returned after processing.
	 * @see makeOrdinaryList()
	 */
	function dataFields($fieldArr, $table, $row, $out = array()) {
		global $TCA;

		// Check table validity:
		if ($TCA[$table]) {
			t3lib_div::loadTCA($table);
			$thumbsCol = $TCA[$table]['ctrl']['thumbnail'];

			// Traverse fields:
			foreach ($fieldArr as $fieldName) {

				if ($TCA[$table]['columns'][$fieldName]) { // Each field has its own cell (if configured in TCA)
					if ($fieldName == $thumbsCol) { // If the column is a thumbnail column:
						$out[$fieldName] = $this->thumbCode($row, $table, $fieldName);
					} else { // ... otherwise just render the output:
						$out[$fieldName] = nl2br(htmlspecialchars(trim(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getProcessedValue($table, $fieldName, $row[$fieldName], 0, 0, 0, $row['uid']), 250))));
					}
				} else { // Each field is separated by <br /> and shown in the same cell (If not a TCA field, then explode the field name with ";" and check each value there as a TCA configured field)
					$theFields = explode(';', $fieldName);

					// Traverse fields, separated by ";" (displayed in a single cell).
					foreach ($theFields as $fName2) {
						if ($TCA[$table]['columns'][$fName2]) {
							$out[$fieldName] .= '<strong>' . $GLOBALS['LANG']->sL($TCA[$table]['columns'][$fName2]['label'], 1) . '</strong>' .
									'&nbsp;&nbsp;' .
									htmlspecialchars(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getProcessedValue($table, $fName2, $row[$fName2], 0, 0, 0, $row['uid']), 25)) .
									'<br />';
						}
					}
				}
				// If no value, add a nbsp.
				if (!$out[$fieldName]) {
					$out[$fieldName] = '&nbsp;';
				}

				// Wrap in dimmed-span tags if record is "disabled"
				if ($this->isDisabled($table, $row)) {
					$out[$fieldName] = $GLOBALS['TBE_TEMPLATE']->dfw($out[$fieldName]);
				}
			}
		}
		return $out;
	}

	/**
	 * Header fields made for the listing of records
	 *
	 * @param	array		Field names
	 * @param	string		The table name
	 * @param	array		Array to which the headers are added.
	 * @return	array		$out returned after addition of the header fields.
	 * @see makeOrdinaryList()
	 */
	function headerFields($fieldArr, $table, $out = array()) {
		global $TCA;

		t3lib_div::loadTCA($table);

		foreach ($fieldArr as $fieldName) {
			$ll = $GLOBALS['LANG']->sL($TCA[$table]['columns'][$fieldName]['label'], 1);
			$out[$fieldName] = '<strong>' . ($ll ? $ll : '&nbsp;') . '</strong>';
		}
		return $out;
	}


	/**********************************
	 *
	 * Additional functions; Pages
	 *
	 **********************************/

	/**
	 * Adds pages-rows to an array, selecting recursively in the page tree.
	 *
	 * @param	array		Array which will accumulate page rows
	 * @param	integer		Pid to select from
	 * @param	string		Query-where clause
	 * @param	string		Prefixed icon code.
	 * @param	integer		Depth (decreasing)
	 * @return	array		$theRows, but with added rows.
	 */
	function pages_getTree($theRows, $pid, $qWhere, $treeIcons, $depth) {
		$depth--;
		if ($depth >= 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'pid=' . intval($pid) . $qWhere, '', 'sorting');
			$c = 0;
			$rc = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				t3lib_BEfunc::workspaceOL('pages', $row);
				if (is_array($row)) {
					$c++;
					$row['treeIcons'] = $treeIcons . '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/join' . ($rc == $c ? 'bottom' : '') . '.gif', 'width="18" height="16"') . ' alt="" />';
					$theRows[] = $row;

					// Get the branch
					$spaceOutIcons = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' . ($rc == $c ? 'blank.gif' : 'line.gif'), 'width="18" height="16"') . ' alt="" />';
					$theRows = $this->pages_getTree($theRows, $row['uid'], $qWhere, $treeIcons . $spaceOutIcons, $row['php_tree_stop'] ? 0 : $depth);
				}
			}
		} else {
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'pages', 'pid=' . intval($pid) . $qWhere);
			if ($count) {
				$this->plusPages[$pid] = $count;
			}
		}
		return $theRows;
	}

	/**
	 * Adds a list item for the pages-rendering
	 *
	 * @param	array		Record array
	 * @param	array		Field list
	 * @return	string		HTML for the item
	 */
	function pages_drawItem($row, $fieldArr) {
		global $TCA;

		// Initialization
		$theIcon = $this->getIcon('pages', $row);

		// 	Preparing and getting the data-array
		$theData = Array();
		foreach ($fieldArr as $field) {
			switch ($field) {
				case 'title':
					$red = $this->plusPages[$row['uid']] ? '<font color="red"><strong>+&nbsp;</strong></font>' : '';
					$pTitle = htmlspecialchars(t3lib_BEfunc::getProcessedValue('pages', $field, $row[$field], 20));
					if ($red) {
						$pTitle = '<a href="' . htmlspecialchars($this->script . '?id=' . $row['uid']) . '">' . $pTitle . '</a>';
					}
					$theData[$field] = $row['treeIcons'] . $theIcon . $red . $pTitle . '&nbsp;&nbsp;';
					break;
				case 'php_tree_stop':
				case 'TSconfig':
					$theData[$field] = $row[$field] ? '&nbsp;<strong>x</strong>' : '&nbsp;';
					break;
				case 'uid':
					if ($GLOBALS['BE_USER']->doesUserHaveAccess($row, 2)) {
						$params = '&edit[pages][' . $row['uid'] . ']=edit';
						$eI = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, '')) . '" title="' . $GLOBALS['LANG']->getLL('editThisPage', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-open') .
								'</a>';
					} else {
						$eI = '';
					}
					$theData[$field] = '<span align="right">' . $row['uid'] . $eI . '</span>';
					break;
				default:
					if (substr($field, 0, 6) == 'table_') {
						$f2 = substr($field, 6);
						if ($TCA[$f2]) {
							$c = $this->numberOfRecords($f2, $row['uid']);
							$theData[$field] = '&nbsp;&nbsp;' . ($c ? $c : '');
						}
					} elseif (substr($field, 0, 5) == 'HITS_') {
						if (t3lib_extMgm::isLoaded('sys_stat')) {
							$fParts = explode(':', substr($field, 5));
							switch ($fParts[0]) {
								case 'days':
									$timespan = mktime(0, 0, 0) + intval($fParts[1]) * 3600 * 24;
									// Page hits
									$number = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
										'*',
										'sys_stat',
											$this->stat_select_field . '=' . intval($row['uid']) .
													' AND tstamp >=' . intval($timespan) .
													' AND tstamp <' . intval($timespan + 3600 * 24)
									);
									if ($number) {
										// Sessions
										$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
											'count(*)',
											'sys_stat',
												$this->stat_select_field . '=' . intval($row['uid']) . '
															AND tstamp>=' . intval($timespan) . '
															AND tstamp<' . intval($timespan + 3600 * 24) . '
															AND surecookie!=""',
											'surecookie'
										);
										$scnumber = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

										$number .= '/' . $scnumber;
									} else {
										$number = '';
									}
									break;
							}
							$theData[$field] = '&nbsp;' . $number;
						} else {
							$theData[$field] = '&nbsp;';
						}
					} else {
						$theData[$field] = '&nbsp;&nbsp;' . htmlspecialchars(t3lib_BEfunc::getProcessedValue('pages', $field, $row[$field]));
					}
					break;
			}
		}
		$this->addElement_tdParams['title'] = ($row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : '');
		return $this->addelement(1, '', $theData);
	}


	/**********************************
	 *
	 * Additional functions; Content Elements
	 *
	 **********************************/

	/**
	 * Draw header for a content element column:
	 *
	 * @param	string		Column name
	 * @param	string		Edit params (Syntax: &edit[...] for alt_doc.php)
	 * @param	string		New element params (Syntax: &edit[...] for alt_doc.php)
	 * @return	string		HTML table
	 */
	function tt_content_drawColHeader($colName, $editParams, $newParams) {

		$icons = '';
		// Create command links:
		if ($this->tt_contentConfig['showCommands']) {
			// New record:
			if ($newParams) {
				$icons .= '<a href="#" onclick="' . htmlspecialchars($newParams) . '" title="' . $GLOBALS['LANG']->getLL('newInColumn', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-new') .
						'</a>';
			}
			// Edit whole of column:
			if ($editParams) {
				$icons .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($editParams, $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('editColumn', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-open') .
						'</a>';
			}
		}
		if (strlen($icons)) {
			$icons = '<div class="t3-page-colHeader-icons">' . $icons . '</div>';
		}

		// Create header row:
		$out = '<div class="t3-page-colHeader t3-row-header">
					' . $icons . '
					<div class="t3-page-colHeader-label">' . htmlspecialchars($colName) . '</div>
				</div>';
		return $out;
	}

	/**
	 * Draw the header for a single tt_content element
	 *
	 * @param	array		Record array
	 * @param	integer		Amount of pixel space above the header.
	 * @param	boolean		If set the buttons for creating new elements and moving up and down are not shown.
	 * @param	boolean		If set, we are in language mode and flags will be shown for languages
	 * @return	string		HTML table with the record header.
	 */
	function tt_content_drawHeader($row, $space = 0, $disableMoveAndNewButtons = FALSE, $langMode = FALSE) {
		global $TCA;

		// Load full table description:
		t3lib_div::loadTCA('tt_content');

		// Get record locking status:
		if ($lockInfo = t3lib_BEfunc::isRecordLocked('tt_content', $row['uid'])) {
			$lockIcon = '<a href="#" onclick="' . htmlspecialchars('alert(' . $GLOBALS['LANG']->JScharCode($lockInfo['msg']) . ');return false;') . '" title="' . htmlspecialchars($lockInfo['msg']) . '">' .
					t3lib_iconWorks::getSpriteIcon('status-warning-in-use') .
					'</a>';
		} else {
			$lockIcon = '';
		}

		// Call stats information hook
		$stat = '';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
			$_params = array('tt_content', $row['uid'], &$row);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
				$stat .= t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}

		// Create line with type of content element and icon/lock-icon/title:
		$ceType = $this->getIcon('tt_content', $row) . ' ' .
				$lockIcon . ' ' .
				$stat . ' ' .
				($langMode ? $this->languageFlag($row['sys_language_uid']) : '') . ' ' .
				'&nbsp;<strong>' . htmlspecialchars($this->CType_labels[$row['CType']]) . '</strong>';

		// If show info is set...;
		if ($this->tt_contentConfig['showInfo']) {

			// Get processed values:
			$info = Array();
			$this->getProcessedValue('tt_content', 'hidden,starttime,endtime,fe_group,spaceBefore,spaceAfter', $row, $info);

			// Render control panel for the element:
			if ($this->tt_contentConfig['showCommands'] && $this->doEdit) {

				if (!$disableMoveAndNewButtons) {
					// New content element:
					if ($this->option_newWizard) {
						$onClick = "window.location.href='db_new_content_el.php?id=" . $row['pid'] . '&sys_language_uid=' . $row['sys_language_uid'] . '&colPos=' . $row['colPos'] . '&uid_pid=' . (-$row['uid']) . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . "';";
					} else {
						$params = '&edit[tt_content][' . (-$row['uid']) . ']=new';
						$onClick = t3lib_BEfunc::editOnClick($params, $this->backPath);
					}
					$out .= '<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->getLL('newAfter', 1) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-new') .
							'</a>';
				}

				// Edit content element:
				$params = '&edit[tt_content][' . $this->tt_contentData['nextThree'][$row['uid']] . ']=edit';
				$out .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath)) . '" title="' .
						htmlspecialchars($this->nextThree > 1 ? sprintf($GLOBALS['LANG']->getLL('nextThree'), $this->nextThree) : $GLOBALS['LANG']->getLL('edit')) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-open') .
						'</a>';

				// Hide element:
				$hiddenField = $TCA['tt_content']['ctrl']['enablecolumns']['disabled'];
				if ($hiddenField && $TCA['tt_content']['columns'][$hiddenField] && (!$TCA['tt_content']['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', 'tt_content:' . $hiddenField))) {
					if ($row[$hiddenField]) {
						$params = '&data[tt_content][' . ($row['_ORIG_uid'] ? $row['_ORIG_uid'] : $row['uid']) . '][' . $hiddenField . ']=0';
						$out .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" title="' . $GLOBALS['LANG']->getLL('unHide', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-edit-unhide') .
								'</a>';
					} else {
						$params = '&data[tt_content][' . ($row['_ORIG_uid'] ? $row['_ORIG_uid'] : $row['uid']) . '][' . $hiddenField . ']=1';
						$out .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" title="' . $GLOBALS['LANG']->getLL('hide', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-edit-hide') .
								'</a>';
					}
				}

				// Delete
				$params = '&cmd[tt_content][' . $row['uid'] . '][delete]=1';
				$confirm = $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('deleteWarning') .
						t3lib_BEfunc::translationCount('tt_content', $row['uid'], ' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.translationsOfRecord')));
				$out .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" onclick="' . htmlspecialchars('return confirm(' . $confirm . ');') . '" title="' . $GLOBALS['LANG']->getLL('deleteItem', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-edit-delete') .
						'</a>';

				if (!$disableMoveAndNewButtons) {
					$out .= '<span class="t3-page-ce-icons-move">';
					// Move element up:
					if ($this->tt_contentData['prev'][$row['uid']]) {
						$params = '&cmd[tt_content][' . $row['uid'] . '][move]=' . $this->tt_contentData['prev'][$row['uid']];
						$out .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" title="' . $GLOBALS['LANG']->getLL('moveUp', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-move-up') .
								'</a>';
					} else {
						$out .= t3lib_iconWorks::getSpriteIcon('empty-empty');
					}
					// Move element down:
					if ($this->tt_contentData['next'][$row['uid']]) {
						$params = '&cmd[tt_content][' . $row['uid'] . '][move]= ' . $this->tt_contentData['next'][$row['uid']];
						$out .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" title="' . $GLOBALS['LANG']->getLL('moveDown', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-move-down') .
								'</a>';
					} else {
						$out .= t3lib_iconWorks::getSpriteIcon('empty-empty');
					}
					$out .= '</span>';
				}
			}

			// Display info from records fields:
			$infoOutput = '';
			if (count($info)) {
				$infoOutput = '<div class="t3-page-ce-info">
					' . implode('<br />', $info) . '
					</div>';
			}
		}
		// Wrap the whole header
		// NOTE: end-tag for <div class="t3-page-ce-body"> is in getTable_tt_content()
		return '<h4 class="t3-page-ce-header">
					<div class="t3-row-header">
					' . $out . '
					</div>
				</h4>
				<div class="t3-page-ce-body">
					<div class="t3-page-ce-type">
						' . $ceType . '
					</div>
					' . $infoOutput;
	}

	/**
	 * Draws the preview content for a content element
	 *
	 * @param	string		Content element
	 * @param	boolean		Set if the RTE link can be created.
	 * @return	string		HTML
	 */
	function tt_content_drawItem($row, $isRTE = FALSE) {
		global $TCA;

		$out = '';
		$outHeader = '';

		// Make header:
		if ($row['header']) {
			$infoArr = Array();
			$this->getProcessedValue('tt_content', 'header_position,header_layout,header_link', $row, $infoArr);

			// If header layout is set to 'hidden', display an accordant note:
			if ($row['header_layout'] == 100) {
				$hiddenHeaderNote = ' <em>[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.hidden', true) . ']</em>';
			}
			$outHeader = ($row['date'] ? htmlspecialchars($this->itemLabels['date'] . ' ' . t3lib_BEfunc::date($row['date'])) . '<br />' : '') .
					'<strong>' . $this->linkEditContent($this->renderText($row['header']), $row) . $hiddenHeaderNote . '</strong><br />';
		}

		// Make content:
		$infoArr = array();
		$drawItem = true;

		// Hook: Render an own preview of a record
		$drawItemHooks =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'];

		if (is_array($drawItemHooks)) {
			foreach ($drawItemHooks as $hookClass) {
				$hookObject = t3lib_div::getUserObj($hookClass);

				if (!($hookObject instanceof tx_cms_layout_tt_content_drawItemHook)) {
					throw new UnexpectedValueException('$hookObject must implement interface tx_cms_layout_tt_content_drawItemHook', 1218547409);
				}

				$hookObject->preProcess($this, $drawItem, $outHeader, $out, $row);
			}
		}

		// Draw preview of the item depending on its CType (if not disabled by previous hook):
		if ($drawItem) {
			switch ($row['CType']) {
				case 'header':
					if ($row['subheader']) {
						$out .= $this->linkEditContent($this->renderText($row['subheader']), $row) . '<br />';
					}
					break;
				case 'text':
				case 'textpic':
				case 'image':
					if ($row['CType'] == 'text' || $row['CType'] == 'textpic') {
						if ($row['bodytext']) {
							$this->getProcessedValue('tt_content', 'text_align,text_face,text_size,text_color,text_properties', $row, $infoArr);
							$out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
						}
					}
					if ($row['CType'] == 'textpic' || $row['CType'] == 'image') {
						if ($row['image']) {
							$out .= $this->thumbCode($row, 'tt_content', 'image') . '<br />';
							if ($row['imagecaption']) {
								$out .= $this->linkEditContent($this->renderText($row['imagecaption']), $row) . '<br />';
							}
						}
					}
					break;
				case 'bullets':
				case 'table':
				case 'mailform':
					if ($row['bodytext']) {
						$out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
					}
					break;
				case 'uploads':
					if ($row['media']) {
						$out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
					}
					break;
				case 'multimedia':
					if ($row['multimedia']) {
						$out .= $this->renderText($row['multimedia']) . '<br />';
						$out .= $this->renderText($row['parameters']) . '<br />';
					}
					break;
				case 'splash':
					if ($row['bodytext']) {
						$out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
					}
					if ($row['image']) {
						$out .= $this->thumbCode($row, 'tt_content', 'image') . '<br />';
					}
					break;
				case 'menu':
					if ($row['pages']) {
						$out .= $this->linkEditContent($row['pages'], $row) . '<br />';
					}
					break;
				case 'shortcut':
					if ($row['records']) {
						$out .= $this->linkEditContent($row['shortcut'], $row) . '<br />';
					}
					break;
				case 'list':
					$out .= $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content', 'list_type'), 1) . ' ' .
							$GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content', 'list_type', $row['list_type']), 1) . '<br />';
					$hookArr = array();
					$hookOut = '';
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']])) {
						$hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']];
					} elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'])) {
						$hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'];
					}
					if (count($hookArr) > 0) {
						$_params = array('pObj' => &$this, 'row' => $row, 'infoArr' => $infoArr);
						foreach ($hookArr as $_funcRef) {
							$hookOut .= t3lib_div::callUserFunction($_funcRef, $_params, $this);
						}
					}
					if (strcmp($hookOut, '')) {
						$out .= $hookOut;
					} else {
						$out .= $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content', 'select_key'), 1) . ' ' . $row['select_key'] . '<br />';
					}

					$out .= $GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content', 'pages', $row['pages']), 1) . '<br />';
					break;
				case 'script':
					$out .= $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('tt_content', 'select_key'), 1) . ' ' . $row['select_key'] . '<br />';
					$out .= '<br />' . $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
					$out .= '<br />' . $this->linkEditContent($this->renderText($row['imagecaption']), $row) . '<br />';
					break;
				default:
					if ($row['bodytext']) {
						$out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
					}
					break;
			}
		}

		// Wrap span-tags:
		$out = '
			<span class="exampleContent">' . $out . '</span>';
		// Add header:
		$out = $outHeader . $out;
		// Add RTE button:
		if ($isRTE) {
			$out .= $this->linkRTEbutton($row);
		}

		// Return values:
		if ($this->isDisabled('tt_content', $row)) {
			return $GLOBALS['TBE_TEMPLATE']->dfw($out);
		} else {
			return $out;
		}
	}

	/**
	 * Filters out all tt_content uids which are already translated so only non-translated uids is left.
	 * Selects across columns, but within in the same PID. Columns are expect to be the same for translations and original but this may be a conceptual error (?)
	 *
	 * @param	array		Numeric array with uids of tt_content elements in the default language
	 * @param	integer		Page pid
	 * @param	integer		Sys language UID
	 * @return	array		Modified $defLanguageCount
	 */
	function getNonTranslatedTTcontentUids($defLanguageCount, $id, $lP) {
		if ($lP && count($defLanguageCount)) {

			// Select all translations here:
			$queryParts = $this->makeQueryArray('tt_content', $id, 'AND sys_language_uid=' . intval($lP) . ' AND l18n_parent IN (' . implode(',', $defLanguageCount) . ')');
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);

			// Flip uids:
			$defLanguageCount = array_flip($defLanguageCount);

			// Traverse any selected elements and unset original UID if any:
			$rowArr = $this->getResult($result);
			foreach ($rowArr as $row) {
				unset($defLanguageCount[$row['l18n_parent']]);
			}

			// Flip again:
			$defLanguageCount = array_keys($defLanguageCount);
		}

		return $defLanguageCount;
	}

	/**
	 * Creates button which is used to create copies of records..
	 *
	 * @param	array		Numeric array with uids of tt_content elements in the default language
	 * @param	integer		Sys language UID
	 * @return	string		"Copy languages" button, if available.
	 */
	function newLanguageButton($defLanguageCount, $lP) {
		if ($this->doEdit && count($defLanguageCount) && $lP) {

			$params = '';
			foreach ($defLanguageCount as $uidVal) {
				$params .= '&cmd[tt_content][' . $uidVal . '][localize]=' . $lP;
			}

			// Copy for language:
			$onClick = "window.location.href='" . $GLOBALS['SOBE']->doc->issueCommand($params) . "'; return false;";
			$theNewButton = $GLOBALS['SOBE']->doc->t3Button($onClick, $GLOBALS['LANG']->getLL('newPageContent_copyForLang') . ' [' . count($defLanguageCount) . ']');
			return $theNewButton;
		}
	}

	/**
	 * Returns an icon, which has its title attribute set to a massive amount of information about the element.
	 *
	 * @param	array		Array where values are human readable output of field values (not htmlspecialchars()'ed though). The values are imploded by a linebreak.
	 * @return	string		HTML img tag if applicable.
	 * @deprecated since TYPO3 4.4, this function will be removed in TYPO3 4.6
	 */
	function infoGif($infoArr) {
		t3lib_div::logDeprecatedFunction();

		if (count($infoArr) && $this->tt_contentConfig['showInfo']) {
			return t3lib_iconWorks::getSpriteIcon('actions-document-info', array('title' => htmlspecialchars(implode(LF, $infoArr))));
		}
	}

	/**
	 * Creates onclick-attribute content for a new content element
	 *
	 * @param	integer		Page id where to create the element.
	 * @param	integer		Preset: Column position value
	 * @param	integer		Preset: Sys langauge value
	 * @return	string		String for onclick attribute.
	 * @see getTable_tt_content()
	 */
	function newContentElementOnClick($id, $colPos, $sys_language) {
		if ($this->option_newWizard) {
			$onClick = "window.location.href='db_new_content_el.php?id=" . $id . '&colPos=' . $colPos . '&sys_language_uid=' . $sys_language . '&uid_pid=' . $id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . "';";
		} else {
			$onClick = t3lib_BEfunc::editOnClick('&edit[tt_content][' . $id . ']=new&defVals[tt_content][colPos]=' . $colPos . '&defVals[tt_content][sys_language_uid]=' . $sys_language, $this->backPath);
		}
		return $onClick;
	}

	/**
	 * Will create a link on the input string and possible a big button after the string which links to editing in the RTE
	 * Used for content element content displayed so the user can click the content / "Edit in Rich Text Editor" button
	 *
	 * @param	string		String to link. Must be prepared for HTML output.
	 * @param	array		The row.
	 * @return	string		If the whole thing was editable ($this->doEdit) $str is return with link around. Otherwise just $str.
	 * @see getTable_tt_content()
	 */
	function linkEditContent($str, $row) {
		$addButton = '';
		$onClick = '';

		if ($this->doEdit) {
			// Setting onclick action for content link:
			$onClick = t3lib_BEfunc::editOnClick('&edit[tt_content][' . $row['uid'] . ']=edit', $this->backPath);
		}
		// Return link
		return $onClick ? '<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->getLL('edit', 1) . '">' . $str . '</a>' . $addButton : $str;
	}

	/**
	 * Adds a button to edit the row in RTE wizard
	 *
	 * @param	array		The row of tt_content element
	 * @return	string		Button to click if you want to edit in RTE wizard.
	 */
	function linkRTEbutton($row) {
		$params = array();
		$params['table'] = 'tt_content';
		$params['uid'] = $row['uid'];
		$params['pid'] = $row['pid'];
		$params['field'] = 'bodytext';
		$params['returnUrl'] = t3lib_div::linkThisScript();
		$RTEonClick = "window.location.href='" . $this->backPath . "wizard_rte.php?" . t3lib_div::implodeArrayForUrl('', array('P' => $params)) . "';return false;";
		$addButton = $this->option_showBigButtons && $this->doEdit ? $GLOBALS['SOBE']->doc->t3Button($RTEonClick, $GLOBALS['LANG']->getLL('editInRTE')) : '';

		return $addButton;
	}

	/**
	 * Make selector box for creating new translation in a language
	 * Displays only languages which are not yet present for the current page and
	 * that are not disabled with page TS.
	 *
	 * @param	integer		Page id for which to create a new language (pages_language_overlay record)
	 * @return	string		<select> HTML element (if there were items for the box anyways...)
	 * @see getTable_tt_content()
	 */
	function languageSelector($id) {
		if ($GLOBALS['BE_USER']->check('tables_modify', 'pages_language_overlay')) {

			// First, select all
			$res = $GLOBALS['SOBE']->exec_languageQuery(0);
			$langSelItems = array();
			$langSelItems[0] = '
						<option value="0"></option>';
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if ($GLOBALS['BE_USER']->checkLanguageAccess($row['uid'])) {
					$langSelItems[$row['uid']] = '
							<option value="' . $row['uid'] . '">' . htmlspecialchars($row['title']) . '</option>';
				}
			}

			// Then, subtract the languages which are already on the page:
			$res = $GLOBALS['SOBE']->exec_languageQuery($id);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				unset($langSelItems[$row['uid']]);
			}
			// Remove disallowed languages
			if (count($langSelItems) > 1 &&
					!$GLOBALS['BE_USER']->user['admin'] &&
					strlen($GLOBALS['BE_USER']->groupData['allowed_languages'])) {

				$allowed_languages = array_flip(explode(',', $GLOBALS['BE_USER']->groupData['allowed_languages']));

				if (count($allowed_languages)) {
					foreach ($langSelItems as $key => $value) {
						if (!isset($allowed_languages[$key]) && $key != 0) {
							unset($langSelItems[$key]);
						}
					}
				}
			}
			// Remove disabled languages
			$modSharedTSconfig = t3lib_BEfunc::getModTSconfig($id, 'mod.SHARED');
			$disableLanguages = isset($modSharedTSconfig['properties']['disableLanguages']) ? t3lib_div::trimExplode(',', $modSharedTSconfig['properties']['disableLanguages'], 1) : array();
			if (count($langSelItems) && count($disableLanguages)) {
				foreach ($disableLanguages as $language) {
					if ($language != 0 && isset($langSelItems[$language])) {
						unset($langSelItems[$language]);
					}
				}
			}

			// If any languages are left, make selector:
			if (count($langSelItems) > 1) {
				$onChangeContent = 'window.location.href=\'' . $this->backPath . 'alt_doc.php?&edit[pages_language_overlay][' . $id . ']=new&overrideVals[pages_language_overlay][doktype]=' . (int) $this->pageRecord['doktype'] . '&overrideVals[pages_language_overlay][sys_language_uid]=\'+this.options[this.selectedIndex].value+\'&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '\'';
				return $GLOBALS['LANG']->getLL('new_language', 1) . ': <select name="createNewLanguage" onchange="' . htmlspecialchars($onChangeContent) . '">
						' . implode('', $langSelItems) . '
					</select><br /><br />';
			}
		}
	}

	/**
	 * Traverse the result pointer given, adding each record to array and setting some internal values at the same time.
	 *
	 * @param	pointer		SQL result pointer for select query.
	 * @param	string		Table name defaulting to tt_content
	 * @return	array		The selected rows returned in this array.
	 */
	function getResult($result, $table = 'tt_content') {

		// Initialize:
		$editUidList = '';
		$recs = Array();
		$nextTree = $this->nextThree;
		$c = 0;
		$output = Array();

		// Traverse the result:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			t3lib_BEfunc::workspaceOL($table, $row, -99, TRUE);

			if ($row) {
				// Add the row to the array:
				$output[] = $row;

				// Set an internal register:
				$recs[$c] = $row['uid'];

				// Create the list of the next three ids (for editing links...)
				for ($a = 0; $a < $nextTree; $a++) {
					if (isset($recs[$c - $a])) {
						$this->tt_contentData['nextThree'][$recs[$c - $a]] .= $row['uid'] . ',';
					}
				}

				// Set next/previous ids:
				if (isset($recs[$c - 1])) {
					if (isset($recs[$c - 2])) {
						$this->tt_contentData['prev'][$row['uid']] = -$recs[$c - 2];
					} else {
						$this->tt_contentData['prev'][$row['uid']] = $row['pid'];
					}
					$this->tt_contentData['next'][$recs[$c - 1]] = -$row['uid'];
				}
				$c++;
			}
		}

		// Return selected records
		return $output;
	}


	/**********************************
	 *
	 * Additional functions; Message board items (tt_board)
	 *
	 **********************************/

	/**
	 * Traverses recursively a branch in a message board.
	 *
	 * @param	array		Array of rows (build up recursively)
	 * @param	integer		tt_content parent uid
	 * @param	integer		Page id
	 * @param	string		Additional query part.
	 * @param	string		HTML content to prefix items with (to draw the proper tree-graphics)
	 * @return	array		$theRows, but with added content
	 */
	function tt_board_getTree($theRows, $parent, $pid, $qWhere, $treeIcons) {

		// Select tt_board elements:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_board', 'pid=' . intval($pid) . ' AND parent=' . intval($parent) . $qWhere, '', 'crdate');

		// Traverse the results:
		$c = 0;
		$rc = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$c++;
			$row['treeIcons'] = $treeIcons . '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' . ($rc == $c ? 'joinbottom.gif' : 'join.gif'), 'width="18" height="16"') . ' alt="" />';
			$theRows[] = $row;

			// Get the branch
			$theRows = $this->tt_board_getTree(
				$theRows,
				$row['uid'],
				$row['pid'],
				$qWhere,
					$treeIcons . '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' . ($rc == $c ? 'blank.gif' : 'line.gif'), 'width="18" height="16"') . ' alt="" />'
			);
		}

		// Return modified rows:
		return $theRows;
	}

	/**
	 * Adds an element to the tt_board listing:
	 *
	 * @param	string		Table name
	 * @param	array		The record row
	 * @param	string		Reply count, if applicable.
	 * @return	string		Return content of element (table row)
	 */
	function tt_board_drawItem($table, $row, $re) {

		// Building data-arary with content:
		$theData = Array();
		$theData['subject'] = t3lib_div::fixed_lgd_cs(htmlspecialchars($row['subject']), 25) . '&nbsp; &nbsp;';
		$theData['author'] = t3lib_div::fixed_lgd_cs(htmlspecialchars($row['author']), 15) . '&nbsp; &nbsp;';
		$theData['date'] = t3lib_div::fixed_lgd_cs(t3lib_BEfunc::datetime($row['crdate']), 20) . '&nbsp; &nbsp;';
		$theData['age'] = t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME'] - $row['crdate'], $this->agePrefixes) . '&nbsp; &nbsp;';
		if ($re) {
			$theData['replys'] = $re;
		}

		// Subject is built:
		$theData['subject'] =
				$row['treeIcons'] .
						$this->getIcon($table, $row) .
						$theData['subject'];

		// Adding element:
		return $this->addelement(1, '', $theData);
	}


	/********************************
	 *
	 * Various helper functions
	 *
	 ********************************/

	/**
	 * Counts and returns the number of records on the page with $pid
	 *
	 * @param	string		Table name
	 * @param	integer		Page id
	 * @return	integer		Number of records.
	 */
	function numberOfRecords($table, $pid) {
		if ($GLOBALS['TCA'][$table]) {
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'uid',
				$table,
					'pid=' . intval($pid) .
							t3lib_BEfunc::deleteClause($table) .
							t3lib_BEfunc::versioningPlaceholderClause($table)
			);
		}
		return intval($count);
	}

	/**
	 * Processing of larger amounts of text (usually from RTE/bodytext fields) with word wrapping etc.
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function renderText($input) {
		$input = $this->strip_tags($input, true);
		$input = t3lib_div::fixed_lgd_cs($input, 1500);
		return nl2br(htmlspecialchars(trim($this->wordWrapper($input))));
	}

	/**
	 * Creates the icon image tag for record from table and wraps it in a link which will trigger the click menu.
	 *
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	string		Record title (NOT USED)
	 * @return	string		HTML for the icon
	 */
	function getIcon($table, $row) {

		// Initialization
		$alttext = t3lib_BEfunc::getRecordIconAltText($row, $table);
		$iconImg = t3lib_iconWorks::getSpriteIconForRecord($table, $row, array('title' => $alttext));
		$this->counter++;

		// The icon with link
		$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, $table, $row['uid']);

		return $theIcon;
	}

	/**
	 * Creates processed values for all fieldnames in $fieldList based on values from $row array.
	 * The result is 'returned' through $info which is passed as a reference
	 *
	 * @param	string		Table name
	 * @param	string		Commalist of fields.
	 * @param	array		Record from which to take values for processing.
	 * @param	array		Array to which the processed values are added.
	 * @return	void
	 */
	function getProcessedValue($table, $fieldList, $row, &$info) {

		// Splitting values from $fieldList:
		$fieldArr = explode(',', $fieldList);

		// Traverse fields from $fieldList:
		foreach ($fieldArr as $field) {
			if ($row[$field]) {
				$info[] = htmlspecialchars($this->itemLabels[$field]) . ' ' . htmlspecialchars(t3lib_BEfunc::getProcessedValue($table, $field, $row[$field]));
			}
		}
	}

	/**
	 * Returns true, if the record given as parameters is NOT visible based on hidden/starttime/endtime (if available)
	 *
	 * @param	string		Tablename of table to test
	 * @param	array		Record row.
	 * @return	boolean		Returns true, if disabled.
	 */
	function isDisabled($table, $row) {
		global $TCA;
		if (
			($TCA[$table]['ctrl']['enablecolumns']['disabled'] && $row[$TCA[$table]['ctrl']['enablecolumns']['disabled']]) ||
			($TCA[$table]['ctrl']['enablecolumns']['starttime'] && $row[$TCA[$table]['ctrl']['enablecolumns']['starttime']] > $GLOBALS['EXEC_TIME']) ||
			($TCA[$table]['ctrl']['enablecolumns']['endtime'] && $row[$TCA[$table]['ctrl']['enablecolumns']['endtime']] && $row[$TCA[$table]['ctrl']['enablecolumns']['endtime']] < $GLOBALS['EXEC_TIME'])
		) {
			return true;
		}
	}

	/**
	 * Will perform "word-wrapping" on the input string. What it does is to split by space or linebreak, then find any word longer than $max and if found, a hyphen is inserted.
	 * Works well on normal texts, little less well when HTML is involved (since much HTML will have long strings that will be broken).
	 *
	 * @param	string		Content to word-wrap.
	 * @param	integer		Max number of chars in a word before it will be wrapped.
	 * @param	string		Character to insert when wrapping.
	 * @return	string		Processed output.
	 */
	function wordWrapper($content, $max = 50, $char = ' -') {
		$array = preg_split('/[ ' . LF . ']/', $content);
		foreach ($array as $val) {
			if (strlen($val) > $max) {
				$content = str_replace($val, substr(chunk_split($val, $max, $char), 0, -1), $content);
			}
		}
		return $content;
	}

	/**
	 * Returns icon for "no-edit" of a record.
	 * Basically, the point is to signal that this record could have had an edit link if the circumstances were right. A placeholder for the regular edit icon...
	 *
	 * @param	string		Label key from LOCAL_LANG
	 * @return	string		IMG tag for icon.
	 */
	function noEditIcon($label = 'noEditItems') {
		return t3lib_iconWorks::getSpriteIcon('status-edit-read-only', array('title' => $GLOBALS['LANG']->getLL($label, TRUE)));
	}

	/**
	 * Function, which fills in the internal array, $this->allowedTableNames with all tables to which the user has access. Also a set of standard tables (pages, static_template, sys_filemounts, etc...) are filtered out. So what is left is basically all tables which makes sense to list content from.
	 *
	 * @return	void
	 */
	function cleanTableNames() {
		global $TCA;

		// Get all table names:
		$tableNames = array_flip(array_keys($TCA));

		// Unset common names:
		unset($tableNames['pages']);
		unset($tableNames['static_template']);
		unset($tableNames['sys_filemounts']);
		unset($tableNames['sys_action']);
		unset($tableNames['sys_workflows']);
		unset($tableNames['be_users']);
		unset($tableNames['be_groups']);

		$this->allowedTableNames = array();

		// Traverse table names and set them in allowedTableNames array IF they can be read-accessed by the user.
		if (is_array($tableNames)) {
			foreach ($tableNames as $k => $v) {
				if ($GLOBALS['BE_USER']->check('tables_select', $k)) {
					$this->allowedTableNames['table_' . $k] = $k;
				}
			}
		}
	}

	/**
	 * Checking if the RTE is available/enabled for a certain table/field and if so, it returns true.
	 * Used to determine if the RTE button should be displayed.
	 *
	 * @param	string		Table name
	 * @param	array		Record row (needed, if there are RTE dependencies based on other fields in the record)
	 * @param	string		Field name
	 * @return	boolean		Returns true if the rich text editor would be enabled/available for the field name specified.
	 */
	function isRTEforField($table, $row, $field) {
		$specConf = $this->getSpecConfForField($table, $row, $field);
		$p = t3lib_BEfunc::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
		if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']])) {
			t3lib_BEfunc::fixVersioningPid($table, $row);
			list($tscPID, $thePidValue) = t3lib_BEfunc::getTSCpid($table, $row['uid'], $row['pid']);
			if ($thePidValue >= 0) { // If the pid-value is not negative (that is, a pid could NOT be fetched)
				$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', t3lib_BEfunc::getPagesTSconfig($tscPID));
				$RTEtypeVal = t3lib_BEfunc::getTCAtypeValue($table, $row);
				$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'], $table, $field, $RTEtypeVal);
				if (!$thisConfig['disabled']) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Returns "special" configuration from the "types" configuration in TCA for the record given by tablename/fieldname.
	 * Used by isRTEforField() in the process of finding whether a field has RTE enabled or not.
	 *
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	string		Field name
	 * @return	array		Spec. conf (if available)
	 * @access private
	 * @see isRTEforField()
	 */
	function getSpecConfForField($table, $row, $field) {

		// Get types-configuration for the record:
		$types_fieldConfig = t3lib_BEfunc::getTCAtypes($table, $row);

		// Find the given field and return the spec key value if found:
		if (is_array($types_fieldConfig)) {
			foreach ($types_fieldConfig as $vconf) {
				if ($vconf['field'] == $field) {
					return $vconf['spec'];
				}
			}
		}
	}


	/*****************************************
	 *
	 * External renderings
	 *
	 *****************************************/

	/**
	 * Creates an info-box for the current page (identified by input record).
	 *
	 * @param	array		Page record
	 * @param	boolean		If set, there will be shown an edit icon, linking to editing of the page properties.
	 * @return	string		HTML for the box.
	 */
	function getPageInfoBox($rec, $edit = 0) {
		global $LANG;

		// If editing of the page properties is allowed:
		if ($edit) {
			$params = '&edit[pages][' . $rec['uid'] . ']=edit';
			$editIcon = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('edit', TRUE) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-open') .
					'</a>';
		} else {
			$editIcon = $this->noEditIcon('noEditPage');
		}

		// Setting page icon, link, title:
		$outPutContent = t3lib_iconWorks::getSpriteIconForRecord('pages', $rec, array('title' => t3lib_BEfunc::titleAttribForPages($rec))) .
				$editIcon .
				'&nbsp;' .
				htmlspecialchars($rec['title']);


		// Init array where infomation is accumulated as label/value pairs.
		$lines = array();

		// Owner user/group:
		if ($this->pI_showUser) {
			// User:
			$users = t3lib_BEfunc::getUserNames('username,usergroup,usergroup_cached_list,uid,realName');
			$groupArray = explode(',', $GLOBALS['BE_USER']->user['usergroup_cached_list']);
			$users = t3lib_BEfunc::blindUserNames($users, $groupArray);
			$lines[] = array($LANG->getLL('pI_crUser') . ':', htmlspecialchars($users[$rec['cruser_id']]['username']) . ' (' . $users[$rec['cruser_id']]['realName'] . ')');
		}

		// Created:
		$lines[] = array(
			$LANG->getLL('pI_crDate') . ':',
			t3lib_BEfunc::datetime($rec['crdate']) . ' (' . t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME'] - $rec['crdate'], $this->agePrefixes) . ')',
		);

		// Last change:
		$lines[] = array(
			$LANG->getLL('pI_lastChange') . ':',
			t3lib_BEfunc::datetime($rec['tstamp']) . ' (' . t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME'] - $rec['tstamp'], $this->agePrefixes) . ')',
		);

		// Last change of content:
		if ($rec['SYS_LASTCHANGED']) {
			$lines[] = array(
				$LANG->getLL('pI_lastChangeContent') . ':',
				t3lib_BEfunc::datetime($rec['SYS_LASTCHANGED']) . ' (' . t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME'] - $rec['SYS_LASTCHANGED'], $this->agePrefixes) . ')',
			);
		}

		// Spacer:
		$lines[] = '';

		// Display contents of certain page fields, if any value:
		$dfields = explode(',', 'alias,target,hidden,starttime,endtime,fe_group,no_cache,cache_timeout,newUntil,lastUpdated,subtitle,keywords,description,abstract,author,author_email');
		foreach ($dfields as $fV) {
			if ($rec[$fV]) {
				$lines[] = array($GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel('pages', $fV)), t3lib_BEfunc::getProcessedValue('pages', $fV, $rec[$fV]));
			}
		}

		// Page hits (depends on "sys_stat" extension)
		if ($this->pI_showStat && t3lib_extMgm::isLoaded('sys_stat')) {

			// Counting total hits:
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'sys_stat', 'page_id=' . intval($rec['uid']));
			if ($count) {

				// Get min/max
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('min(tstamp) AS min,max(tstamp) AS max', 'sys_stat', 'page_id=' . intval($rec['uid']));
				$rrow2 = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

				$lines[] = '';
				$lines[] = array($LANG->getLL('pI_hitsPeriod') . ':', t3lib_BEfunc::date($rrow2[0]) . ' - ' . t3lib_BEfunc::date($rrow2[1]) . ' (' . t3lib_BEfunc::calcAge($rrow2[1] - $rrow2[0], $this->agePrefixes) . ')');
				$lines[] = array($LANG->getLL('pI_hitsTotal') . ':', $rrow2[0]);


				// Last 10 days
				$nextMidNight = mktime(0, 0, 0) + 1 * 3600 * 24;

				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*), FLOOR((' . $nextMidNight . '-tstamp)/(24*3600)) AS day', 'sys_stat', 'page_id=' . intval($rec['uid']) . ' AND tstamp>' . ($nextMidNight - 10 * 24 * 3600), 'day');
				$days = array();
				while ($rrow = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
					$days[$rrow[1]] = $rrow[0];
				}

				$headerH = array();
				$contentH = array();
				for ($a = 9; $a >= 0; $a--) {
					$headerH[] = '
							<td class="bgColor5" nowrap="nowrap">&nbsp;' . date('d', $nextMidNight - ($a + 1) * 24 * 3600) . '&nbsp;</td>';
					$contentH[] = '
							<td align="center">' . ($days[$a] ? intval($days[$a]) : '-') . '</td>';
				}

				// Compile first hit-table (last 10 days)
				$hitTable = '
					<table border="0" cellpadding="0" cellspacing="1" class="typo3-page-hits">
						<tr>' . implode('', $headerH) . '</tr>
						<tr>' . implode('', $contentH) . '</tr>
					</table>';
				$lines[] = array($LANG->getLL('pI_hits10days') . ':', $hitTable, 1);


				// Last 24 hours
				$nextHour = mktime(date('H'), 0, 0) + 3600;
				$hours = 16;

				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*), FLOOR((' . $nextHour . '-tstamp)/3600) AS hours', 'sys_stat', 'page_id=' . intval($rec['uid']) . ' AND tstamp>' . ($nextHour - $hours * 3600), 'hours');
				$days = array();
				while ($rrow = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
					$days[$rrow[1]] = $rrow[0];
				}

				$headerH = array();
				$contentH = array();
				for ($a = ($hours - 1); $a >= 0; $a--) {
					$headerH[] = '
							<td class="bgColor5" nowrap="nowrap">&nbsp;' . intval(date('H', $nextHour - ($a + 1) * 3600)) . '&nbsp;</td>';
					$contentH[] = '
							<td align="center">' . ($days[$a] ? intval($days[$a]) : '-') . '</td>';
				}

				// Compile second hit-table (last 24 hours)
				$hitTable = '
					<table border="0" cellpadding="0" cellspacing="1" class="typo3-page-stat">
						<tr>' . implode('', $headerH) . '</tr>
						<tr>' . implode('', $contentH) . '</tr>
					</table>';
				$lines[] = array($LANG->getLL('pI_hits24hours') . ':', $hitTable, 1);
			}
		}


		// Finally, wrap the elements in the $lines array in table cells/rows
		foreach ($lines as $fV) {
			if (is_array($fV)) {
				if (!$fV[2]) {
					$fV[1] = htmlspecialchars($fV[1]);
				}
				$out .= '
				<tr>
					<td class="bgColor4" nowrap="nowrap"><strong>' . htmlspecialchars($fV[0]) . '&nbsp;&nbsp;</strong></td>
					<td class="bgColor4">' . $fV[1] . '</td>
				</tr>';
			} else {
				$out .= '
				<tr>
					<td colspan="2"><img src="clear.gif" width="1" height="3" alt="" /></td>
				</tr>';
			}
		}

		// Wrap table tags around...
		$outPutContent .= '



			<!--
				Page info box:
			-->
			<table border="0" cellpadding="0" cellspacing="1" id="typo3-page-info">
				' . $out . '
			</table>';

		// ... and return it.
		return $outPutContent;
	}

	/**
	 * Creates a menu of the tables that can be listed by this function
	 * Only tables which has records on the page will be included.
	 * Notice: The function also fills in the internal variable $this->activeTables with icon/titles.
	 *
	 * @param	integer		Page id from which we are listing records (the function will look up if there are records on the page)
	 * @return	string		HTML output.
	 */
	function getTableMenu($id) {
		global $TCA;

		// Initialize:
		$this->activeTables = array();
		$theTables = explode(',', 'tt_content,fe_users,tt_address,tt_links,tt_board,tt_guest,tt_calender,tt_products,tt_news'); // NOTICE: This serves double function: Both being tables names (all) and for most others also being extension keys for the extensions they are related to!

		// External tables:
		if (is_array($this->externalTables)) {
			$theTables = array_unique(array_merge($theTables, array_keys($this->externalTables)));
		}

		// Traverse tables to check:
		foreach ($theTables as $tName) {

			// Check access and whether the proper extensions are loaded:
			if ($GLOBALS['BE_USER']->check('tables_select', $tName) && (t3lib_extMgm::isLoaded($tName) || t3lib_div::inList('fe_users,tt_content', $tName) || isset($this->externalTables[$tName]))) {

				// Make query to count records from page:
				$c = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
					'uid',
					$tName,
						'pid=' . intval($id) .
								t3lib_BEfunc::deleteClause($tName) .
								t3lib_BEfunc::versioningPlaceholderClause($tName)
				);

				// If records were found (or if "tt_content" is the table...):
				if ($c || t3lib_div::inList('tt_content', $tName)) {

					// Add row to menu:
					$out .= '
					<td><a href="#' . $tName . '"></a>' .
							t3lib_iconWorks::getSpriteIconForRecord($tName, Array(), array('title' => $GLOBALS['LANG']->sL($TCA[$tName]['ctrl']['title'], 1))) .
							'</td>';

					// ... and to the internal array, activeTables we also add table icon and title (for use elsewhere)
					$this->activeTables[$tName] =
							t3lib_iconWorks::getSpriteIconForRecord($tName, Array(), array('title' => $GLOBALS['LANG']->sL($TCA[$tName]['ctrl']['title'], 1) . ': ' . $c . ' ' . $GLOBALS['LANG']->getLL('records', 1))) .
									'&nbsp;' .
									$GLOBALS['LANG']->sL($TCA[$tName]['ctrl']['title'], 1);
				}
			}
		}

		// Wrap cells in table tags:
		$out = '



			<!--
				Menu of tables on the page (table menu)
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-page-tblMenu">
				<tr>' . $out . '
				</tr>
			</table>';

		// Return the content:
		return $out;
	}

	/**
	 * Enhancement for the strip_tags function that provides the feature to fill in empty tags.
	 * Example <link email@hostname.com></link> is accepted by TYPO3 but would not displayed in the Backend otherwise.
	 *
	 * @param	string		Input string
	 * @param	boolean		If true, empty tags will be filled with the first attribute of the tag before.
	 * @return	string		Input string with all HTML and PHP tags stripped
	 */
	function strip_tags($content, $fillEmptyContent = false) {
		if ($fillEmptyContent && strstr($content, '><')) {
			$content = preg_replace('/(<[^ >]* )([^ >]*)([^>]*>)(<\/[^>]*>)/', '$1$2$3$2$4', $content);
		}
		$content = preg_replace('/<br.?\/?>/', LF, $content);

		return strip_tags($content);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cms/layout/class.tx_cms_layout.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cms/layout/class.tx_cms_layout.php']);
}

?>