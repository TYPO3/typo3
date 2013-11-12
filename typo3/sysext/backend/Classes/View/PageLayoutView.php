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

use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Lang\LanguageService;

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

	// If TRUE, elements will have edit icons (probably this is whether the user has permission to edit the page content). Set externally.
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

	/**
	 * Used to store the RTE setup of a particular page
	 *
	 * @var array
	 */
	protected $rteSetup = array();

	/**
	 * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
	 */
	protected $clipboard;

	/**
	 * @var array
	 */
	protected $plusPages = array();

	/**
	 * User permissions
	 *
	 * @var integer
	 */
	public $ext_CALC_PERMS;

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
				default:
					return '';
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
		$type = $this->getPageLayoutController()->MOD_SETTINGS[$table];
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
		$delClause = BackendUtility::deleteClause('pages') . ' AND ' . $this->getBackendUser()->getPagePermsClause(1);
		// Select current page:
		if (!$id) {
			// The root has a pseudo record in pageinfo...
			$row = $this->getPageLayoutController()->pageinfo;
		} else {
			$result = $this->getDatabase()->exec_SELECTquery('*', 'pages', 'uid=' . (int)$id . $delClause);
			$row = $this->getDatabase()->sql_fetch_assoc($result);
			BackendUtility::workspaceOL('pages', $row);
		}
		// If there was found a page:
		if (is_array($row)) {
			// Select which fields to show:
			$pKey = $this->getPageLayoutController()->MOD_SETTINGS['pages'];
			switch ($pKey) {
				case 1:
					$this->fieldArray = array('title','uid') + array_keys($this->cleanTableNames());
					break;
				case 2:
					$this->fieldArray = array(
						'title',
						'uid',
						'lastUpdated',
						'newUntil',
						'no_cache',
						'cache_timeout',
						'php_tree_stop',
						'TSconfig',
						'storage_pid',
						'is_siteroot',
						'fe_login_mode'
					);
					break;
				default:
					$this->fieldArray = array(
						'title',
						'uid',
						'alias',
						'starttime',
						'endtime',
						'fe_group',
						'target',
						'url',
						'shortcut',
						'shortcut_mode'
					);
			}
			// Getting select-depth:
			$depth = (int)$this->getPageLayoutController()->MOD_SETTINGS['pages_levels'];
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
				$theRows = $this->pages_getTree($theRows, $row['uid'], $delClause . BackendUtility::versioningPlaceholderClause('pages'), '', $depth);
				if ($this->getBackendUser()->doesUserHaveAccess($row, 2)) {
					$editUids[] = $row['uid'];
				}
				$out .= $this->pages_drawItem($row, $this->fieldArray);
				// Traverse all pages selected:
				foreach ($theRows as $sRow) {
					if ($this->getBackendUser()->doesUserHaveAccess($sRow, 2)) {
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
					$iTitle = sprintf(
						$this->getLanguageService()->getLL('editThisColumn'),
						rtrim(trim($this->getLanguageService()->sL(BackendUtility::getItemLabel('pages', $field))), ':')
					);
					$eI = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, ''))
						. '" title="' . htmlspecialchars($iTitle) . '">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
				} else {
					$eI = '';
				}
				switch ($field) {
					case 'title':
						$theData[$field] = '&nbsp;<strong>'
							. $this->getLanguageService()->sL($GLOBALS['TCA']['pages']['columns'][$field]['label'])
							. '</strong>' . $eI;
						break;
					case 'uid':
						$theData[$field] = '&nbsp;<strong>ID:</strong>';
						break;
					default:
						if (substr($field, 0, 6) == 'table_') {
							$f2 = substr($field, 6);
							if ($GLOBALS['TCA'][$f2]) {
								$theData[$field] = '&nbsp;' . IconUtility::getSpriteIconForRecord($f2, array(), array(
									'title' => $this->getLanguageService()->sL($GLOBALS['TCA'][$f2]['ctrl']['title'], TRUE)
									));
							}
						} else {
							$theData[$field] = '&nbsp;&nbsp;<strong>'
								. $this->getLanguageService()->sL($GLOBALS['TCA']['pages']['columns'][$field]['label'], TRUE)
								. '</strong>' . $eI;
						}
				}
			}
			// Start table:
			$this->oddColumnsCssClass = '';
			// CSH:
			$out = BackendUtility::cshItem($this->descrTable, ('func_' . $pKey), $GLOBALS['BACK_PATH']) . '
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
	 * @return boolean|string Identifier of the backend layout to be used, or FALSE if none
	 * @deprecated since TYPO3 CMS 6.2, will be removed two versions later
	 */
	public function getSelectedBackendLayoutUid($id) {
		GeneralUtility::logDeprecatedFunction();
		return $this->getBackendLayoutView()->getSelectedCombinedIdentifier($id);
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
		$this->initializeClipboard();
		// Initialize:
		$RTE = $this->getBackendUser()->isRTE();
		$lMarg = 1;
		$showHidden = $this->tt_contentConfig['showHidden'] ? '' : BackendUtility::BEenableFields('tt_content');
		$pageTitleParamForAltDoc = '&recTitle=' . rawurlencode(BackendUtility::getRecordTitle('pages', BackendUtility::getRecordWSOL('pages', $id), TRUE));
		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $this->getPageLayoutController()->doc->getPageRenderer();
		$pageRenderer->loadExtJs();
		$pageRenderer->addJsFile($GLOBALS['BACK_PATH'] . 'sysext/cms/layout/js/typo3pageModule.js');
		// Get labels for CTypes and tt_content element fields in general:
		$this->CType_labels = array();
		foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $val) {
			$this->CType_labels[$val[1]] = $this->getLanguageService()->sL($val[0]);
		}
		$this->itemLabels = array();
		foreach ($GLOBALS['TCA']['tt_content']['columns'] as $name => $val) {
			$this->itemLabels[$name] = $this->getLanguageService()->sL($val['label']);
		}
		$languageColumn = array();
		$out = '';
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
			$langListArr = GeneralUtility::intExplode(',', $langList);
			$defLanguageCount = array();
			$defLangBinding = array();
			// For each languages... :
			// If not languageMode, then we'll only be through this once.
			foreach ($langListArr as $lP) {
				$lP = (int)$lP;
				if (count($langListArr) === 1 || $lP === 0) {
					$showLanguage = ' AND sys_language_uid IN (' . $lP . ',-1)';
				} else {
					$showLanguage = ' AND sys_language_uid=' . $lP;
				}
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
					$content[$key] .= '<div class="t3-page-ce-wrapper';
					if (count($contentRecordsPerColumn[$key]) === 0) {
						$content[$key] .= ' t3-page-ce-empty';
					}
					$content[$key] .= '">';
					// Add new content at the top most position
					$content[$key] .= '
					<div class="t3-page-ce" id="' . uniqid() . '">
						<div class="t3-page-ce-dropzone" id="colpos-' . $key . '-' . 'page-' . $id . '-' . uniqid() . '">
							<div class="t3-page-ce-wrapper-new-ce">
								<a href="#" onclick="' . htmlspecialchars($this->newContentElementOnClick($id, $key, $lP))
									. '" title="' . $this->getLanguageService()->getLL('newRecordHere', TRUE) . '">'
									. IconUtility::getSpriteIcon('actions-document-new') . '</a>
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
								$languageColumn[$key][$lP] .= $this->newLanguageButton(
									$this->getNonTranslatedTTcontentUids($defLanguageCount[$key], $id, $lP),
									$lP
								);
							}
						}
						if (is_array($row) && !VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
							$singleElementHTML = '';
							if (!$lP && ($this->defLangBinding || $row['sys_language_uid'] != -1)) {
								$defLanguageCount[$key][] = $row['uid'];
							}
							$editUidList .= $row['uid'] . ',';
							$disableMoveAndNewButtons = $this->defLangBinding && $lP > 0;
							if (!$this->tt_contentConfig['languageMode']) {
								$singleElementHTML .= '<div class="t3-page-ce-dragitem" id="' . uniqid() . '">';
							}
							$singleElementHTML .= $this->tt_content_drawHeader(
								$row,
								$this->tt_contentConfig['showInfo'] ? 15 : 5,
								$disableMoveAndNewButtons,
								TRUE,
								!$this->tt_contentConfig['languageMode']
							);
							$isRTE = $RTE && $this->isRTEforField('tt_content', $row, 'bodytext');
							$innerContent = '<div ' . ($row['_ORIG_uid'] ? ' class="ver-element"' : '') . '>'
								. $this->tt_content_drawItem($row, $isRTE) . '</div>';
							$singleElementHTML .= '<div class="t3-page-ce-body-inner">' . $innerContent . '</div>'
								. $this->tt_content_drawFooter($row);
							// NOTE: this is the end tag for <div class="t3-page-ce-body">
							// because of bad (historic) conception, starting tag has to be placed inside tt_content_drawHeader()
							$singleElementHTML .= '</div>';
							$statusHidden = $this->isDisabled('tt_content', $row) ? ' t3-page-ce-hidden' : '';
							$singleElementHTML = '<div class="t3-page-ce' . $statusHidden . '" id="element-tt_content-'
								. $row['uid'] . '">' . $singleElementHTML . '</div>';
							if ($this->tt_contentConfig['languageMode']) {
								$singleElementHTML .= '<div class="t3-page-ce">';
							}
							$singleElementHTML .= '<div class="t3-page-ce-dropzone" id="colpos-' . $key . '-' . 'page-' . $id .
								'-' . uniqid() . '">';
							// Add icon "new content element below"
							if (!$disableMoveAndNewButtons) {
								// New content element:
								if ($this->option_newWizard) {
									$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $row['pid']
										. '&sys_language_uid=' . $row['sys_language_uid'] . '&colPos=' . $row['colPos']
										. '&uid_pid=' . -$row['uid'] .
										'&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
								} else {
									$params = '&edit[tt_content][' . -$row['uid'] . ']=new';
									$onClick = BackendUtility::editOnClick($params, $this->backPath);
								}
								$singleElementHTML .= '
									<div class="t3-page-ce-wrapper-new-ce">
										<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
											. $this->getLanguageService()->getLL('newRecordHere', TRUE) . '">'
											. IconUtility::getSpriteIcon('actions-document-new') . '</a>
									</div>
								';
							}
							$singleElementHTML .= '</div></div>';
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
					$colTitle = BackendUtility::getProcessedValue('tt_content', 'colPos', $key);
					$tcaItems = GeneralUtility::callUserFunction('TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getColPosListItemsParsed', $id, $this);
					foreach ($tcaItems as $item) {
						if ($item[1] == $key) {
							$colTitle = $this->getLanguageService()->sL($item[0]);
						}
					}

					$pasteP = array('colPos' => $key, 'sys_language_uid' => $lP);
					$editParam = $this->doEdit && count($rowArr)
						? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc
						: '';
					$head[$key] .= $this->tt_content_drawColHeader($colTitle, $editParam, $newP, $pasteP);
				}
				// For each column, fit the rendered content into a table cell:
				$out = '';
				if ($this->tt_contentConfig['languageMode']) {
					// in language mode process the content elements, but only fill $languageColumn. output will be generated later
					foreach ($cList as $key) {
						$languageColumn[$key][$lP] = $head[$key] . $content[$key];
						if (!$this->defLangBinding) {
							$languageColumn[$key][$lP] .= $this->newLanguageButton(
								$this->getNonTranslatedTTcontentUids($defLanguageCount[$key], $id, $lP),
								$lP
							);
						}
					}
				} else {
					$backendLayout = $this->getBackendLayoutView()->getSelectedBackendLayout($this->id);
					// GRID VIEW:
					$grid = '<div class="t3-gridContainer"><table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%" class="t3-page-columns t3-gridTable">';
					// Add colgroups
					$colCount = (int)$backendLayout['__config']['backend_layout.']['colCount'];
					$rowCount = (int)$backendLayout['__config']['backend_layout.']['rowCount'];
					$grid .= '<colgroup>';
					for ($i = 0; $i < $colCount; $i++) {
						$grid .= '<col style="width:' . 100 / $colCount . '%"></col>';
					}
					$grid .= '</colgroup>';
					// Cycle through rows
					for ($row = 1; $row <= $rowCount; $row++) {
						$rowConfig = $backendLayout['__config']['backend_layout.']['rows.'][$row . '.'];
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
							$columnKey = (int)$columnConfig['colPos'];
							// Render the grid cell
							$colSpan = (int)$columnConfig['colspan'];
							$rowSpan = (int)$columnConfig['rowspan'];
							$grid .= '<td valign="top"' .
								($colSpan > 0 ? ' colspan="' . $colSpan . '"' : '') .
								($rowSpan > 0 ? ' rowspan="' . $rowSpan . '"' : '') .
								' class="t3-gridCell t3-page-column t3-page-column-' . $columnKey .
								((!isset($columnConfig['colPos']) || $columnConfig['colPos'] === '') ? ' t3-gridCell-unassigned' : '') .
								((isset($columnConfig['colPos']) && $columnConfig['colPos'] !== '' && !$head[$columnKey]) ? ' t3-gridCell-restricted' : '') .
								($colSpan > 0 ? ' t3-gridCell-width' . $colSpan : '') .
								($rowSpan > 0 ? ' t3-gridCell-height' . $rowSpan : '') . '">';

							// Draw the pre-generated header with edit and new buttons if a colPos is assigned.
							// If not, a new header without any buttons will be generated.
							if (isset($columnConfig['colPos']) && $columnConfig['colPos'] !== '' && $head[$columnKey]) {
								$grid .= $head[$columnKey] . $content[$columnKey];
							} elseif (isset($columnConfig['colPos']) && $columnConfig['colPos'] !== '') {
								$grid .= $this->tt_content_drawColHeader($this->getLanguageService()->getLL('noAccess'), '', '');
							} elseif (isset($columnConfig['name']) && strlen($columnConfig['name']) > 0) {
								$grid .= $this->tt_content_drawColHeader($this->getLanguageService()->sL($columnConfig['name'])
									. ' (' . $this->getLanguageService()->getLL('notAssigned') . ')', '', '');
							} else {
								$grid .= $this->tt_content_drawColHeader($this->getLanguageService()->getLL('notAssigned'), '', '');
							}

							$grid .= '</td>';
						}
						$grid .= '</tr>';
					}
					$out .= $grid . '</table></div>';
				}
				// CSH:
				$out .= BackendUtility::cshItem($this->descrTable, 'columns_multi', $GLOBALS['BACK_PATH']);
			}
			// If language mode, then make another presentation:
			// Notice that THIS presentation will override the value of $out!
			// But it needs the code above to execute since $languageColumn is filled with content we need!
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
					$lP = (int)$lP;
					$cCont[$lP] = '
						<td valign="top" class="t3-page-lang-column">
							<h3>' . htmlspecialchars($this->tt_contentConfig['languageCols'][$lP]) . '</h3>
						</td>';

					// "View page" icon is added:
					$onClick = BackendUtility::viewOnClick($this->id, $this->backPath, BackendUtility::BEgetRootLine($this->id), '', '', ('&L=' . $lP));
					$viewLink = '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . IconUtility::getSpriteIcon('actions-document-view') . '</a>';
					// Language overlay page header:
					if ($lP) {
						list($lpRecord) = BackendUtility::getRecordsByField('pages_language_overlay', 'pid', $id, 'AND sys_language_uid=' . $lP);
						BackendUtility::workspaceOL('pages_language_overlay', $lpRecord);
						$params = '&edit[pages_language_overlay][' . $lpRecord['uid'] . ']=edit&overrideVals[pages_language_overlay][sys_language_uid]=' . $lP;
						$lPLabel = $this->getPageLayoutController()->doc->wrapClickMenuOnIcon(
							IconUtility::getSpriteIconForRecord('pages_language_overlay', $lpRecord),
							'pages_language_overlay',
							$lpRecord['uid']
						) . $viewLink . ($this->getBackendUser()->check('tables_modify', 'pages_language_overlay')
								? '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath))
									. '" title="' . $this->getLanguageService()->getLL('edit', TRUE) . '">'
									. IconUtility::getSpriteIcon('actions-document-open') . '</a>'
								: ''
							) . htmlspecialchars(GeneralUtility::fixed_lgd_cs($lpRecord['title'], 20));
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
						<td valign="top" class="t3-gridCell t3-page-column t3-page-lang-column">' . implode(('</td>' . '
						<td valign="top" class="t3-gridCell t3-page-column t3-page-lang-column">'), $cCont) . '</td>
					</tr>';
					if ($this->defLangBinding) {
						// "defLangBinding" mode
						foreach ($defLanguageCount[$cKey] as $defUid) {
							$cCont = array();
							foreach ($langListArr as $lP) {
								$cCont[] = $defLangBinding[$cKey][$lP][$defUid] . $this->newLanguageButton(
									$this->getNonTranslatedTTcontentUids(array($defUid), $id, $lP),
									$lP
								);
							}
							$out .= '
							<tr>
								<td valign="top" class="t3-page-lang-column">' . implode(('</td>' . '
								<td valign="top" class="t3-page-lang-column">'), $cCont) . '</td>
							</tr>';
						}
						// Create spacer:
						$cCont = array_fill(0, count($langListArr), '&nbsp;');
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
				$out .= BackendUtility::cshItem($this->descrTable, 'language_list', $GLOBALS['BACK_PATH']);
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
					if ($this->doEdit && $this->option_showBigButtons && !(int)$key && $numberOfContentElementsInColumn == 0) {
						$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $id . '&colPos=' . (int)$key
							. '&sys_language_uid=' . $lP . '&uid_pid=' . $id . '&returnUrl='
							. rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
						$theNewButton = $this->getPageLayoutController()->doc->t3Button($onClick, $this->getLanguageService()->getLL('newPageContent'));
						$theNewButton = '<img src="clear.gif" width="1" height="5" alt="" /><br />' . $theNewButton;
					} else {
						$theNewButton = '';
					}
					$editUidList = '';
					// Traverse any selected elements:
					foreach ($rowArr as $rKey => $row) {
						if (is_array($row) && !VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
							$c++;
							$editUidList .= $row['uid'] . ',';
							$isRTE = $RTE && $this->isRTEforField('tt_content', $row, 'bodytext');
							// Create row output:
							$rowOut .= '
								<tr>
									<td></td>
									<td valign="top">' . $this->tt_content_drawHeader($row) . '</td>
									<td>&nbsp;</td>
									<td' . ($row['_ORIG_uid'] ? ' class="ver-element"' : '') . ' valign="top">'
										. $this->tt_content_drawItem($row, $isRTE) . '</td>
								</tr>';
							// If the element was not the last element, add a divider line:
							if ($c != $numberOfContentElementsInColumn) {
								$rowOut .= '
								<tr>
									<td></td>
									<td colspan="3"><img'
									. IconUtility::skinImg($this->backPath, 'gfx/stiblet_medium2.gif', 'width="468" height="1"')
									. ' class="c-divider" alt="" /></td>
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
					$pasteP = array('colPos' => $key, 'sys_language_uid' => $this->tt_contentConfig['sys_language_uid']);
					$out .= '

						<!-- Column header: -->
						<tr>
							<td></td>
							<td valign="top" colspan="3">' . $this->tt_content_drawColHeader(
								BackendUtility::getProcessedValue('tt_content', 'colPos', $key),
								$this->doEdit && count($rowArr) ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc : '',
								$newP,
								$pasteP
							) . $theNewButton . '<br /></td>
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
				$out .= BackendUtility::cshItem($this->descrTable, 'columns_single', $GLOBALS['BACK_PATH']);
			} else {
				$out = '<br/><br/>' . $this->getPageLayoutController()->doc->icons(1)
					. 'Sorry, you cannot view a single language in this localization mode (Default Language Binding is enabled)<br/><br/>';
			}
		}
		// Add the big buttons to page:
		if ($this->option_showBigButtons) {
			$bArray = array();
			if (!$this->getPageLayoutController()->current_sys_language) {
				if ($this->ext_CALC_PERMS & 2) {
					$bArray[0] = $this->getPageLayoutController()->doc->t3Button(
						BackendUtility::editOnClick('&edit[pages][' . $id . ']=edit', $this->backPath, ''),
						$this->getLanguageService()->getLL('editPageProperties')
					);
				}
			} else {
				if ($this->doEdit && $this->getBackendUser()->check('tables_modify', 'pages_language_overlay')) {
					list($languageOverlayRecord) = BackendUtility::getRecordsByField(
						'pages_language_overlay',
						'pid',
						$id,
						'AND sys_language_uid=' . (int)$this->getPageLayoutController()->current_sys_language
					);
					$bArray[0] = $this->getPageLayoutController()->doc->t3Button(
						BackendUtility::editOnClick('&edit[pages_language_overlay][' . $languageOverlayRecord['uid'] . ']=edit',
							$this->backPath, ''),
						$this->getLanguageService()->getLL('editPageProperties_curLang')
					);
				}
			}
			if ($this->ext_CALC_PERMS & 4 || $this->ext_CALC_PERMS & 2) {
				$bArray[1] = $this->getPageLayoutController()->doc->t3Button(
					'window.location.href=\'' . $this->backPath . 'move_el.php?table=pages&uid=' . $id
						. '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';',
					$this->getLanguageService()->getLL('move_page')
				);
			}
			if ($this->ext_CALC_PERMS & 8) {
				$bArray[2] = $this->getPageLayoutController()->doc->t3Button(
					'window.location.href=\'' . $this->backPath . 'db_new.php?id=' . $id
						. '&pagesOnly=1&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';',
					$this->getLanguageService()->getLL('newPage2')
				);
			}
			if ($this->doEdit && $this->ext_function == 1) {
				$bArray[3] = $this->getPageLayoutController()->doc->t3Button(
					'window.location.href=\'db_new_content_el.php?id=' . $id
						. '&sys_language_uid=' . $this->getPageLayoutController()->current_sys_language
						. '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';',
					$this->getLanguageService()->getLL('newPageContent2')
				);
			}
			$out = '
				<table border="0" cellpadding="4" cellspacing="0" class="typo3-page-buttons">
					<tr>
						<td>' . implode('</td>
						<td>', $bArray) . '</td>
						<td>' . BackendUtility::cshItem($this->descrTable, 'button_panel', $GLOBALS['BACK_PATH']) . '</td>
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
	 * @deprecated since TYPO3 CMS 6.2, will be removed two versions later
	 */
	public function getBackendLayoutConfiguration() {
		GeneralUtility::logDeprecatedFunction();
		return $this->getBackendLayoutView()->getSelectedBackendLayout($this->id);
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
	public function makeOrdinaryList($table, $id, $fList, $icon = FALSE, $addWhere = '') {
		// Initialize
		$queryParts = $this->makeQueryArray($table, $id, $addWhere);
		$this->setTotalItems($queryParts);
		$dbCount = 0;
		$result = FALSE;
		// Make query for records if there were any records found in the count operation
		if ($this->totalItems) {
			$result = $this->getDatabase()->exec_SELECT_queryArray($queryParts);
			// Will return FALSE, if $result is invalid
			$dbCount = $this->getDatabase()->sql_num_rows($result);
		}
		// If records were found, render the list
		if (!$dbCount) {
			return '';
		}
		// Set fields
		$out = '';
		$this->fieldArray = GeneralUtility::trimExplode(',', '__cmds__,' . $fList . ',__editIconLink__', TRUE);
		$theData = array();
		$theData = $this->headerFields($this->fieldArray, $table, $theData);
		// Title row
		$localizedTableTitle = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title'], TRUE);
		$out .= '<tr class="t3-row-header">' . '<td nowrap="nowrap" class="col-icon"></td>'
			. '<td nowrap="nowrap" colspan="' . (count($theData) - 2) . '"><span class="c-table">'
			. $localizedTableTitle . '</span> (' . $dbCount . ')</td>' . '<td nowrap="nowrap" class="col-icon"></td>'
			. '</tr>';
		// Column's titles
		if ($this->doEdit) {
			$theData['__cmds__'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick(
					'&edit[' . $table . '][' . $this->id . ']=new',
					$this->backPath
				)) . '" title="' . $this->getLanguageService()->getLL('new', TRUE) . '">'
				. IconUtility::getSpriteIcon('actions-document-new') . '</a>';
		}
		$out .= $this->addelement(1, '', $theData, ' class="c-headLine"', 15);
		// Render Items
		$this->eCounter = $this->firstElementNumber;
		while ($row = $this->getDatabase()->sql_fetch_assoc($result)) {
			BackendUtility::workspaceOL($table, $row);
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
						$Nrow['__editIconLink__'] = '<a href="#" onclick="' . htmlspecialchars(
								BackendUtility::editOnClick($params, $this->backPath))
							. '" title="' . $this->getLanguageService()->getLL('edit', TRUE) . '">'
							. IconUtility::getSpriteIcon('actions-document-open') . '</a>';
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
	 * Each field name in $fieldArr has a special feature which is that the field name can be specified as more field names.
	 * Eg. "field1,field2;field3".
	 * Field 2 and 3 will be shown in the same cell of the table separated by <br /> while field1 will have its own cell.
	 *
	 * @param array $fieldArr Array of fields to display
	 * @param string $table Table name
	 * @param array $row Record array
	 * @param array $out Array to which the data is added
	 * @return array $out array returned after processing.
	 * @see makeOrdinaryList()
	 * @todo Define visibility
	 */
	public function dataFields($fieldArr, $table, $row, $out = array()) {
		// Check table validity
		if (!isset($GLOBALS['TCA'][$table])) {
			return $out;
		}

		$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
		// Traverse fields
		foreach ($fieldArr as $fieldName) {
			if ($GLOBALS['TCA'][$table]['columns'][$fieldName]) {
				// Each field has its own cell (if configured in TCA)
				// If the column is a thumbnail column:
				if ($fieldName == $thumbsCol) {
					$out[$fieldName] = $this->thumbCode($row, $table, $fieldName);
				} else {
					// ... otherwise just render the output:
					$out[$fieldName] = nl2br(htmlspecialchars(trim(GeneralUtility::fixed_lgd_cs(
						BackendUtility::getProcessedValue($table, $fieldName, $row[$fieldName], 0, 0, 0, $row['uid']),
						250)
					)));
				}
			} else {
				// Each field is separated by <br /> and shown in the same cell (If not a TCA field, then explode
				// the field name with ";" and check each value there as a TCA configured field)
				$theFields = explode(';', $fieldName);
				// Traverse fields, separated by ";" (displayed in a single cell).
				foreach ($theFields as $fName2) {
					if ($GLOBALS['TCA'][$table]['columns'][$fName2]) {
						$out[$fieldName] .= '<strong>' . $this->getLanguageService()->sL(
								$GLOBALS['TCA'][$table]['columns'][$fName2]['label'],
								TRUE
							) . '</strong>' . '&nbsp;&nbsp;' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(
								BackendUtility::getProcessedValue($table, $fName2, $row[$fName2], 0, 0, 0, $row['uid']),
								25
							)) . '<br />';
					}
				}
			}
			// If no value, add a nbsp.
			if (!$out[$fieldName]) {
				$out[$fieldName] = '&nbsp;';
			}
			// Wrap in dimmed-span tags if record is "disabled"
			if ($this->isDisabled($table, $row)) {
				$out[$fieldName] = $this->getDocumentTemplate()->dfw($out[$fieldName]);
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
			$ll = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label'], TRUE);
			$out[$fieldName] = $ll ? $ll : '&nbsp;';
		}
		return $out;
	}

	/**
	 * Gets content records per column.
	 * This is required for correct workspace overlays.
	 *
	 * @param string $table UNUSED (will always be queried from tt_content)
	 * @param integer $id Page Id to be used (not used at all, but part of the API, see $this->pidSelect)
	 * @param array $columns colPos values to be considered to be shown
	 * @param string $additionalWhereClause Additional where clause for database select
	 * @return array Associative array for each column (colPos)
	 */
	protected function getContentRecordsPerColumn($table, $id, array $columns, $additionalWhereClause = '') {
		$columns = array_map('intval', $columns);
		$contentRecordsPerColumn = array_fill_keys($columns, array());

		$queryParts = $this->makeQueryArray('tt_content', $id, 'AND colPos IN (' . implode(',', $columns) . ')' . $additionalWhereClause);
		$result = $this->getDatabase()->exec_SELECT_queryArray($queryParts);
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
			$res = $this->getDatabase()->exec_SELECTquery('*', 'pages', 'pid=' . (int)$pid . $qWhere, '', 'sorting');
			$c = 0;
			$rc = $this->getDatabase()->sql_num_rows($res);
			while ($row = $this->getDatabase()->sql_fetch_assoc($res)) {
				BackendUtility::workspaceOL('pages', $row);
				if (is_array($row)) {
					$c++;
					$row['treeIcons'] = $treeIcons . '<img' . IconUtility::skinImg(
							$this->backPath,
							'gfx/ol/join' . ($rc == $c ? 'bottom' : '') . '.gif',
							'width="18" height="16"'
						) . ' alt="" />';
					$theRows[] = $row;
					// Get the branch
					$spaceOutIcons = '<img' . IconUtility::skinImg(
							$this->backPath,
							'gfx/ol/' . ($rc == $c ? 'blank.gif' : 'line.gif'),
							'width="18" height="16"'
						) . ' alt="" />';
					$theRows = $this->pages_getTree($theRows, $row['uid'], $qWhere, $treeIcons . $spaceOutIcons, $row['php_tree_stop'] ? 0 : $depth);
				}
			}
		} else {
			$count = $this->getDatabase()->exec_SELECTcountRows('uid', 'pages', 'pid=' . (int)$pid . $qWhere);
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
					$pTitle = htmlspecialchars(BackendUtility::getProcessedValue('pages', $field, $row[$field], 20));
					if ($red) {
						$pTitle = '<a href="'
							. htmlspecialchars($this->script . ((strpos($this->script, '?') !== FALSE) ? '&' : '?')
							. 'id=' . $row['uid']) . '">' . $pTitle . '</a>';
					}
					$theData[$field] = $row['treeIcons'] . $theIcon . $red . $pTitle . '&nbsp;&nbsp;';
					break;
				case 'php_tree_stop':
					// Intended fall through
				case 'TSconfig':
					$theData[$field] = $row[$field] ? '&nbsp;<strong>x</strong>' : '&nbsp;';
					break;
				case 'uid':
					if ($this->getBackendUser()->doesUserHaveAccess($row, 2)) {
						$params = '&edit[pages][' . $row['uid'] . ']=edit';
						$eI = '<a href="#" onclick="'
							. htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, ''))
							. '" title="' . $this->getLanguageService()->getLL('editThisPage', TRUE) . '">'
							. IconUtility::getSpriteIcon('actions-document-open') . '</a>';
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
						$theData[$field] = '&nbsp;&nbsp;'
							. htmlspecialchars(BackendUtility::getProcessedValue('pages', $field, $row[$field]));
					}
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
	 * @param array|NULL $pasteParams Paste element params (i.e. array(colPos => 1, sys_language_uid => 2))
	 * @return string HTML table
	 * @todo Define visibility
	 */
	public function tt_content_drawColHeader($colName, $editParams, $newParams, array $pasteParams = NULL) {
		$iconsArr = array();
		// Create command links:
		if ($this->tt_contentConfig['showCommands']) {
			// Edit whole of column:
			if ($editParams) {
				$iconsArr['edit'] = '<a href="#" onclick="'
					. htmlspecialchars(BackendUtility::editOnClick($editParams, $this->backPath)) . '" title="'
					. $this->getLanguageService()->getLL('editColumn', TRUE) . '">'
					. IconUtility::getSpriteIcon('actions-document-open') . '</a>';
			}
			if ($pasteParams) {
				$elFromTable = $this->clipboard->elFromTable('tt_content');
				if (count($elFromTable)) {
					$iconsArr['paste'] = '<a href="'
						. htmlspecialchars($this->clipboard->pasteUrl('tt_content', $this->id, TRUE, $pasteParams))
						. '" onclick="' . htmlspecialchars(('return '
						. $this->clipboard->confirmMsg('pages', $this->pageRecord, 'into', $elFromTable, $colName)))
						. '" title="' . $this->getLanguageService()->getLL('clip_paste', TRUE) . '">'
						. IconUtility::getSpriteIcon('actions-document-paste-into') . '</a>';
				}
			}
		}
		$icons = '';
		if (count($iconsArr)) {
			$icons = '<div class="t3-page-colHeader-icons">' . implode('', $iconsArr) . '</div>';
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
	 * @param integer $space Amount of pixel space above the header. UNUSED
	 * @param boolean $disableMoveAndNewButtons If set the buttons for creating new elements and moving up and down are not shown.
	 * @param boolean $langMode If set, we are in language mode and flags will be shown for languages
	 * @param boolean $dragDropEnabled If set the move button must be hidden
	 * @return string HTML table with the record header.
	 * @todo Define visibility
	 */
	public function tt_content_drawHeader($row, $space = 0, $disableMoveAndNewButtons = FALSE, $langMode = FALSE, $dragDropEnabled = FALSE) {
		$out = '';
		// If show info is set...;
		if ($this->tt_contentConfig['showInfo'] && $this->getBackendUser()->recordEditAccessInternals('tt_content', $row)) {
			// Render control panel for the element:
			if ($this->tt_contentConfig['showCommands'] && $this->doEdit) {
				// Edit content element:
				$params = '&edit[tt_content][' . $this->tt_contentData['nextThree'][$row['uid']] . ']=edit';
				$out .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick(
						$params,
						$this->backPath,
						GeneralUtility::getIndpEnv('REQUEST_URI') . '#element-tt_content-' . $row['uid']
					)) . '" title="' . htmlspecialchars($this->nextThree > 1
						? sprintf($this->getLanguageService()->getLL('nextThree'), $this->nextThree)
						: $this->getLanguageService()->getLL('edit'))
					. '">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
				// Hide element:
				$hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
				if (
					$hiddenField && $GLOBALS['TCA']['tt_content']['columns'][$hiddenField]
					&& (!$GLOBALS['TCA']['tt_content']['columns'][$hiddenField]['exclude']
						|| $this->getBackendUser()->check('non_exclude_fields', 'tt_content:' . $hiddenField))
				) {
					if ($row[$hiddenField]) {
						$value = 0;
						$label = 'unHide';
					} else {
						$value = 1;
						$label = 'hide';
					}
					$params = '&data[tt_content][' . ($row['_ORIG_uid'] ? $row['_ORIG_uid'] : $row['uid'])
						. '][' . $hiddenField . ']=' . $value;
					$out .= '<a href="' . htmlspecialchars($this->getPageLayoutController()->doc->issueCommand($params))
						. '" title="' . $this->getLanguageService()->getLL($label, TRUE) . '">'
						. IconUtility::getSpriteIcon('actions-edit-' . strtolower($label)) . '</a>';
				}
				// Delete
				$params = '&cmd[tt_content][' . $row['uid'] . '][delete]=1';
				$confirm = GeneralUtility::quoteJSvalue($this->getLanguageService()->getLL('deleteWarning')
					. BackendUtility::translationCount('tt_content', $row['uid'], (' '
						. $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord')))
				);
				$out .= '<a href="' . htmlspecialchars($this->getPageLayoutController()->doc->issueCommand($params))
					. '" onclick="' . htmlspecialchars(('return confirm(' . $confirm . ');')) . '" title="'
					. $this->getLanguageService()->getLL('deleteItem', TRUE) . '">'
					. IconUtility::getSpriteIcon('actions-edit-delete') . '</a>';
				if (!$disableMoveAndNewButtons) {
					$moveButtonContent = '';
					$displayMoveButtons = FALSE;
					// Move element up:
					if ($this->tt_contentData['prev'][$row['uid']]) {
						$params = '&cmd[tt_content][' . $row['uid'] . '][move]=' . $this->tt_contentData['prev'][$row['uid']];
						$moveButtonContent .= '<a href="'
							. htmlspecialchars($this->getPageLayoutController()->doc->issueCommand($params))
							. '" title="' . $this->getLanguageService()->getLL('moveUp', TRUE) . '">'
							. IconUtility::getSpriteIcon('actions-move-up') . '</a>';
						if (!$dragDropEnabled) {
							$displayMoveButtons = TRUE;
						}
					} else {
						$moveButtonContent .= IconUtility::getSpriteIcon('empty-empty');
					}
					// Move element down:
					if ($this->tt_contentData['next'][$row['uid']]) {
						$params = '&cmd[tt_content][' . $row['uid'] . '][move]= ' . $this->tt_contentData['next'][$row['uid']];
						$moveButtonContent .= '<a href="'
							. htmlspecialchars($this->getPageLayoutController()->doc->issueCommand($params))
							. '" title="' . $this->getLanguageService()->getLL('moveDown', TRUE) . '">'
							. IconUtility::getSpriteIcon('actions-move-down') . '</a>';
						if (!$dragDropEnabled) {
							$displayMoveButtons = TRUE;
						}
					} else {
						$moveButtonContent .= IconUtility::getSpriteIcon('empty-empty');
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
		if ($lockInfo = BackendUtility::isRecordLocked('tt_content', $row['uid'])) {
			$additionalIcons[] = '<a href="#" onclick="alert(' . GeneralUtility::quoteJSvalue($lockInfo['msg'])
				. ');return false;" title="' . htmlspecialchars($lockInfo['msg']) . '">'
				. IconUtility::getSpriteIcon('status-warning-in-use') . '</a>';
		}
		// Call stats information hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
			$_params = array('tt_content', $row['uid'], &$row);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
				$additionalIcons[] = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
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
	 * @throws \UnexpectedValueException
	 * @todo Define visibility
	 */
	public function tt_content_drawItem($row, $isRTE = FALSE) {
		$out = '';
		$outHeader = '';
		// Make header:
		if ($row['header']) {
			$infoArr = array();
			$this->getProcessedValue('tt_content', 'header_position,header_layout,header_link', $row, $infoArr);
			$hiddenHeaderNote = '';
			// If header layout is set to 'hidden', display an accordant note:
			if ($row['header_layout'] == 100) {
				$hiddenHeaderNote = ' <em>[' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.hidden', TRUE) . ']</em>';
			}
			$outHeader = $row['date']
				? htmlspecialchars($this->itemLabels['date'] . ' ' . BackendUtility::date($row['date'])) . '<br />'
				: '';
			$outHeader .= '<strong>' . $this->linkEditContent($this->renderText($row['header']), $row)
				. $hiddenHeaderNote . '</strong><br />';
		}
		// Make content:
		$infoArr = array();
		$drawItem = TRUE;
		// Hook: Render an own preview of a record
		$drawItemHooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'];
		if (is_array($drawItemHooks)) {
			foreach ($drawItemHooks as $hookClass) {
				$hookObject = GeneralUtility::getUserObj($hookClass);
				if (!$hookObject instanceof PageLayoutViewDrawItemHookInterface) {
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
							$split = BackendUtility::splitTable_Uid($recordIdentifier);
							$tableName = empty($split[0]) ? 'tt_content' : $split[0];
							$shortcutRecord = BackendUtility::getRecord($tableName, $split[1]);
							if (is_array($shortcutRecord)) {
								$icon = IconUtility::getSpriteIconForRecord($tableName, $shortcutRecord);
								$onClick = $this->getPageLayoutController()->doc->wrapClickMenuOnIcon($icon, $tableName,
									$shortcutRecord['uid'], 1, '', '+copy,info,edit,view', TRUE);
								$shortcutContent[] = '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $icon . '</a>'
									. htmlspecialchars(BackendUtility::getRecordTitle($tableName, $shortcutRecord));
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
							$hookOut .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
						}
					}
					if ((string)$hookOut !== '') {
						$out .= $hookOut;
					} elseif (!empty($row['list_type'])) {
						$label = BackendUtility::getLabelFromItemlist('tt_content', 'list_type', $row['list_type']);
						if (!empty($label)) {
							$out .=  '<strong>' . $this->getLanguageService()->sL($label, TRUE) . '</strong><br />';
						} else {
							$message = sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'), $row['list_type']);
							$out .= GeneralUtility::makeInstance(
								'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
								htmlspecialchars($message),
								'',
								FlashMessage::WARNING
							)->render();
						}
					} elseif (!empty($row['select_key'])) {
						$out .= $this->getLanguageService()->sL(BackendUtility::getItemLabel('tt_content', 'select_key'), TRUE)
							. ' ' . $row['select_key'] . '<br />';
					} else {
						$out .= '<strong>' . $this->getLanguageService()->getLL('noPluginSelected') . '</strong>';
					}
					$out .= $this->getLanguageService()->sL(
							BackendUtility::getLabelFromItemlist('tt_content', 'pages', $row['pages']),
							TRUE
						) . '<br />';
					break;
				case 'script':
					$out .= $this->getLanguageService()->sL(BackendUtility::getItemLabel('tt_content', 'select_key'), TRUE)
						. ' ' . $row['select_key'] . '<br />';
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
						$message = sprintf(
							$this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'),
							$row['CType']
						);
						$out .= GeneralUtility::makeInstance(
							'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
							htmlspecialchars($message),
							'',
							FlashMessage::WARNING
						)->render();
					}
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
			return $this->getDocumentTemplate()->dfw($out);
		} else {
			return $out;
		}
	}

	/**
	 * Filters out all tt_content uids which are already translated so only non-translated uids is left.
	 * Selects across columns, but within in the same PID. Columns are expect to be the same
	 * for translations and original but this may be a conceptual error (?)
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
			$queryParts = $this->makeQueryArray('tt_content', $id, 'AND sys_language_uid=' . (int)$lP
				. ' AND l18n_parent IN (' . implode(',', $defLanguageCount) . ')');
			$result = $this->getDatabase()->exec_SELECT_queryArray($queryParts);
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
		if (!$this->doEdit || count($defLanguageCount) === 0 || !$lP) {
			return '';
		}
		$params = '';
		foreach ($defLanguageCount as $uidVal) {
			$params .= '&cmd[tt_content][' . $uidVal . '][localize]=' . $lP;
		}
		// Copy for language:
		$onClick = 'window.location.href=\'' . $this->getPageLayoutController()->doc->issueCommand($params) . '\'; return false;';
		$theNewButton = '<div class="t3-page-lang-copyce">' .
			$this->getPageLayoutController()->doc->t3Button(
				$onClick,
				$this->getLanguageService()->getLL('newPageContent_copyForLang') . ' [' . count($defLanguageCount) . ']'
			) . '</div>';
		return $theNewButton;
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
			$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $id . '&colPos=' . $colPos
				. '&sys_language_uid=' . $sys_language . '&uid_pid=' . $id
				. '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
		} else {
			$onClick = BackendUtility::editOnClick('&edit[tt_content][' . $id . ']=new&defVals[tt_content][colPos]='
				. $colPos . '&defVals[tt_content][sys_language_uid]=' . $sys_language, $this->backPath);
		}
		return $onClick;
	}

	/**
	 * Will create a link on the input string and possibly a big button after the string which links to editing in the RTE.
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
		if ($this->doEdit && $this->getBackendUser()->recordEditAccessInternals('tt_content', $row)) {
			// Setting onclick action for content link:
			$onClick = BackendUtility::editOnClick('&edit[tt_content][' . $row['uid'] . ']=edit', $this->backPath);
		}
		// Return link
		return $onClick ? '<a href="#" onclick="' . htmlspecialchars($onClick)
			. '" title="' . $this->getLanguageService()->getLL('edit', TRUE) . '">' . $str . '</a>' . $addButton : $str;
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
		$params['returnUrl'] = GeneralUtility::linkThisScript();
		$RTEonClick = 'window.location.href=' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('wizard_rte', array('P' => $params))) . ';return false;';
		$addButton = $this->option_showBigButtons && $this->doEdit
			? $this->getPageLayoutController()->doc->t3Button($RTEonClick, $this->getLanguageService()->getLL('editInRTE'))
			: '';
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
		if ($this->getBackendUser()->check('tables_modify', 'pages_language_overlay')) {
			// First, select all
			$res = $this->getPageLayoutController()->exec_languageQuery(0);
			$langSelItems = array();
			$langSelItems[0] = '
						<option value="0"></option>';
			while ($row = $this->getDatabase()->sql_fetch_assoc($res)) {
				if ($this->getBackendUser()->checkLanguageAccess($row['uid'])) {
					$langSelItems[$row['uid']] = '
							<option value="' . $row['uid'] . '">' . htmlspecialchars($row['title']) . '</option>';
				}
			}
			// Then, subtract the languages which are already on the page:
			$res = $this->getPageLayoutController()->exec_languageQuery($id);
			while ($row = $this->getDatabase()->sql_fetch_assoc($res)) {
				unset($langSelItems[$row['uid']]);
			}
			// Remove disallowed languages
			if (count($langSelItems) > 1
				&& !$this->getBackendUser()->user['admin']
				&& strlen($this->getBackendUser()->groupData['allowed_languages'])
			) {
				$allowed_languages = array_flip(explode(',', $this->getBackendUser()->groupData['allowed_languages']));
				if (count($allowed_languages)) {
					foreach ($langSelItems as $key => $value) {
						if (!isset($allowed_languages[$key]) && $key != 0) {
							unset($langSelItems[$key]);
						}
					}
				}
			}
			// Remove disabled languages
			$modSharedTSconfig = BackendUtility::getModTSconfig($id, 'mod.SHARED');
			$disableLanguages = isset($modSharedTSconfig['properties']['disableLanguages'])
				? GeneralUtility::trimExplode(',', $modSharedTSconfig['properties']['disableLanguages'], TRUE)
				: array();
			if (count($langSelItems) && count($disableLanguages)) {
				foreach ($disableLanguages as $language) {
					if ($language != 0 && isset($langSelItems[$language])) {
						unset($langSelItems[$language]);
					}
				}
			}
			// If any languages are left, make selector:
			if (count($langSelItems) > 1) {
				$onChangeContent = 'window.location.href=\'' . $this->backPath . 'alt_doc.php?&edit[pages_language_overlay]['
					. $id . ']=new&overrideVals[pages_language_overlay][doktype]=' . (int)$this->pageRecord['doktype']
					. '&overrideVals[pages_language_overlay][sys_language_uid]=\'+this.options[this.selectedIndex].value+\'&returnUrl='
					. rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\'';
				return $this->getLanguageService()->getLL('new_language', TRUE)
					. ': <select name="createNewLanguage" onchange="' . htmlspecialchars($onChangeContent) . '">
						' . implode('', $langSelItems) . '
					</select><br /><br />';
			}
		}
		return '';
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
		$recs = array();
		$nextTree = $this->nextThree;
		$c = 0;
		$output = array();
		// Traverse the result:
		while ($row = $this->getDatabase()->sql_fetch_assoc($result)) {
			BackendUtility::workspaceOL($table, $row, -99, TRUE);
			if ($row) {
				// Add the row to the array:
				$output[] = $row;
				// Set an internal register:
				$recs[$c] = $row['uid'];
				// Create the list of the next three ids (for editing links...)
				for ($a = 0; $a < $nextTree; $a++) {
					$inList = GeneralUtility::inList($this->tt_contentData['nextThree'][$recs[$c - $a]], $row['uid']);
					if (isset($recs[$c - $a]) && !$inList) {
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
	 * Initializes the clipboard for generating paste links
	 *
	 * @return void
	 *
	 * @see \TYPO3\CMS\Recordlist\RecordList::main()
	 * @see \TYPO3\CMS\Backend\Controller\ClickMenuController::main()
	 * @see \TYPO3\CMS\Filelist\Controller\FileListController::main()
	 */
	protected function initializeClipboard() {
		// Start clipboard
		$this->clipboard = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');

		// Initialize - reads the clipboard content from the user session
		$this->clipboard->initializeClipboard();

		// This locks the clipboard to the Normal for this request.
		$this->clipboard->lockToNormal();

		// Clean up pad
		$this->clipboard->cleanCurrent();

		// Save the clipboard content
		$this->clipboard->endClipboard();
	}

	/**
	 * Counts and returns the number of records on the page with $pid
	 *
	 * @param string $table Table name
	 * @param integer $pid Page id
	 * @return integer Number of records.
	 * @todo Define visibility
	 */
	public function numberOfRecords($table, $pid) {
		$count = 0;
		if ($GLOBALS['TCA'][$table]) {
			$where = 'pid=' . (int)$pid . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table);
			$count = $this->getDatabase()->exec_SELECTcountRows('uid', $table, $where);
		}
		return (int)$count;
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
		$input = GeneralUtility::fixed_lgd_cs($input, 1500);
		return nl2br(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8', FALSE));
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
		$altText = BackendUtility::getRecordIconAltText($row, $table);
		$icon = IconUtility::getSpriteIconForRecord($table, $row, array('title' => $altText));
		$this->counter++;
		// The icon with link
		if ($this->getBackendUser()->recordEditAccessInternals($table, $row)) {
			$icon = $this->getPageLayoutController()->doc->wrapClickMenuOnIcon($icon, $table, $row['uid']);
		}
		return $icon;
	}

	/**
	 * Creates processed values for all field names in $fieldList based on values from $row array.
	 * The result is 'returned' through $info which is passed as a reference
	 *
	 * @param string $table Table name
	 * @param string $fieldList Comma separated list of fields.
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
				$info[] = '<strong>' . htmlspecialchars($this->itemLabels[$field]) . '</strong> '
					. htmlspecialchars(BackendUtility::getProcessedValue($table, $field, $row[$field]));
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
		$enableCols = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'];
		return $enableCols['disabled'] && $row[$enableCols['disabled']]
			|| $enableCols['starttime'] && $row[$enableCols['starttime']] > $GLOBALS['EXEC_TIME']
			|| $enableCols['endtime'] && $row[$enableCols['endtime']] && $row[$enableCols['endtime']] < $GLOBALS['EXEC_TIME'];
	}

	/**
	 * Will perform "word-wrapping" on the input string. What it does is to split by space or line break,
	 * then find any word longer than $max and if found, a hyphen is inserted.
	 * Works well on normal texts, little less well when HTML is involved (since much HTML will have
	 * long strings that will be broken).
	 *
	 * @param string $content Content to word-wrap.
	 * @param integer $max Max number of chars in a word before it will be wrapped.
	 * @param string $char Character to insert when wrapping.
	 * @return string Processed output.
	 * @deprecated since 6.2 - will be removed two versions later; use CSS instead (word-break: break-all;)
	 */
	public function wordWrapper($content, $max = 50, $char = ' -') {
		GeneralUtility::logDeprecatedFunction();
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
	 * Basically, the point is to signal that this record could have had an edit link if
	 * the circumstances were right. A placeholder for the regular edit icon...
	 *
	 * @param string $label Label key from LOCAL_LANG
	 * @return string IMG tag for icon.
	 * @todo Define visibility
	 */
	public function noEditIcon($label = 'noEditItems') {
		return IconUtility::getSpriteIcon(
			'status-edit-read-only',
			array('title' => $this->getLanguageService()->getLL($label, TRUE))
		);
	}

	/**
	 * Function, which fills in the internal array, $this->allowedTableNames with all tables to
	 * which the user has access. Also a set of standard tables (pages, static_template, sys_filemounts, etc...)
	 * are filtered out. So what is left is basically all tables which makes sense to list content from.
	 *
	 * @return array
	 */
	protected function cleanTableNames() {
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
		$allowedTableNames = array();
		// Traverse table names and set them in allowedTableNames array IF they can be read-accessed by the user.
		if (is_array($tableNames)) {
			foreach ($tableNames as $k => $v) {
				if ($this->getBackendUser()->check('tables_select', $k)) {
					$allowedTableNames['table_' . $k] = $k;
				}
			}
		}
		return $allowedTableNames;
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
		if (!count($specConf)) {
			return FALSE;
		}
		$p = BackendUtility::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
		if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']])) {
			BackendUtility::fixVersioningPid($table, $row);
			list($tscPID, $thePidValue) = BackendUtility::getTSCpid($table, $row['uid'], $row['pid']);
			// If the pid-value is not negative (that is, a pid could NOT be fetched)
			if ($thePidValue >= 0) {
				if (!isset($this->rteSetup[$tscPID])) {
					$this->rteSetup[$tscPID] = $this->getBackendUser()->getTSConfig('RTE', BackendUtility::getPagesTSconfig($tscPID));
				}
				$RTEtypeVal = BackendUtility::getTCAtypeValue($table, $row);
				$thisConfig = BackendUtility::RTEsetup($this->rteSetup[$tscPID]['properties'], $table, $field, $RTEtypeVal);
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
		$types_fieldConfig = BackendUtility::getTCAtypes($table, $row);
		// Find the given field and return the spec key value if found:
		if (is_array($types_fieldConfig)) {
			foreach ($types_fieldConfig as $vConf) {
				if ($vConf['field'] == $field) {
					return $vConf['spec'];
				}
			}
		}
		return array();
	}

	/*****************************************
	 *
	 * External renderings
	 *
	 *****************************************/

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
		$out = '';
		// Traverse tables to check:
		foreach ($theTables as $tName) {
			// Check access and whether the proper extensions are loaded:
			if ($this->getBackendUser()->check('tables_select', $tName)
				&& (isset($this->externalTables[$tName])
					|| GeneralUtility::inList('fe_users,tt_content', $tName)
					|| \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($tName)
				)
			) {
				// Make query to count records from page:
				$c = $this->getDatabase()->exec_SELECTcountRows('uid', $tName, 'pid=' . (int)$id
					. BackendUtility::deleteClause($tName) . BackendUtility::versioningPlaceholderClause($tName));
				// If records were found (or if "tt_content" is the table...):
				if ($c || GeneralUtility::inList('tt_content', $tName)) {
					// Add row to menu:
					$out .= '
					<td><a href="#' . $tName . '"></a>' . IconUtility::getSpriteIconForRecord(
							$tName,
							array(),
							array('title' => $this->getLanguageService()->sL($GLOBALS['TCA'][$tName]['ctrl']['title'], TRUE))
						) . '</td>';
					// ... and to the internal array, activeTables we also add table icon and title (for use elsewhere)
					$this->activeTables[$tName] = IconUtility::getSpriteIconForRecord(
							$tName,
							array(),
							array('title' => $this->getLanguageService()->sL($GLOBALS['TCA'][$tName]['ctrl']['title'], TRUE)
									. ': ' . $c . ' ' . $this->getLanguageService()->getLL('records', TRUE))
						) . '&nbsp;' . $this->getLanguageService()->sL($GLOBALS['TCA'][$tName]['ctrl']['title'], TRUE);
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
	 * @return BackendLayoutView
	 */
	protected function getBackendLayoutView() {
		return GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Backend\\View\\BackendLayoutView'
		);
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return DatabaseConnection
	 */
	protected function getDatabase() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return PageLayoutController
	 */
	protected function getPageLayoutController() {
		return $GLOBALS['SOBE'];
	}

	/**
	 * @return DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		return $GLOBALS['TBE_TEMPLATE'];
	}

}
