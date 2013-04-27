<?php
namespace TYPO3\CMS\Backend\View;

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
 * Include file extending db_list.inc for use with the web_layout module
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Child class for the Web > Page module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class PageLayoutView extends \TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList {

	// External, static: Flags of various kinds:
	// If TRUE, users/groups are shown in the page info box.
	/**
	 * @todo Define visibility
	 */
	public $pI_showUser = 0;

	// The number of successive records to edit when showing content elements.
	/**
	 * @todo Define visibility
	 */
	public $nextThree = 3;

	// If TRUE, disables the edit-column icon for tt_content elements
	/**
	 * @todo Define visibility
	 */
	public $pages_noEditColumns = 0;

	// If TRUE, shows big buttons for editing page properties, moving, creating elements etc. in the columns view.
	/**
	 * @todo Define visibility
	 */
	public $option_showBigButtons = 1;

	// If TRUE, new-wizards are linked to rather than the regular new-element list.
	/**
	 * @todo Define visibility
	 */
	public $option_newWizard = 1;

	// If set to "1", will link a big button to content element wizard.
	/**
	 * @todo Define visibility
	 */
	public $ext_function = 0;

	// If TRUE, elements will have edit icons (probably this is whethere the user has permission to edit the page content). Set externally.
	/**
	 * @todo Define visibility
	 */
	public $doEdit = 1;

	// Age prefixes for displaying times. May be set externally to localized values.
	/**
	 * @todo Define visibility
	 */
	public $agePrefixes = ' min| hrs| days| yrs| min| hour| day| year';

	// Array of tables to be listed by the Web > Page module in addition to the default tables.
	/**
	 * @todo Define visibility
	 */
	public $externalTables = array();

	// "Pseudo" Description -table name
	/**
	 * @todo Define visibility
	 */
	public $descrTable;

	// If set TRUE, the language mode of tt_content elements will be rendered with hard binding between
	// default language content elements and their translations!
	/**
	 * @todo Define visibility
	 */
	public $defLangBinding = FALSE;

	// External, static: Configuration of tt_content element display:
	/**
	 * @todo Define visibility
	 */
	public $tt_contentConfig = array(
		'showInfo' => 1,
		// Boolean: Display info-marks or not
		'showCommands' => 1,
		// Boolean: Display up/down arrows and edit icons for tt_content records
		'single' => 1,
		// Boolean: If set, the content of column(s) $this->tt_contentConfig['showSingleCol'] is shown in the total width of the page
		'showAsGrid' => 0,
		// Boolean: If set, the content of columns is shown in grid
		'showSingleCol' => 0,
		// The column(s) to show if single mode (under each other)
		'languageCols' => 0,
		'languageMode' => 0,
		'languageColsPointer' => 0,
		'showHidden' => 1,
		// Displays hidden records as well
		'sys_language_uid' => 0,
		// Which language
		'cols' => '1,0,2,3'
	);

	// Internal, dynamic:
	// Will contain a list of tables which can be listed by the user.
	/**
	 * @todo Define visibility
	 */
	public $allowedTableNames = array();

	// Contains icon/title of pages which are listed in the tables menu (see getTableMenu() function )
	/**
	 * @todo Define visibility
	 */
	public $activeTables = array();

	/**
	 * @todo Define visibility
	 */
	public $tt_contentData = array(
		'nextThree' => array(),
		'prev' => array(),
		'next' => array()
	);

	// Used to store labels for CTypes for tt_content elements
	/**
	 * @todo Define visibility
	 */
	public $CType_labels = array();

	// Used to store labels for the various fields in tt_content elements
	/**
	 * @todo Define visibility
	 */
	public $itemLabels = array();

	/*****************************************
	 *
	 * Renderings
	 *
	 *****************************************/
	/**
	 * Adds the code of a single table
	 *
	 * @param string $table Table name
	 * @param integer $id Current page id
	 * @return string HTML for listing.
	 * @todo Define visibility
	 */
	public function getTable($table, $id) {
		if (isset($this->externalTables[$table])) {
			return $this->getExternalTables($id, $table);
		} else {
			// Branch out based on table name:
			switch ($table) {
			case 'pages':
				return $this->getTable_pages($id);
				break;
			case 'tt_content':
				return $this->getTable_tt_content($id);
				break;
			}
		}
	}

	/**
	 * Renders an external table from page id
	 *
	 * @param integer $id Page id
	 * @param string $table Name of the table
	 * @return string HTML for the listing
	 * @todo Define visibility
	 */
	public function getExternalTables($id, $table) {
		$type = $GLOBALS['SOBE']->MOD_SETTINGS[$table];
		if (!isset($type)) {
			$type = 0;
		}
		// eg. "name;title;email;company,image"
		$fList = $this->externalTables[$table][$type]['fList'];
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
	 * @param integer $id Page id
	 * @return string HTML for the listing
	 * @todo Define visibility
	 */
	public function getTable_pages($id) {
		// Initializing:
		$out = '';
		// Select clause for pages:
		$delClause = \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages') . ' AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1);
		// Select current page:
		if (!$id) {
			// The root has a pseudo record in pageinfo...
			$row = $GLOBALS['SOBE']->pageinfo;
		} else {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid=' . intval($id) . $delClause);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('pages', $row);
		}
		// If there was found a page:
		if (is_array($row)) {
			// Select which fields to show:
			$pKey = $GLOBALS['SOBE']->MOD_SETTINGS['pages'];
			switch ($pKey) {
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
			$theData = array();
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
				$theRows = array();
				$theRows = $this->pages_getTree($theRows, $row['uid'], $delClause . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('pages'), '', $depth);
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
			$theData = array();
			$editIdList = implode(',', $editUids);
			// Traverse fields (as set above) in order to create header values:
			foreach ($this->fieldArray as $field) {
				if ($editIdList && isset($GLOBALS['TCA']['pages']['columns'][$field]) && $field != 'uid' && !$this->pages_noEditColumns) {
					$params = '&edit[pages][' . $editIdList . ']=edit&columnsOnly=' . $field . '&disHelp=1';
					$iTitle = sprintf($GLOBALS['LANG']->getLL('editThisColumn'), rtrim(trim($GLOBALS['LANG']->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('pages', $field))), ':'));
					$eI = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath, '')) . '" title="' . htmlspecialchars($iTitle) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
				} else {
					$eI = '';
				}
				switch ($field) {
				case 'title':
					$theData[$field] = '&nbsp;<strong>' . $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns'][$field]['label']) . '</strong>' . $eI;
					break;
				case 'uid':
					$theData[$field] = '&nbsp;<strong>ID:</strong>';
					break;
				default:
					if (substr($field, 0, 6) == 'table_') {
						$f2 = substr($field, 6);
						if ($GLOBALS['TCA'][$f2]) {
							$theData[$field] = '&nbsp;' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($f2, array(), array('title' => $GLOBALS['LANG']->sL($GLOBALS['TCA'][$f2]['ctrl']['title'], 1)));
						}
					} else {
						$theData[$field] = '&nbsp;&nbsp;<strong>' . $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns'][$field]['label'], 1) . '</strong>' . $eI;
					}
					break;
				}
			}
			// Start table:
			$this->oddColumnsCssClass = '';
			// CSH:
			$out = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, ('func_' . $pKey), $GLOBALS['BACK_PATH']) . '
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-page-pages">
					' . $this->addelement(1, '', $theData, ' class="t3-row-header"', 20) . $out . '
				</table>';
		}
		$this->oddColumnsCssClass = '';
		return $out;
	}

	/**
	 * Returns the backend layout which should be used for this page.
	 *
	 * @param integer $id Uid of the current page
	 * @return mixed Uid of the backend layout record or NULL if no layout should be used
	 * @todo Define visibility
	 */
	public function getSelectedBackendLayoutUid($id) {
		// uid and pid are needed for workspaceOL()
		$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid, pid, backend_layout', 'pages', 'uid=' . $id);
		\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('pages', $page);
		$backendLayoutUid = intval($page['backend_layout']);
		if ($backendLayoutUid == -1) {
			// If it is set to "none" - don't use any
			$backendLayoutUid = NULL;
		} elseif ($backendLayoutUid == 0) {
			// If it not set check the rootline for a layout on next level and use this
			$rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id, '', TRUE);
			for ($i = count($rootline) - 2; $i > 0; $i--) {
				$backendLayoutUid = intval($rootline[$i]['backend_layout_next_level']);
				if ($backendLayoutUid > 0) {
					// Stop searching if a layout for "next level" is set
					break;
				} elseif ($backendLayoutUid == -1) {
					// If layout for "next level" is set to "none" - don't use any and stop searching
					$backendLayoutUid = NULL;
					break;
				}
			}
		}
		// If it is set to a positive value use this
		return $backendLayoutUid;
	}

	/**
	 * Renders Content Elements from the tt_content table from page id
	 *
	 * @param integer $id Page id
	 * @return string HTML for the listing
	 * @todo Define visibility
	 */
	public function getTable_tt_content($id) {
		$this->initializeLanguages();
		// Initialize:
		$RTE = $GLOBALS['BE_USER']->isRTE();
		$lMarg = 1;
		$showHidden = $this->tt_contentConfig['showHidden'] ? '' : \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_content');
		$pageTitleParamForAltDoc = '&recTitle=' . rawurlencode(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $id), TRUE));
		$GLOBALS['SOBE']->doc->getPageRenderer()->loadExtJs();
		$GLOBALS['SOBE']->doc->getPageRenderer()->addJsFile($GLOBALS['BACK_PATH'] . 'sysext/cms/layout/js/typo3pageModule.js');
		// Get labels for CTypes and tt_content element fields in general:
		$this->CType_labels = array();
		foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $val) {
			$this->CType_labels[$val[1]] = $GLOBALS['LANG']->sL($val[0]);
		}
		$this->itemLabels = array();
		foreach ($GLOBALS['TCA']['tt_content']['columns'] as $name => $val) {
			$this->itemLabels[$name] = $GLOBALS['LANG']->sL($val['label']);
		}
		// Select display mode:
		// MULTIPLE column display mode, side by side:
		if (!$this->tt_contentConfig['single']) {
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
			$langListArr = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $langList);
			$defLanguageCount = array();
			$defLangBinding = array();
			// For each languages... :
			// If not languageMode, then we'll only be through this once.
			foreach ($langListArr as $lP) {
				$showLanguage = ' AND sys_language_uid IN (' . intval($lP) . ',-1)';
				$cList = explode(',', $this->tt_contentConfig['cols']);
				$content = array();
				$head = array();

				// Select content records per column
				$contentRecordsPerColumn = $this->getContentRecordsPerColumn('table', $id, array_values($cList), $showHidden . $showLanguage);
				// For each column, render the content into a variable:
				foreach ($cList as $key) {
					if (!$lP) {
						$defLanguageCount[$key] = array();
					}
					// Start wrapping div
					$content[$key] .= '<div class="t3-page-ce-wrapper">';
					// Add new content at the top most position
					$content[$key] .= '
					<div class="t3-page-ce" id="' . uniqid() . '">
						<div class="t3-page-ce-dropzone" id="colpos-' . $key . '-' . 'page-' . $id . '-' . uniqid() . '">
							<div class="t3-page-ce-wrapper-new-ce">
								<a href="#" onclick="' . htmlspecialchars($this->newContentElementOnClick($id, $key, $lP)) . '" title="' . $GLOBALS['LANG']->getLL('newRecordHere', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') . '</a>
							</div>
						</div>
					</div>
					';
					$editUidList = '';
					$rowArr = $contentRecordsPerColumn[$key];
					foreach ((array) $rowArr as $rKey => $row) {
						if ($this->tt_contentConfig['languageMode']) {
							$languageColumn[$key][$lP] = $head[$key] . $content[$key];
							if (!$this->defLangBinding) {
								$languageColumn[$key][$lP] .= '<br /><br />' . $this->newLanguageButton($this->getNonTranslatedTTcontentUids($defLanguageCount[$key], $id, $lP), $lP);
							}
						}
						if (is_array($row) && (int) $row['t3ver_state'] != 2) {
							$singleElementHTML = '';
							if (!$lP && $row['sys_language_uid'] != -1) {
								$defLanguageCount[$key][] = $row['uid'];
							}
							$editUidList .= $row['uid'] . ',';
							$disableMoveAndNewButtons = $this->defLangBinding && $lP > 0;
							if (!$this->tt_contentConfig['languageMode']) {
								$singleElementHTML .= '<div class="t3-page-ce-dragitem" id="' . uniqid() . '">';
							}
							$singleElementHTML .= $this->tt_content_drawHeader($row, $this->tt_contentConfig['showInfo'] ? 15 : 5, $disableMoveAndNewButtons, TRUE,
								!$this->tt_contentConfig['languageMode']);
							$isRTE = $RTE && $this->isRTEforField('tt_content', $row, 'bodytext');
							$innerContent = '<div ' . ($row['_ORIG_uid'] ? ' class="ver-element"' : '') . '>' . $this->tt_content_drawItem($row, $isRTE) . '</div>';
							$singleElementHTML .= '<div class="t3-page-ce-body-inner">' . $innerContent . '</div>' . $this->tt_content_drawFooter($row);
							// NOTE: this is the end tag for <div class="t3-page-ce-body">
							// because of bad (historic) conception, starting tag has to be placed inside tt_content_drawHeader()
							$singleElementHTML .= '</div>';
							$statusHidden = $this->isDisabled('tt_content', $row) ? ' t3-page-ce-hidden' : '';
							$singleElementHTML = '<div class="t3-page-ce' . $statusHidden . '" id="element-tt_content-' . $row['uid'] . '">' . $singleElementHTML . '</div>';
							$singleElementHTML .= '<div class="t3-page-ce-dropzone" id="colpos-' . $key . '-' . 'page-' . $id .
								'-' . uniqid() . '">';
							// Add icon "new content element below"
							if (!$disableMoveAndNewButtons) {
								// New content element:
								if ($this->option_newWizard) {
									$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $row['pid'] . '&sys_language_uid=' . $row['sys_language_uid'] . '&colPos=' . $row['colPos'] . '&uid_pid=' . -$row['uid'] . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
								} else {
									$params = '&edit[tt_content][' . -$row['uid'] . ']=new';
									$onClick = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath);
								}
								$singleElementHTML .= '
									<div class="t3-page-ce-wrapper-new-ce">
										<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->getLL('newRecordHere', 1) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') . '</a>
									</div>
								';
							}
							if (!$this->tt_contentConfig['languageMode']) {
								$singleElementHTML .= '
								</div>';
							}
							$singleElementHTML .= '
							</div>';
							if ($this->defLangBinding && $this->tt_contentConfig['languageMode']) {
								$defLangBinding[$key][$lP][$row[$lP ? 'l18n_parent' : 'uid']] = $singleElementHTML;
							} else {
								$content[$key] .= $singleElementHTML;
							}
						} else {
							unset($rowArr[$rKey]);
						}
					}
					$content[$key] .= '</div>';
					// Add new-icon link, header:
					$newP = $this->newContentElementOnClick($id, $key, $lP);
					$colTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue('tt_content', 'colPos', $key);
					$tcaItems = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction('EXT:cms/classes/class.tx_cms_backendlayout.php:TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getColPosListItemsParsed', $id, $this);
					foreach ($tcaItems as $item) {
						if ($item[1] == $key) {
							$colTitle = $GLOBALS['LANG']->sL($item[0]);
						}
					}
					$head[$key] .= $this->tt_content_drawColHeader($colTitle, $this->doEdit && count($rowArr) ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc : '', $newP);
					$editUidList = '';
				}
				// For each column, fit the rendered content into a table cell:
				$out = '';
				if ($this->tt_contentConfig['languageMode']) {
					// in language mode process the content elements, but only fill $languageColumn. output will be generated later
					foreach ($cList as $k => $key) {
						$languageColumn[$key][$lP] = $head[$key] . $content[$key];
						if (!$this->defLangBinding) {
							$languageColumn[$key][$lP] .= '<br /><br />' . $this->newLanguageButton($this->getNonTranslatedTTcontentUids($defLanguageCount[$key], $id, $lP), $lP);
						}
					}
				} else {
					$backendLayoutRecord = $this->getBackendLayoutConfiguration();
					// GRID VIEW:
					// Initialize TS parser to parse config to array
					$parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
					$parser->parse($parser->checkIncludeLines($backendLayoutRecord['config']));
					$grid .= '<div class="t3-gridContainer"><table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%" class="t3-page-columns t3-gridTable">';
					// Add colgroups
					$colCount = intval($parser->setup['backend_layout.']['colCount']);
					$rowCount = intval($parser->setup['backend_layout.']['rowCount']);
					$grid .= '<colgroup>';
					for ($i = 0; $i < $colCount; $i++) {
						$grid .= '<col style="width:' . 100 / $colCount . '%"></col>';
					}
					$grid .= '</colgroup>';
					// Cycle through rows
					for ($row = 1; $row <= $rowCount; $row++) {
						$rowConfig = $parser->setup['backend_layout.']['rows.'][$row . '.'];
						if (!isset($rowConfig)) {
							continue;
						}
						$grid .= '<tr>';
						for ($col = 1; $col <= $colCount; $col++) {
							$columnConfig = $rowConfig['columns.'][$col . '.'];
							if (!isset($columnConfig)) {
								continue;
							}
							// Which tt_content colPos should be displayed inside this cell
							$columnKey = intval($columnConfig['colPos']);
							// Render the grid cell
							$colSpan = intval($columnConfig['colspan']);
							$rowSpan = intval($columnConfig['rowspan']);
							$grid .= '<td valign="top"' . ($colSpan > 0 ? ' colspan="' . $colSpan . '"' : '') . ($rowSpan > 0 ? ' rowspan="' . $rowSpan . '"' : '') . ' class="t3-gridCell t3-page-column t3-page-column-' . $columnKey . (!isset($columnConfig['colPos']) ? ' t3-gridCell-unassigned' : '') . (isset($columnConfig['colPos']) && !$head[$columnKey] ? ' t3-gridCell-restricted' : '') . ($colSpan > 0 ? ' t3-gridCell-width' . $colSpan : '') . ($rowSpan > 0 ? ' t3-gridCell-height' . $rowSpan : '') . '">';
							// Draw the pre-generated header with edit and new buttons if a colPos is assigned.
							// If not, a new header without any buttons will be generated.
							if (isset($columnConfig['colPos']) && $head[$columnKey]) {
								$grid .= $head[$columnKey] . $content[$columnKey];
							} elseif ($columnConfig['colPos']) {
								$grid .= $this->tt_content_drawColHeader($GLOBALS['LANG']->getLL('noAccess'), '', '');
							} else {
								$grid .= $this->tt_content_drawColHeader($GLOBALS['LANG']->getLL('notAssigned'), '', '');
							}
							$grid .= '</td>';
						}
						$grid .= '</tr>';
					}
					$out .= $grid . '</table></div>';
				}
				// CSH:
				$out .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'columns_multi', $GLOBALS['BACK_PATH']);
			}
			// If language mode, then make another presentation:
			// Notice that THIS presentation will override the value of $out! But it needs the code above to execute since $languageColumn is filled with content we need!
			if ($this->tt_contentConfig['languageMode']) {
				// Get language selector:
				$languageSelector = $this->languageSelector($id);
				// Reset out - we will make new content here:
				$out = '';
				// Traverse languages found on the page and build up the table displaying them side by side:
				$cCont = array();
				$sCont = array();
				foreach ($langListArr as $lP) {
					// Header:
					$cCont[$lP] = '
						<td valign="top" class="t3-page-lang-column">
							<h3>' . htmlspecialchars($this->tt_contentConfig['languageCols'][$lP]) . '</h3>
						</td>';

					// "View page" icon is added:
					$viewLink = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($this->id, $this->backPath, \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($this->id), '', '', ('&L=' . $lP))) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-view') . '</a>';
					// Language overlay page header:
					if ($lP) {
						list($lpRecord) = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages_language_overlay', 'pid', $id, 'AND sys_language_uid=' . intval($lP));
						\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('pages_language_overlay', $lpRecord);
						$params = '&edit[pages_language_overlay][' . $lpRecord['uid'] . ']=edit&overrideVals[pages_language_overlay][sys_language_uid]=' . $lP;
						$lPLabel = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages_language_overlay', $lpRecord), 'pages_language_overlay', $lpRecord['uid']) . $viewLink . ($GLOBALS['BE_USER']->check('tables_modify', 'pages_language_overlay') ? '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('edit', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>' : '') . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($lpRecord['title'], 20));
					} else {
						$lPLabel = $viewLink;
					}
					$sCont[$lP] = '
						<td nowrap="nowrap" class="t3-page-lang-column t3-page-lang-label">' . $lPLabel . '</td>';
				}
				// Add headers:
				$out .= '<tr>' . implode($cCont) . '</tr>';
				$out .= '<tr>' . implode($sCont) . '</tr>';
				// Traverse previously built content for the columns:
				foreach ($languageColumn as $cKey => $cCont) {
					$out .= '
					<tr>
						<td valign="top" class="t3-gridCell t3-page-lang-column"">' . implode(('</td>' . '
						<td valign="top" class="t3-gridCell t3-page-lang-column">'), $cCont) . '</td>
					</tr>';
					if ($this->defLangBinding) {
						// "defLangBinding" mode
						foreach ($defLanguageCount[$cKey] as $defUid) {
							$cCont = array();
							foreach ($langListArr as $lP) {
								$cCont[] = $defLangBinding[$cKey][$lP][$defUid] . '<br/>' . $this->newLanguageButton($this->getNonTranslatedTTcontentUids(array($defUid), $id, $lP), $lP);
							}
							$out .= '
							<tr>
								<td valign="top" class="t3-page-lang-column">' . implode(('</td>' . '
								<td valign="top" class="t3-page-lang-column">'), $cCont) . '</td>
							</tr>';
						}
						// Create spacer:
						$cCont = array();
						foreach ($langListArr as $lP) {
							$cCont[] = '&nbsp;';
						}
						$out .= '
						<tr>
							<td valign="top" class="t3-page-lang-column">' . implode(('</td>' . '
							<td valign="top" class="t3-page-lang-column">'), $cCont) . '</td>
						</tr>';
					}
				}
				// Finally, wrap it all in a table and add the language selector on top of it:
				$out = $languageSelector . '
					<div class="t3-lang-gridContainer">
						<table cellpadding="0" cellspacing="0" class="t3-page-langMode">
							' . $out . '
						</table>
					</div>';
				// CSH:
				$out .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'language_list', $GLOBALS['BACK_PATH']);
			}
		} else {
			// SINGLE column mode (columns shown beneath each other):
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

				// Select content records per column
				$contentRecordsPerColumn = $this->getContentRecordsPerColumn('tt_content', $id, array_values($cList), $showHidden . $showLanguage);
				// Traverse columns to display top-on-top
				foreach ($cList as $counter => $key) {
					$c = 0;
					$rowArr = $contentRecordsPerColumn[$key];
					$numberOfContentElementsInColumn = count($rowArr);
					$rowOut = '';
					// If it turns out that there are not content elements in the column, then display a big button which links directly to the wizard script:
					if ($this->doEdit && $this->option_showBigButtons && !intval($key) && $numberOfContentElementsInColumn == 0) {
						$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $id . '&colPos=' . intval($key) . '&sys_language_uid=' . $lP . '&uid_pid=' . $id . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
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
							if ($c != $numberOfContentElementsInColumn) {
								$rowOut .= '
								<tr>
									<td></td>
									<td colspan="3"><img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/stiblet_medium2.gif', 'width="468" height="1"') . ' class="c-divider" alt="" /></td>
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
							<td valign="top" colspan="3">' . $this->tt_content_drawColHeader(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue('tt_content', 'colPos', $key), ($this->doEdit && count($rowArr) ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc : ''), $newP) . $theNewButton . '<br /></td>
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
				$out .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'columns_single', $GLOBALS['BACK_PATH']);
			} else {
				$out = '<br/><br/>' . $GLOBALS['SOBE']->doc->icons(1) . 'Sorry, you cannot view a single language in this localization mode (Default Language Binding is enabled)<br/><br/>';
			}
		}
		// Add the big buttons to page:
		if ($this->option_showBigButtons) {
			$bArray = array();
			if (!$GLOBALS['SOBE']->current_sys_language) {
				if ($this->ext_CALC_PERMS & 2) {
					$bArray[0] = $GLOBALS['SOBE']->doc->t3Button(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[pages][' . $id . ']=edit', $this->backPath, ''), $GLOBALS['LANG']->getLL('editPageProperties'));
				}
			} else {
				if ($this->doEdit && $GLOBALS['BE_USER']->check('tables_modify', 'pages_language_overlay')) {
					list($languageOverlayRecord) = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages_language_overlay', 'pid', $id, 'AND sys_language_uid=' . intval($GLOBALS['SOBE']->current_sys_language));
					$bArray[0] = $GLOBALS['SOBE']->doc->t3Button(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[pages_language_overlay][' . $languageOverlayRecord['uid'] . ']=edit', $this->backPath, ''), $GLOBALS['LANG']->getLL('editPageProperties_curLang'));
				}
			}
			if ($this->ext_CALC_PERMS & 4 || $this->ext_CALC_PERMS & 2) {
				$bArray[1] = $GLOBALS['SOBE']->doc->t3Button('window.location.href=\'' . $this->backPath . 'move_el.php?table=pages&uid=' . $id . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';', $GLOBALS['LANG']->getLL('move_page'));
			}
			if ($this->ext_CALC_PERMS & 8) {
				$bArray[2] = $GLOBALS['SOBE']->doc->t3Button('window.location.href=\'' . $this->backPath . 'db_new.php?id=' . $id . '&pagesOnly=1&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';', $GLOBALS['LANG']->getLL('newPage2'));
			}
			if ($this->doEdit && $this->ext_function == 1) {
				$bArray[3] = $GLOBALS['SOBE']->doc->t3Button('window.location.href=\'db_new_content_el.php?id=' . $id . '&sys_language_uid=' . $GLOBALS['SOBE']->current_sys_language . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';', $GLOBALS['LANG']->getLL('newPageContent2'));
			}
			$out = '
				<table border="0" cellpadding="4" cellspacing="0" class="typo3-page-buttons">
					<tr>
						<td>' . implode('</td>
						<td>', $bArray) . '</td>
						<td>' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'button_panel', $GLOBALS['BACK_PATH']) . '</td>
					</tr>
				</table>
				<br />
				' . $out;
		}
		// Return content:
		return $out;
	}

	/**
	 * Get backend layout configuration
	 *
	 * @return array
	 */
	public function getBackendLayoutConfiguration() {
		$backendLayoutUid = $this->getSelectedBackendLayoutUid($this->id);
		if (!$backendLayoutUid) {
			return array(
				'config' => \TYPO3\CMS\Backend\View\BackendLayoutView::getDefaultColumnLayout()
			);
		}
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('backend_layout', intval($backendLayoutUid));
	}

	/**********************************
	 *
	 * Generic listing of items
	 *
	 **********************************/
	/**
	 * Creates a standard list of elements from a table.
	 *
	 * @param string $table Table name
	 * @param integer $id Page id.
	 * @param string $fList Comma list of fields to display
	 * @param boolean $icon If TRUE, icon is shown
	 * @param string $addWhere Additional WHERE-clauses.
	 * @return string HTML table
	 * @todo Define visibility
	 */
	public function makeOrdinaryList($table, $id, $fList, $icon = 0, $addWhere = '') {
		// Initialize
		$queryParts = $this->makeQueryArray($table, $id, $addWhere);
		$this->setTotalItems($queryParts);
		$dbCount = 0;
		// Make query for records if there were any records found in the count operation
		if ($this->totalItems) {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
			$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		}
		// If records were found, render the list
		if ($dbCount == 0) {
			return '';
		}
		// Set fields
		$out = '';
		$this->fieldArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', '__cmds__,' . $fList . ',__editIconLink__', TRUE);
		$theData = array();
		$theData = $this->headerFields($this->fieldArray, $table, $theData);
		// Title row
		$localizedTableTitle = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title'], 1);
		$out .= '<tr class="t3-row-header">' . '<td nowrap="nowrap" class="col-icon"></td>' . '<td nowrap="nowrap" colspan="' . (count($theData) - 2) . '"><span class="c-table">' . $localizedTableTitle . '</span> (' . $dbCount . ')</td>' . '<td nowrap="nowrap" class="col-icon"></td>' . '</tr>';
		// Column's titles
		if ($this->doEdit) {
			$theData['__cmds__'] = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick(('&edit[' . $table . '][' . $this->id . ']=new'), $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('new', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') . '</a>';
		}
		$out .= $this->addelement(1, '', $theData, ' class="c-headLine"', 15);
		// Render Items
		$this->eCounter = $this->firstElementNumber;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL($table, $row);
			if (is_array($row)) {
				list($flag, $code) = $this->fwd_rwd_nav();
				$out .= $code;
				if ($flag) {
					$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
					$Nrow = array();
					// Setting icons links
					if ($icon) {
						$Nrow['__cmds__'] = $this->getIcon($table, $row);
					}
					// Get values:
					$Nrow = $this->dataFields($this->fieldArray, $table, $row, $Nrow);
					// Attach edit icon
					if ($this->doEdit) {
						$Nrow['__editIconLink__'] = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('edit', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
					} else {
						$Nrow['__editIconLink__'] = $this->noEditIcon();
					}
					$out .= $this->addelement(1, '', $Nrow, 'class="db_list_normal"');
				}
				$this->eCounter++;
			}
		}
		// Wrap it all in a table:
		$out = '
			<!--
				Standard list of table "' . $table . '"
			-->
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
				' . $out . '
			</table>';
		return $out;
	}

	/**
	 * Adds content to all data fields in $out array
	 *
	 * @param array $fieldArr Array of fields to display. Each field name has a special feature which is that the field name can be specified as more field names. Eg. "field1,field2;field3". Field 2 and 3 will be shown in the same cell of the table separated by <br /> while field1 will have its own cell.
	 * @param string $table Table name
	 * @param array $row Record array
	 * @param array $out Array to which the data is added
	 * @return array $out array returned after processing.
	 * @see makeOrdinaryList()
	 * @todo Define visibility
	 */
	public function dataFields($fieldArr, $table, $row, $out = array()) {
		// Check table validity:
		if ($GLOBALS['TCA'][$table]) {
			$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
			// Traverse fields:
			foreach ($fieldArr as $fieldName) {
				if ($GLOBALS['TCA'][$table]['columns'][$fieldName]) {
					// Each field has its own cell (if configured in TCA)
					// If the column is a thumbnail column:
					if ($fieldName == $thumbsCol) {
						$out[$fieldName] = $this->thumbCode($row, $table, $fieldName);
					} else {
						// ... otherwise just render the output:
						$out[$fieldName] = nl2br(htmlspecialchars(trim(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($table, $fieldName, $row[$fieldName], 0, 0, 0, $row['uid']), 250))));
					}
				} else {
					// Each field is separated by <br /> and shown in the same cell (If not a TCA field, then explode the field name with ";" and check each value there as a TCA configured field)
					$theFields = explode(';', $fieldName);
					// Traverse fields, separated by ";" (displayed in a single cell).
					foreach ($theFields as $fName2) {
						if ($GLOBALS['TCA'][$table]['columns'][$fName2]) {
							$out[$fieldName] .= '<strong>' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['columns'][$fName2]['label'], 1) . '</strong>' . '&nbsp;&nbsp;' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($table, $fName2, $row[$fName2], 0, 0, 0, $row['uid']), 25)) . '<br />';
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
	 * @param array $fieldArr Field names
	 * @param string $table The table name
	 * @param array $out Array to which the headers are added.
	 * @return array $out returned after addition of the header fields.
	 * @see makeOrdinaryList()
	 * @todo Define visibility
	 */
	public function headerFields($fieldArr, $table, $out = array()) {
		foreach ($fieldArr as $fieldName) {
			$ll = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label'], 1);
			$out[$fieldName] = $ll ? $ll : '&nbsp;';
		}
		return $out;
	}

	/**
	 * Gets content records per column. This is required for correct workspace overlays.
	 *
	 * @param string $table Table to be queried
	 * @param integer $id Page Id to be used (not used at all, but part of the API, see $this->pidSelect)
	 * @param array $columns colPos values to be considered to be shown
	 * @return array Associative array for each column (colPos)
	 */
	protected function getContentRecordsPerColumn($table, $id, array $columns, $additionalWhereClause = '') {
		$columns = array_map('intval', $columns);
		$contentRecordsPerColumn = array_fill_keys($columns, array());

		$queryParts = $this->makeQueryArray('tt_content', $id, 'AND colPos IN (' . implode(',', $columns) . ')' . $additionalWhereClause);
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		// Traverse any selected elements and render their display code:
		$rowArr = $this->getResult($result);

		foreach ($rowArr as $record) {
			$columnValue = $record['colPos'];
			$contentRecordsPerColumn[$columnValue][] = $record;
		}

		return $contentRecordsPerColumn;
	}

	/**********************************
	 *
	 * Additional functions; Pages
	 *
	 **********************************/
	/**
	 * Adds pages-rows to an array, selecting recursively in the page tree.
	 *
	 * @param array $theRows Array which will accumulate page rows
	 * @param integer $pid Pid to select from
	 * @param string $qWhere Query-where clause
	 * @param string $treeIcons Prefixed icon code.
	 * @param integer $depth Depth (decreasing)
	 * @return array $theRows, but with added rows.
	 * @todo Define visibility
	 */
	public function pages_getTree($theRows, $pid, $qWhere, $treeIcons, $depth) {
		$depth--;
		if ($depth >= 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'pid=' . intval($pid) . $qWhere, '', 'sorting');
			$c = 0;
			$rc = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('pages', $row);
				if (is_array($row)) {
					$c++;
					$row['treeIcons'] = $treeIcons . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/join' . ($rc == $c ? 'bottom' : '') . '.gif'), 'width="18" height="16"') . ' alt="" />';
					$theRows[] = $row;
					// Get the branch
					$spaceOutIcons = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . ($rc == $c ? 'blank.gif' : 'line.gif')), 'width="18" height="16"') . ' alt="" />';
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
	 * @param array $row Record array
	 * @param array $fieldArr Field list
	 * @return string HTML for the item
	 * @todo Define visibility
	 */
	public function pages_drawItem($row, $fieldArr) {
		// Initialization
		$theIcon = $this->getIcon('pages', $row);
		// Preparing and getting the data-array
		$theData = array();
		foreach ($fieldArr as $field) {
			switch ($field) {
			case 'title':
				$red = $this->plusPages[$row['uid']] ? '<font color="red"><strong>+&nbsp;</strong></font>' : '';
				$pTitle = htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue('pages', $field, $row[$field], 20));
				if ($red) {
					$pTitle = '<a href="' . htmlspecialchars(($this->script . '?id=' . $row['uid'])) . '">' . $pTitle . '</a>';
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
					$eI = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath, '')) . '" title="' . $GLOBALS['LANG']->getLL('editThisPage', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
				} else {
					$eI = '';
				}
				$theData[$field] = '<span align="right">' . $row['uid'] . $eI . '</span>';
				break;
			default:
				if (substr($field, 0, 6) == 'table_') {
					$f2 = substr($field, 6);
					if ($GLOBALS['TCA'][$f2]) {
						$c = $this->numberOfRecords($f2, $row['uid']);
						$theData[$field] = '&nbsp;&nbsp;' . ($c ? $c : '');
					}
				} else {
					$theData[$field] = '&nbsp;&nbsp;' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue('pages', $field, $row[$field]));
				}
				break;
			}
		}
		$this->addElement_tdParams['title'] = $row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : '';
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
	 * @param string $colName Column name
	 * @param string $editParams Edit params (Syntax: &edit[...] for alt_doc.php)
	 * @param string $newParams New element params (Syntax: &edit[...] for alt_doc.php) OBSOLETE
	 * @return string HTML table
	 * @todo Define visibility
	 */
	public function tt_content_drawColHeader($colName, $editParams, $newParams) {
		$icons = '';
		// Create command links:
		if ($this->tt_contentConfig['showCommands']) {
			// Edit whole of column:
			if ($editParams) {
				$icons .= '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($editParams, $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('editColumn', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
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
	 * Draw the footer for a single tt_content element
	 *
	 * @param array $row Record array
	 * @return string HTML of the footer
	 */
	protected function tt_content_drawFooter(array $row) {
		$content = '';
		// Get processed values:
		$info = array();
		$this->getProcessedValue('tt_content', 'starttime,endtime,fe_group,spaceBefore,spaceAfter', $row, $info);
		// Display info from records fields:
		if (count($info)) {
			$content = '<div class="t3-page-ce-info">
				' . implode('<br />', $info) . '
				</div>';
		}
		// Wrap it
		if (!empty($content)) {
			$content = '<div class="t3-page-ce-footer">' . $content . '</div>';
		}
		return $content;
	}

	/**
	 * Draw the header for a single tt_content element
	 *
	 * @param array $row Record array
	 * @param integer $space Amount of pixel space above the header.
	 * @param boolean $disableMoveAndNewButtons If set the buttons for creating new elements and moving up and down are not shown.
	 * @param boolean $langMode If set, we are in language mode and flags will be shown for languages
	 * @param boolean $dragDropEnabled If set the move button must be hidden
	 * @return string HTML table with the record header.
	 * @todo Define visibility
	 */
	public function tt_content_drawHeader($row, $space = 0, $disableMoveAndNewButtons = FALSE, $langMode = FALSE, $dragDropEnabled = FALSE) {
		$out = '';
		// If show info is set...;
		if ($this->tt_contentConfig['showInfo'] && $GLOBALS['BE_USER']->recordEditAccessInternals('tt_content', $row)) {
			// Render control panel for the element:
			if ($this->tt_contentConfig['showCommands'] && $this->doEdit) {
				// Edit content element:
				$params = '&edit[tt_content][' . $this->tt_contentData['nextThree'][$row['uid']] . ']=edit';
				$out .= '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath, (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI') . '#element-tt_content-' . $row['uid']))) . '" title="' . htmlspecialchars(($this->nextThree > 1 ? sprintf($GLOBALS['LANG']->getLL('nextThree'), $this->nextThree) : $GLOBALS['LANG']->getLL('edit'))) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
				// Hide element:
				$hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
				if ($hiddenField && $GLOBALS['TCA']['tt_content']['columns'][$hiddenField] && (!$GLOBALS['TCA']['tt_content']['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', 'tt_content:' . $hiddenField))) {
					if ($row[$hiddenField]) {
						$params = '&data[tt_content][' . ($row['_ORIG_uid'] ? $row['_ORIG_uid'] : $row['uid']) . '][' . $hiddenField . ']=0';
						$out .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" title="' . $GLOBALS['LANG']->getLL('unHide', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-unhide') . '</a>';
					} else {
						$params = '&data[tt_content][' . ($row['_ORIG_uid'] ? $row['_ORIG_uid'] : $row['uid']) . '][' . $hiddenField . ']=1';
						$out .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" title="' . $GLOBALS['LANG']->getLL('hide', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-hide') . '</a>';
					}
				}
				// Delete
				$params = '&cmd[tt_content][' . $row['uid'] . '][delete]=1';
				$confirm = $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('deleteWarning') . \TYPO3\CMS\Backend\Utility\BackendUtility::translationCount('tt_content', $row['uid'], (' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord'))));
				$out .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" onclick="' . htmlspecialchars(('return confirm(' . $confirm . ');')) . '" title="' . $GLOBALS['LANG']->getLL('deleteItem', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete') . '</a>';
				if (!$disableMoveAndNewButtons) {
					$moveButtonContent = '';
					$displayMoveButtons = FALSE;
					// Move element up:
					if ($this->tt_contentData['prev'][$row['uid']]) {
						$params = '&cmd[tt_content][' . $row['uid'] . '][move]=' . $this->tt_contentData['prev'][$row['uid']];
						$moveButtonContent .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" title="' . $GLOBALS['LANG']->getLL('moveUp', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-move-up') . '</a>';
						if (!$dragDropEnabled) {
							$displayMoveButtons = TRUE;
						}
					} else {
						$moveButtonContent .= \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('empty-empty');
					}
					// Move element down:
					if ($this->tt_contentData['next'][$row['uid']]) {
						$params = '&cmd[tt_content][' . $row['uid'] . '][move]= ' . $this->tt_contentData['next'][$row['uid']];
						$moveButtonContent .= '<a href="' . htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)) . '" title="' . $GLOBALS['LANG']->getLL('moveDown', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-move-down') . '</a>';
						if (!$dragDropEnabled) {
							$displayMoveButtons = TRUE;
						}
					} else {
						$moveButtonContent .= \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('empty-empty');
					}
					if ($displayMoveButtons) {
						$out .= '<span class="t3-page-ce-icons-move">' . $moveButtonContent . '</span>';
					}
				}
			}
		}
		$additionalIcons = array();
		$additionalIcons[] = $this->getIcon('tt_content', $row) . ' ';
		$additionalIcons[] = $langMode ? $this->languageFlag($row['sys_language_uid'], FALSE) : '';
		// Get record locking status:
		if ($lockInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::isRecordLocked('tt_content', $row['uid'])) {
			$additionalIcons[] = '<a href="#" onclick="' . htmlspecialchars(('alert(' . $GLOBALS['LANG']->JScharCode($lockInfo['msg']) . ');return false;')) . '" title="' . htmlspecialchars($lockInfo['msg']) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-warning-in-use') . '</a>';
		}
		// Call stats information hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
			$_params = array('tt_content', $row['uid'], &$row);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
				$additionalIcons[] = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		// Wrap the whole header
		// NOTE: end-tag for <div class="t3-page-ce-body"> is in getTable_tt_content()
		return '<h4 class="t3-page-ce-header">
					<div class="t3-row-header">
					<span class="ce-icons-left">' . implode('', $additionalIcons) . '</span>
					<span class="ce-icons">
					' . $out . '
					</span></div>
				</h4>
				<div class="t3-page-ce-body">';
	}

	/**
	 * Draws the preview content for a content element
	 *
	 * @param string $row Content element
	 * @param boolean $isRTE Set if the RTE link can be created.
	 * @return string HTML
	 * @todo Define visibility
	 */
	public function tt_content_drawItem($row, $isRTE = FALSE) {
		$out = '';
		$outHeader = '';
		// Make header:
		if ($row['header']) {
			$infoArr = array();
			$this->getProcessedValue('tt_content', 'header_position,header_layout,header_link', $row, $infoArr);
			// If header layout is set to 'hidden', display an accordant note:
			if ($row['header_layout'] == 100) {
				$hiddenHeaderNote = ' <em>[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.hidden', TRUE) . ']</em>';
			}
			$outHeader = ($row['date'] ? htmlspecialchars(($this->itemLabels['date'] . ' ' . \TYPO3\CMS\Backend\Utility\BackendUtility::date($row['date']))) . '<br />' : '') . '<strong>' . $this->linkEditContent($this->renderText($row['header']), $row) . $hiddenHeaderNote . '</strong><br />';
		}
		// Make content:
		$infoArr = array();
		$drawItem = TRUE;
		// Hook: Render an own preview of a record
		$drawItemHooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'];
		if (is_array($drawItemHooks)) {
			foreach ($drawItemHooks as $hookClass) {
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($hookClass);
				if (!$hookObject instanceof \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface TYPO3\\CMS\\Backend\\View\\PageLayoutViewDrawItemHookInterface', 1218547409);
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
					$out .= $this->thumbCode($row, 'tt_content', 'media') . '<br />';
				}
				break;
			case 'multimedia':
				if ($row['multimedia']) {
					$out .= $this->renderText($row['multimedia']) . '<br />';
					$out .= $this->renderText($row['parameters']) . '<br />';
				}
				break;
			case 'menu':
				if ($row['pages']) {
					$out .= $this->linkEditContent($row['pages'], $row) . '<br />';
				}
				break;
			case 'shortcut':
				if (!empty($row['records'])) {
					$shortcutContent = array();
					$recordList = explode(',', $row['records']);
					foreach ($recordList as $recordIdentifier) {
						$split = \TYPO3\CMS\Backend\Utility\BackendUtility::splitTable_Uid($recordIdentifier);
						$tableName = empty($split[0]) ? 'tt_content' : $split[0];
						$shortcutRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($tableName, $split[1]);
						if (is_array($shortcutRecord)) {
							$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($tableName, $shortcutRecord);
							$onClick = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($icon, $tableName, $shortcutRecord['uid'], 1, '', '+copy,info,edit,view', TRUE);
							$shortcutContent[] = '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $icon . '</a>' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($tableName, $shortcutRecord));
						}
					}
					$out .= implode('<br />', $shortcutContent) . '<br />';
				}
				break;
			case 'list':
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
						$hookOut .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
					}
				}
				if (strcmp($hookOut, '')) {
					$out .= $hookOut;
				} elseif (!empty($row['list_type'])) {
					$label = \TYPO3\CMS\Backend\Utility\BackendUtility::getLabelFromItemlist('tt_content', 'list_type', $row['list_type']);
					if (!empty($label)) {
						$out .=  '<strong>' . $GLOBALS['LANG']->sL($label, TRUE) . '</strong><br />';
					} else {
						$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'), $row['list_type']);
						$out .= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', htmlspecialchars($message), '', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING)->render();
					}
				} elseif (!empty($row['select_key'])) {
					$out .= $GLOBALS['LANG']->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('tt_content', 'select_key'), 1) . ' ' . $row['select_key'] . '<br />';
				} else {
					$out .= '<strong>' . $GLOBALS['LANG']->getLL('noPluginSelected') . '</strong>';
				}
				$out .= $GLOBALS['LANG']->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getLabelFromItemlist('tt_content', 'pages', $row['pages']), 1) . '<br />';
				break;
			case 'script':
				$out .= $GLOBALS['LANG']->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('tt_content', 'select_key'), 1) . ' ' . $row['select_key'] . '<br />';
				$out .= '<br />' . $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
				$out .= '<br />' . $this->linkEditContent($this->renderText($row['imagecaption']), $row) . '<br />';
				break;
			default:
				$contentType = $this->CType_labels[$row['CType']];

				if (isset($contentType)) {
					$out .= '<strong>' . htmlspecialchars($contentType) . '</strong><br />';
					if ($row['bodytext']) {
						$out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
					}
				} else {
					$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'), $row['CType']);
					$out .= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', htmlspecialchars($message), '', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING)->render();
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
	 * @param array $defLanguageCount Numeric array with uids of tt_content elements in the default language
	 * @param integer $id Page pid
	 * @param integer $lP Sys language UID
	 * @return array Modified $defLanguageCount
	 * @todo Define visibility
	 */
	public function getNonTranslatedTTcontentUids($defLanguageCount, $id, $lP) {
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
	 * @param array $defLanguageCount Numeric array with uids of tt_content elements in the default language
	 * @param integer $lP Sys language UID
	 * @return string "Copy languages" button, if available.
	 * @todo Define visibility
	 */
	public function newLanguageButton($defLanguageCount, $lP) {
		if ($this->doEdit && count($defLanguageCount) && $lP) {
			$params = '';
			foreach ($defLanguageCount as $uidVal) {
				$params .= '&cmd[tt_content][' . $uidVal . '][localize]=' . $lP;
			}
			// Copy for language:
			$onClick = 'window.location.href=\'' . $GLOBALS['SOBE']->doc->issueCommand($params) . '\'; return false;';
			$theNewButton = $GLOBALS['SOBE']->doc->t3Button($onClick, $GLOBALS['LANG']->getLL('newPageContent_copyForLang') . ' [' . count($defLanguageCount) . ']');
			return $theNewButton;
		}
	}

	/**
	 * Creates onclick-attribute content for a new content element
	 *
	 * @param integer $id Page id where to create the element.
	 * @param integer $colPos Preset: Column position value
	 * @param integer $sys_language Preset: Sys langauge value
	 * @return string String for onclick attribute.
	 * @see getTable_tt_content()
	 * @todo Define visibility
	 */
	public function newContentElementOnClick($id, $colPos, $sys_language) {
		if ($this->option_newWizard) {
			$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $id . '&colPos=' . $colPos . '&sys_language_uid=' . $sys_language . '&uid_pid=' . $id . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
		} else {
			$onClick = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[tt_content][' . $id . ']=new&defVals[tt_content][colPos]=' . $colPos . '&defVals[tt_content][sys_language_uid]=' . $sys_language, $this->backPath);
		}
		return $onClick;
	}

	/**
	 * Will create a link on the input string and possible a big button after the string which links to editing in the RTE
	 * Used for content element content displayed so the user can click the content / "Edit in Rich Text Editor" button
	 *
	 * @param string $str String to link. Must be prepared for HTML output.
	 * @param array $row The row.
	 * @return string If the whole thing was editable ($this->doEdit) $str is return with link around. Otherwise just $str.
	 * @see getTable_tt_content()
	 * @todo Define visibility
	 */
	public function linkEditContent($str, $row) {
		$addButton = '';
		$onClick = '';
		if ($this->doEdit && $GLOBALS['BE_USER']->recordEditAccessInternals('tt_content', $row)) {
			// Setting onclick action for content link:
			$onClick = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[tt_content][' . $row['uid'] . ']=edit', $this->backPath);
		}
		// Return link
		return $onClick ? '<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->getLL('edit', 1) . '">' . $str . '</a>' . $addButton : $str;
	}

	/**
	 * Adds a button to edit the row in RTE wizard
	 *
	 * @param array $row The row of tt_content element
	 * @return string Button to click if you want to edit in RTE wizard.
	 * @todo Define visibility
	 */
	public function linkRTEbutton($row) {
		$params = array();
		$params['table'] = 'tt_content';
		$params['uid'] = $row['uid'];
		$params['pid'] = $row['pid'];
		$params['field'] = 'bodytext';
		$params['returnUrl'] = \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript();
		$RTEonClick = 'window.location.href=\'' . $this->backPath . 'wizard_rte.php?' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', array('P' => $params)) . '\';return false;';
		$addButton = $this->option_showBigButtons && $this->doEdit ? $GLOBALS['SOBE']->doc->t3Button($RTEonClick, $GLOBALS['LANG']->getLL('editInRTE')) : '';
		return $addButton;
	}

	/**
	 * Make selector box for creating new translation in a language
	 * Displays only languages which are not yet present for the current page and
	 * that are not disabled with page TS.
	 *
	 * @param integer $id Page id for which to create a new language (pages_language_overlay record)
	 * @return string <select> HTML element (if there were items for the box anyways...)
	 * @see getTable_tt_content()
	 * @todo Define visibility
	 */
	public function languageSelector($id) {
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
			if (count($langSelItems) > 1 && !$GLOBALS['BE_USER']->user['admin'] && strlen($GLOBALS['BE_USER']->groupData['allowed_languages'])) {
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
			$modSharedTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($id, 'mod.SHARED');
			$disableLanguages = isset($modSharedTSconfig['properties']['disableLanguages']) ? \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $modSharedTSconfig['properties']['disableLanguages'], 1) : array();
			if (count($langSelItems) && count($disableLanguages)) {
				foreach ($disableLanguages as $language) {
					if ($language != 0 && isset($langSelItems[$language])) {
						unset($langSelItems[$language]);
					}
				}
			}
			// If any languages are left, make selector:
			if (count($langSelItems) > 1) {
				$onChangeContent = 'window.location.href=\'' . $this->backPath . 'alt_doc.php?&edit[pages_language_overlay][' . $id . ']=new&overrideVals[pages_language_overlay][doktype]=' . (int) $this->pageRecord['doktype'] . '&overrideVals[pages_language_overlay][sys_language_uid]=\'+this.options[this.selectedIndex].value+\'&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\'';
				return $GLOBALS['LANG']->getLL('new_language', 1) . ': <select name="createNewLanguage" onchange="' . htmlspecialchars($onChangeContent) . '">
						' . implode('', $langSelItems) . '
					</select><br /><br />';
			}
		}
	}

	/**
	 * Traverse the result pointer given, adding each record to array and setting some internal values at the same time.
	 *
	 * @param boolean|\mysqli_result|object $result MySQLi result object / DBAL object
	 * @param string $table Table name defaulting to tt_content
	 * @return array The selected rows returned in this array.
	 * @todo Define visibility
	 */
	public function getResult($result, $table = 'tt_content') {
		// Initialize:
		$editUidList = '';
		$recs = array();
		$nextTree = $this->nextThree;
		$c = 0;
		$output = array();
		// Traverse the result:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL($table, $row, -99, TRUE);
			if ($row) {
				// Add the row to the array:
				$output[] = $row;
				// Set an internal register:
				$recs[$c] = $row['uid'];
				// Create the list of the next three ids (for editing links...)
				for ($a = 0; $a < $nextTree; $a++) {
					if (isset($recs[$c - $a]) && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->tt_contentData['nextThree'][$recs[$c - $a]], $row['uid'])) {
						$this->tt_contentData['nextThree'][$recs[$c - $a]] .= $row['uid'] . ',';
					}
				}
				// Set next/previous ids:
				if (isset($recs[$c - 1])) {
					if (isset($recs[$c - 2])) {
						$this->tt_contentData['prev'][$row['uid']] = -$recs[($c - 2)];
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

	/********************************
	 *
	 * Various helper functions
	 *
	 ********************************/
	/**
	 * Counts and returns the number of records on the page with $pid
	 *
	 * @param string $table Table name
	 * @param integer $pid Page id
	 * @return integer Number of records.
	 * @todo Define visibility
	 */
	public function numberOfRecords($table, $pid) {
		if ($GLOBALS['TCA'][$table]) {
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $table, 'pid=' . intval($pid) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table) . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause($table));
		}
		return intval($count);
	}

	/**
	 * Processing of larger amounts of text (usually from RTE/bodytext fields) with word wrapping etc.
	 *
	 * @param string $input Input string
	 * @return string Output string
	 * @todo Define visibility
	 */
	public function renderText($input) {
		$input = strip_tags($input);
		$input = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($input, 1500);
		return nl2br(htmlspecialchars(trim($this->wordWrapper($input)), ENT_QUOTES, 'UTF-8', FALSE));
	}

	/**
	 * Creates the icon image tag for record from table and wraps it in a link which will trigger the click menu.
	 *
	 * @param string $table Table name
	 * @param array $row Record array
	 * @return string HTML for the icon
	 * @todo Define visibility
	 */
	public function getIcon($table, $row) {
		// Initialization
		$alttext = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordIconAltText($row, $table);
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $row, array('title' => $alttext));
		$this->counter++;
		// The icon with link
		if ($GLOBALS['BE_USER']->recordEditAccessInternals($table, $row)) {
			$icon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($icon, $table, $row['uid']);
		}
		return $icon;
	}

	/**
	 * Creates processed values for all fieldnames in $fieldList based on values from $row array.
	 * The result is 'returned' through $info which is passed as a reference
	 *
	 * @param string $table Table name
	 * @param string $fieldList Commalist of fields.
	 * @param array $row Record from which to take values for processing.
	 * @param array $info Array to which the processed values are added.
	 * @return void
	 * @todo Define visibility
	 */
	public function getProcessedValue($table, $fieldList, array $row, array &$info) {
		// Splitting values from $fieldList
		$fieldArr = explode(',', $fieldList);
		// Traverse fields from $fieldList
		foreach ($fieldArr as $field) {
			if ($row[$field]) {
				$info[] = '<strong>' . htmlspecialchars($this->itemLabels[$field]) . '</strong> ' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($table, $field, $row[$field]));
			}
		}
	}

	/**
	 * Returns TRUE, if the record given as parameters is NOT visible based on hidden/starttime/endtime (if available)
	 *
	 * @param string $table Tablename of table to test
	 * @param array $row Record row.
	 * @return boolean Returns TRUE, if disabled.
	 * @todo Define visibility
	 */
	public function isDisabled($table, $row) {
		if ($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] && $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']] || $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'] && $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime']] > $GLOBALS['EXEC_TIME'] || $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime'] && $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime']] && $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime']] < $GLOBALS['EXEC_TIME']) {
			return TRUE;
		}
	}

	/**
	 * Will perform "word-wrapping" on the input string. What it does is to split by space or linebreak, then find any word longer than $max and if found, a hyphen is inserted.
	 * Works well on normal texts, little less well when HTML is involved (since much HTML will have long strings that will be broken).
	 *
	 * @param string $content Content to word-wrap.
	 * @param integer $max Max number of chars in a word before it will be wrapped.
	 * @param string $char Character to insert when wrapping.
	 * @return string Processed output.
	 * @todo Define visibility
	 */
	public function wordWrapper($content, $max = 50, $char = ' -') {
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
	 * @param string $label Label key from LOCAL_LANG
	 * @return string IMG tag for icon.
	 * @todo Define visibility
	 */
	public function noEditIcon($label = 'noEditItems') {
		return \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-edit-read-only', array('title' => $GLOBALS['LANG']->getLL($label, TRUE)));
	}

	/**
	 * Function, which fills in the internal array, $this->allowedTableNames with all tables to which the user has access. Also a set of standard tables (pages, static_template, sys_filemounts, etc...) are filtered out. So what is left is basically all tables which makes sense to list content from.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function cleanTableNames() {
		// Get all table names:
		$tableNames = array_flip(array_keys($GLOBALS['TCA']));
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
	 * Checking if the RTE is available/enabled for a certain table/field and if so, it returns TRUE.
	 * Used to determine if the RTE button should be displayed.
	 *
	 * @param string $table Table name
	 * @param array $row Record row (needed, if there are RTE dependencies based on other fields in the record)
	 * @param string $field Field name
	 * @return boolean Returns TRUE if the rich text editor would be enabled/available for the field name specified.
	 * @todo Define visibility
	 */
	public function isRTEforField($table, $row, $field) {
		$specConf = $this->getSpecConfForField($table, $row, $field);
		$p = \TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
		if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']])) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid($table, $row);
			list($tscPID, $thePidValue) = \TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpid($table, $row['uid'], $row['pid']);
			// If the pid-value is not negative (that is, a pid could NOT be fetched)
			if ($thePidValue >= 0) {
				$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($tscPID));
				$RTEtypeVal = \TYPO3\CMS\Backend\Utility\BackendUtility::getTCAtypeValue($table, $row);
				$thisConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $table, $field, $RTEtypeVal);
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
	 * @param string $table Table name
	 * @param array $row Record array
	 * @param string $field Field name
	 * @return array Spec. conf (if available)
	 * @access private
	 * @see isRTEforField()
	 * @todo Define visibility
	 */
	public function getSpecConfForField($table, $row, $field) {
		// Get types-configuration for the record:
		$types_fieldConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getTCAtypes($table, $row);
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
	 * @param array $rec Page record
	 * @param boolean $edit If set, there will be shown an edit icon, linking to editing of the page properties.
	 * @return string HTML for the box.
	 * @deprecated and unused since 6.0, will be removed two versions later
	 * @todo Define visibility
	 */
	public function getPageInfoBox($rec, $edit = 0) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		// If editing of the page properties is allowed:
		if ($edit) {
			$params = '&edit[pages][' . $rec['uid'] . ']=edit';
			$editIcon = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('edit', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
		} else {
			$editIcon = $this->noEditIcon('noEditPage');
		}
		// Setting page icon, link, title:
		$outPutContent = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $rec, array('title' => \TYPO3\CMS\Backend\Utility\BackendUtility::titleAttribForPages($rec))) . $editIcon . '&nbsp;' . htmlspecialchars($rec['title']);
		// Init array where infomation is accumulated as label/value pairs.
		$lines = array();
		// Owner user/group:
		if ($this->pI_showUser) {
			// User:
			$users = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames('username,usergroup,usergroup_cached_list,uid,realName');
			$groupArray = explode(',', $GLOBALS['BE_USER']->user['usergroup_cached_list']);
			$users = \TYPO3\CMS\Backend\Utility\BackendUtility::blindUserNames($users, $groupArray);
			$lines[] = array($GLOBALS['LANG']->getLL('pI_crUser') . ':', htmlspecialchars($users[$rec['cruser_id']]['username']) . ' (' . $users[$rec['cruser_id']]['realName'] . ')');
		}
		// Created:
		$lines[] = array(
			$GLOBALS['LANG']->getLL('pI_crDate') . ':',
			\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($rec['crdate']) . ' (' . \TYPO3\CMS\Backend\Utility\BackendUtility::calcAge(($GLOBALS['EXEC_TIME'] - $rec['crdate']), $this->agePrefixes) . ')'
		);
		// Last change:
		$lines[] = array(
			$GLOBALS['LANG']->getLL('pI_lastChange') . ':',
			\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($rec['tstamp']) . ' (' . \TYPO3\CMS\Backend\Utility\BackendUtility::calcAge(($GLOBALS['EXEC_TIME'] - $rec['tstamp']), $this->agePrefixes) . ')'
		);
		// Last change of content:
		if ($rec['SYS_LASTCHANGED']) {
			$lines[] = array(
				$GLOBALS['LANG']->getLL('pI_lastChangeContent') . ':',
				\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($rec['SYS_LASTCHANGED']) . ' (' . \TYPO3\CMS\Backend\Utility\BackendUtility::calcAge(($GLOBALS['EXEC_TIME'] - $rec['SYS_LASTCHANGED']), $this->agePrefixes) . ')'
			);
		}
		// Spacer:
		$lines[] = '';
		// Display contents of certain page fields, if any value:
		$dfields = explode(',', 'alias,target,hidden,starttime,endtime,fe_group,no_cache,cache_timeout,newUntil,lastUpdated,subtitle,keywords,description,abstract,author,author_email');
		foreach ($dfields as $fV) {
			if ($rec[$fV]) {
				$lines[] = array($GLOBALS['LANG']->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel('pages', $fV)), \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue('pages', $fV, $rec[$fV]));
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
	 * @param integer $id Page id from which we are listing records (the function will look up if there are records on the page)
	 * @return string HTML output.
	 * @todo Define visibility
	 */
	public function getTableMenu($id) {
		// Initialize:
		$this->activeTables = array();
		$theTables = array('tt_content');
		// External tables:
		if (is_array($this->externalTables)) {
			$theTables = array_unique(array_merge($theTables, array_keys($this->externalTables)));
		}
		// Traverse tables to check:
		foreach ($theTables as $tName) {
			// Check access and whether the proper extensions are loaded:
			if ($GLOBALS['BE_USER']->check('tables_select', $tName) && (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($tName) || \TYPO3\CMS\Core\Utility\GeneralUtility::inList('fe_users,tt_content', $tName) || isset($this->externalTables[$tName]))) {
				// Make query to count records from page:
				$c = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $tName, 'pid=' . intval($id) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tName) . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause($tName));
				// If records were found (or if "tt_content" is the table...):
				if ($c || \TYPO3\CMS\Core\Utility\GeneralUtility::inList('tt_content', $tName)) {
					// Add row to menu:
					$out .= '
					<td><a href="#' . $tName . '"></a>' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($tName, array(), array('title' => $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tName]['ctrl']['title'], 1))) . '</td>';
					// ... and to the internal array, activeTables we also add table icon and title (for use elsewhere)
					$this->activeTables[$tName] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($tName, array(), array('title' => ($GLOBALS['LANG']->sL($GLOBALS['TCA'][$tName]['ctrl']['title'], 1) . ': ' . $c . ' ' . $GLOBALS['LANG']->getLL('records', 1)))) . '&nbsp;' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tName]['ctrl']['title'], 1);
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
	 * @param string $content Input string
	 * @param boolean $fillEmptyContent If TRUE, empty tags will be filled with the first attribute of the tag before.
	 * @return string Input string with all HTML and PHP tags stripped
	 * @deprecated since TYPO3 4.6, deprecationLog since 6.0, will be removed two versions later - use php-function strip_tags instead
	 * @todo Define visibility
	 */
	public function strip_tags($content, $fillEmptyContent = FALSE) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		if ($fillEmptyContent && strstr($content, '><')) {
			$content = preg_replace('/(<[^ >]* )([^ >]*)([^>]*>)(<\\/[^>]*>)/', '$1$2$3$2$4', $content);
		}
		$content = preg_replace('/<br.?\\/?>/', LF, $content);
		return strip_tags($content);
	}

}

?>