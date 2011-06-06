<?php
/*************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Include file extending recordList which extended t3lib_recordList
 * Used specifically for the Web>List module (db_list.php)
 *
 * Revised for TYPO3 3.6 December/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   91: class localRecordList extends recordList
 *  123:     function writeTop($row)
 *  304:     function getTable($table,$id,$rowlist)
 *  559:     function renderListRow($table,$row,$cc,$titleCol,$thumbsCol,$indent=0)
 *  648:     function renderListHeader($table,$currentIdList)
 *
 *              SECTION: Rendering of various elements
 *  796:     function makeControl($table,$row)
 *  986:     function makeClip($table,$row)
 * 1057:     function makeRef($table,$uid)
 * 1086:     function makeLocalizationPanel($table,$row)
 * 1148:     function fieldSelectBox($table,$formFields=1)
 *
 *              SECTION: Helper functions
 * 1231:     function linkClipboardHeaderIcon($string,$table,$cmd,$warning='')
 * 1242:     function clipNumPane()
 * 1256:     function addSortLink($code,$field,$table)
 * 1281:     function recPath($pid)
 * 1294:     function showNewRecLink($table)
 * 1304:     function makeReturnUrl()
 *
 *              SECTION: CSV related functions
 * 1329:     function initCSV()
 * 1352:     function addToCSV($row,$table)
 * 1376:     function setCsvRow($csvRow)
 * 1387:     function outputCSV($prefix)
 *
 * TOTAL FUNCTIONS: 19
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */










/**
 * Class for rendering of Web>List module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localRecordList extends recordList {

		// External:
	var $alternateBgColors=FALSE;			// If TRUE, table rows in the list will alternate in background colors (and have background colors at all!)
	var $allowedNewTables=array();			// Used to indicate which tables (values in the array) that can have a create-new-record link. If the array is empty, all tables are allowed.
	var $deniedNewTables=array();			// Used to indicate which tables (values in the array) that cannot have a create-new-record link. If the array is empty, all tables are allowed.
	var $newWizards=FALSE;					// If TRUE, the control panel will contain links to the create-new wizards for pages and tt_content elements (normally, the link goes to just creating a new element without the wizards!).

	var $dontShowClipControlPanels=FALSE;	// If TRUE, will disable the rendering of clipboard + control panels.
	var $showClipboard=FALSE;				// If TRUE, will show the clipboard in the field list.
 	var $noControlPanels = FALSE;			// If TRUE, will DISABLE all control panels in lists. (Takes precedence)
	var $clickMenuEnabled = TRUE;			// If TRUE, clickmenus will be rendered

	var $totalRowCount;						// count of record rows in view

	var $spaceIcon;							// space icon used for alignment

		// Internal:
	var $pageRow=array();					// Set to the page record (see writeTop())

		// Used to accumulate CSV lines for CSV export.
	protected $csvLines = array();

	var $csvOutput=FALSE;					// If set, the listing is returned as CSV instead.

	/**
	 * Clipboard object
	 *
	 * @var t3lib_clipboard
	 */
	var $clipObj;
	var $CBnames=array();					// Tracking names of elements (for clipboard use)
	var $duplicateStack=array();			// Used to track which elements has duplicates and how many

	/**
	 * references of the current record
	 *
	 * @var array
	 *
	 * @deprecated since 4.4: Use getReferenceCount instead
	 */
	public $references;

	/**
	 * [$tablename][$uid] = number of references to this record
	 *
	 * @var array
	 */
	protected $referenceCount = array();

	var $translations;						// Translations of the current record
	var $selFieldList;						// select fields for the query which fetches the translations of the current record

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	public function getButtons()	{
		$buttons = array(
			'csh' => '',
			'view' => '',
			'edit' => '',
			'hide_unhide' => '',
			'move' => '',
			'new_record' => '',
			'paste' => '',
			'level_up' => '',
			'cache' => '',
			'reload' => '',
			'shortcut' => '',
			'back' => '',
			'csv' => '',
			'export' => ''
		);

			// Get users permissions for this page record:
		$localCalcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageRow);

			// CSH
		if (!strlen($this->id))	{
			$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_noId', $GLOBALS['BACK_PATH'], '', TRUE);
		} elseif(!$this->id) {
			$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module_root', $GLOBALS['BACK_PATH'], '', TRUE);
		} else {
			$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_module', $GLOBALS['BACK_PATH'], '', TRUE);
		}

		if (isset($this->id)) {
				// View  Exclude doktypes 254,255 Configuration: mod.web_list.noViewWithDokTypes = 254,255
			if (isset($GLOBALS['SOBE']->modTSconfig['properties']['noViewWithDokTypes'])) {
				$noViewDokTypes = t3lib_div::trimExplode(',', $GLOBALS['SOBE']->modTSconfig['properties']['noViewWithDokTypes'], TRUE);
			} else {
					//default exclusion: doktype 254 (folder), 255 (recycler)
				$noViewDokTypes = array(t3lib_pageSelect::DOKTYPE_SYSFOLDER, t3lib_pageSelect::DOKTYPE_RECYCLER);
			}

			if (!in_array($this->pageRow['doktype'], $noViewDokTypes)) {
				$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($this->id, $this->backPath, t3lib_BEfunc::BEgetRootLine($this->id))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-view') .
							'</a>';
			}

				// New record
			if (!$GLOBALS['SOBE']->modTSconfig['properties']['noCreateRecordsLink']) {
				$buttons['new_record'] = '<a href="#" onclick="' . htmlspecialchars('return jumpExt(\'' . $this->backPath . 'db_new.php?id=' . $this->id . '\');') . '" title="' . $GLOBALS['LANG']->getLL('newRecordGeneral', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-document-new') .
								'</a>';
			}

				// If edit permissions are set (see class.t3lib_userauthgroup.php)
			if ($localCalcPerms&2 && !empty($this->id))	{

					// Edit
				$params = '&edit[pages][' . $this->pageRow['uid'] . ']=edit';
				$buttons['edit'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) . '" title="' . $GLOBALS['LANG']->getLL('editPage', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-page-open') .
								'</a>';
					// Unhide
				if ($this->pageRow['hidden'])	{
					$params = '&data[pages][' . $this->pageRow['uid'] . '][hidden]=0';
					$buttons['hide_unhide'] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '" title="' . $GLOBALS['LANG']->getLL('unHidePage', TRUE) . '">' .
										t3lib_iconWorks::getSpriteIcon('actions-edit-unhide') .
									'</a>';
					// Hide
				} else {
					$params = '&data[pages][' . $this->pageRow['uid'] . '][hidden]=1';
					$buttons['hide_unhide'] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '" title="' . $GLOBALS['LANG']->getLL('hidePage', TRUE) . '">'.
										t3lib_iconWorks::getSpriteIcon('actions-edit-hide') .
									'</a>';
				}

					// Move
				$buttons['move'] = '<a href="#" onclick="' . htmlspecialchars('return jumpExt(\'' . $this->backPath . 'move_el.php?table=pages&uid=' . $this->pageRow['uid'] . '\');') . '" title="' . $GLOBALS['LANG']->getLL('move_page', TRUE) . '">' .
									(($this->table == 'tt_content') ? t3lib_iconWorks::getSpriteIcon('actions-document-move') : t3lib_iconWorks::getSpriteIcon('actions-page-move')) .
								'</a>';

					// Up one level
				$buttons['level_up'] = '<a href="' . htmlspecialchars($this->listURL($this->pageRow['pid'], '-1', 'firstElementNumber')) . '" onclick="setHighlight(' . $this->pageRow['pid'] . ')" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-view-go-up') .
							'</a>';

			}


				// Paste
			if (($localCalcPerms&8) || ($localCalcPerms&16)) {
				$elFromTable = $this->clipObj->elFromTable('');
				if (count($elFromTable)) {
					$buttons['paste'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('', $this->id)) . '" onclick="' . htmlspecialchars('return ' . $this->clipObj->confirmMsg('pages', $this->pageRow, 'into', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_paste', TRUE) . '">' .
										t3lib_iconWorks::getSpriteIcon('actions-document-paste-after') .
									'</a>';
				}
			}

				// Cache
			$buttons['cache'] = '<a href="' . htmlspecialchars($this->listURL() . '&clear_cache=1') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.clear_cache', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-system-cache-clear') .
							'</a>';

			if ($this->table) {

					// CSV
				$buttons['csv'] = '<a href="' . htmlspecialchars($this->listURL() . '&csv=1') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.csv', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('mimetypes-text-csv') .
								'</a>';

					// Export
				if (t3lib_extMgm::isLoaded('impexp')) {
					$url = $this->backPath . t3lib_extMgm::extRelPath('impexp') . 'app/index.php?tx_impexp[action]=export';
					$buttons['export'] = '<a href="' . htmlspecialchars($url . '&tx_impexp[list][]=' . rawurlencode($this->table . ':' . $this->id)) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.export', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-export-t3d') .
							'</a>';
				}

			}

				// Reload
			$buttons['reload'] = '<a href="' . htmlspecialchars($this->listURL()) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.reload', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-system-refresh') .
							'</a>';

				// Shortcut
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$buttons['shortcut'] = $GLOBALS['TBE_TEMPLATE']->makeShortcutIcon('id, imagemode, pointer, table, search_field, search_levels, showLimit, sortField, sortRev', implode(',', array_keys($this->MOD_MENU)), 'web_list');
			}

				// Back
			if ($this->returnUrl) {
				$buttons['back'] = '<a href="' . htmlspecialchars(t3lib_div::linkThisUrl($this->returnUrl, array('id' => $this->id))) . '" class="typo3-goBack" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.goBack', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-view-go-back') .
								'</a>';
			}
		}

		return $buttons;
	}

	/**
	 * Creates the listing of records from a single table
	 *
	 * @param	string		Table name
	 * @param	integer		Page id
	 * @param	string		List of fields to show in the listing. Pseudo fields will be added including the record header.
	 * @return	string		HTML table with the listing for the record.
	 */
	function getTable($table,$id,$rowlist)	{
		global $TYPO3_CONF_VARS;

			// Loading all TCA details for this table:
		t3lib_div::loadTCA($table);

			// Init
		$addWhere = '';
		$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
		$l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField'] && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
			&& !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'];
		$tableCollapsed = (!$this->tablesCollapsed[$table]) ? FALSE : TRUE;

		// prepare space icon
		$this->spaceIcon = t3lib_iconWorks::getSpriteIcon('empty-empty', array('style' => 'background-position: 0 10px;'));

			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray=array();
			// title Column
		$this->fieldArray[] = $titleCol;	// Add title column
			// Control-Panel
		if (!t3lib_div::inList($rowlist,'_CONTROL_'))	{
			$this->fieldArray[] = '_CONTROL_';
			$this->fieldArray[] = '_AFTERCONTROL_';
		}
			// Clipboard
		if ($this->showClipboard)	{
			$this->fieldArray[] = '_CLIPBOARD_';
		}
			// Ref
		if (!$this->dontShowClipControlPanels)	{
			$this->fieldArray[]='_REF_';
			$this->fieldArray[]='_AFTERREF_';
		}
			// Path
		if ($this->searchLevels)	{
			$this->fieldArray[]='_PATH_';
		}
			// Localization
		if ($this->localizationView && $l10nEnabled)	{
			$this->fieldArray[] = '_LOCALIZATION_';
			$this->fieldArray[] = '_LOCALIZATION_b';
			$addWhere.=' AND (
				' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '<=0
				OR
				' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . ' = 0
			)';
		}
			// Cleaning up:
		$this->fieldArray=array_unique(array_merge($this->fieldArray,t3lib_div::trimExplode(',',$rowlist,1)));
		if ($this->noControlPanels)	{
			$tempArray = array_flip($this->fieldArray);
			unset($tempArray['_CONTROL_']);
			unset($tempArray['_CLIPBOARD_']);
			$this->fieldArray = array_keys($tempArray);
		}

			// Creating the list of fields to include in the SQL query:
		$selectFields = $this->fieldArray;
		$selectFields[] = 'uid';
		$selectFields[] = 'pid';
		if ($thumbsCol)	$selectFields[] = $thumbsCol;	// adding column for thumbnails
		if ($table=='pages')	{
			if (t3lib_extMgm::isLoaded('cms'))	{
				$selectFields[] = 'module';
				$selectFields[] = 'extendToSubpages';
				$selectFields[] = 'nav_hide';
			}
			$selectFields[] = 'doktype';
		}
		if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
			$selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['type']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['type'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['typeicon_column']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			$selectFields[] = 't3ver_id';
			$selectFields[] = 't3ver_state';
			$selectFields[] = 't3ver_wsid';
			$selectFields[] = 't3ver_swapmode';		// Filtered out when pages in makeFieldList()
		}
		if ($l10nEnabled)	{
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['label_alt'])	{
			$selectFields = array_merge(
				$selectFields,
				t3lib_div::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], 1)
			);
		}
		$selectFields = array_unique($selectFields);		// Unique list!
		$selectFields = array_intersect($selectFields,$this->makeFieldList($table,1));		// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selFieldList = implode(',',$selectFields);		// implode it into a list of fields for the SQL-statement.
		$this->selFieldList = $selFieldList;

		/**
		 * @hook			DB-List getTable
		 * @date			2007-11-16
		 * @request		Malte Jansen  <mail@maltejansen.de>
		 */
		if(is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'])) {
			foreach($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);

				if(!($hookObject instanceof t3lib_localRecordListGetTableHook)) {
					throw new UnexpectedValueException('$hookObject must implement interface t3lib_localRecordListGetTableHook', 1195114460);
				}

				$hookObject->getDBlistQuery($table, $id, $addWhere, $selFieldList, $this);
			}
		}

			// Create the SQL query for selecting the elements in the listing:
		if ($this->csvOutput) {	// do not do paging when outputting as CSV
			$this->iLimit = 0;
		}

		if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
				// Get the two previous rows for sorting if displaying page > 1
			$this->firstElementNumber = $this->firstElementNumber - 2;
			$this->iLimit = $this->iLimit + 2;
			$queryParts = $this->makeQueryArray($table, $id,$addWhere,$selFieldList);	// (API function from class.db_list.inc)
			$this->firstElementNumber = $this->firstElementNumber + 2;
			$this->iLimit = $this->iLimit - 2;
		} else {
			$queryParts = $this->makeQueryArray($table, $id,$addWhere,$selFieldList);	// (API function from class.db_list.inc)
		}

		$this->setTotalItems($queryParts);		// Finding the total amount of records on the page (API function from class.db_list.inc)

			// Init:
		$dbCount = 0;
		$out = '';
		$listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;

			// If the count query returned any number of records, we perform the real query, selecting records.
		if ($this->totalItems)	{
			// Fetch records only if not in single table mode or if in multi table mode and not collapsed
			if ($listOnlyInSingleTableMode || (!$this->table && $tableCollapsed)) {
				$dbCount = $this->totalItems;
			} else {
					// set the showLimit to the number of records when outputting as CSV
				if ($this->csvOutput) {
					$this->showLimit = $this->totalItems;
					$this->iLimit = $this->totalItems;
				}
				$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
				$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
			}
		}

			// If any records was selected, render the list:
		if ($dbCount)	{

				// Half line is drawn between tables:
			if (!$listOnlyInSingleTableMode)	{
				$theData = Array();
				if (!$this->table && !$rowlist)	{
					$theData[$titleCol] = '<img src="clear.gif" width="'.($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel']?'230':'350').'" height="1" alt="" />';
					if (in_array('_CONTROL_',$this->fieldArray))	$theData['_CONTROL_']='';
					if (in_array('_CLIPBOARD_',$this->fieldArray))	$theData['_CLIPBOARD_']='';
				}
				$out.=$this->addelement(0,'',$theData,'class="c-table-row-spacer"',$this->leftMargin);
			}

				// Header line is drawn
			$theData = Array();
			if ($this->disableSingleTableView)	{
				$theData[$titleCol] = '<span class="c-table">' . t3lib_BEfunc::wrapInHelp($table, '', $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title'], TRUE)) . '</span> ('.$this->totalItems.')';
			} else {
				$theData[$titleCol] = $this->linkWrapTable($table, '<span class="c-table">' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title'], TRUE) . '</span> (' . $this->totalItems . ') ' .
						($this->table ? t3lib_iconWorks::getSpriteIcon('actions-view-table-collapse', array('title' => $GLOBALS['LANG']->getLL('contractView', TRUE))) : t3lib_iconWorks::getSpriteIcon('actions-view-table-expand', array('title' => $GLOBALS['LANG']->getLL('expandView', TRUE))))
					);
			}

			if ($listOnlyInSingleTableMode)	{
				$out.='
					<tr>
						<td class="t3-row-header" style="width:95%;">' . t3lib_BEfunc::wrapInHelp($table, '', $theData[$titleCol]) . '</td>
					</tr>';
			} else {
				// Render collapse button if in multi table mode
				$collapseIcon = '';
				if (!$this->table) {
					$collapseIcon = '<a href="' . htmlspecialchars($this->listURL() . '&collapse[' . $table . ']=' . ($tableCollapsed ? '0' : '1')) . '" title="' . ($tableCollapsed ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.expandTable', TRUE) : $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.collapseTable', TRUE)) . '">' .
							($tableCollapsed ? t3lib_iconWorks::getSpriteIcon('actions-view-list-expand', array('class' => 'collapseIcon')) : t3lib_iconWorks::getSpriteIcon('actions-view-list-collapse', array('class' => 'collapseIcon'))) .
						'</a>';
				}
				$out .= $this->addElement(1, $collapseIcon, $theData, ' class="t3-row-header"', '');
			}

			// Render table rows only if in multi table view and not collapsed or if in single table view
			if (!$listOnlyInSingleTableMode && (!$tableCollapsed || $this->table)) {
					// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = ($GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField);

				$prevUid = 0;
				$prevPrevUid = 0;

					// Get first two rows and initialize prevPrevUid and prevUid if on page > 1
				if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
					$prevPrevUid = -(int) $row['uid'];
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
					$prevUid = $row['uid'];
				}

				$accRows = array();	// Accumulate rows here
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{

						// In offline workspace, look for alternative record:
					t3lib_BEfunc::workspaceOL($table, $row, $GLOBALS['BE_USER']->workspace, TRUE);

					if (is_array($row))	{
						$accRows[] = $row;
						$currentIdList[] = $row['uid'];
						if ($doSort)	{
							if ($prevUid)	{
								$this->currentTable['prev'][$row['uid']] = $prevPrevUid;
								$this->currentTable['next'][$prevUid] = '-'.$row['uid'];
								$this->currentTable['prevUid'][$row['uid']] = $prevUid;
							}
							$prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
							$prevUid=$row['uid'];
						}
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($result);

				$this->totalRowCount = count($accRows);

					// CSV initiated
				if ($this->csvOutput) $this->initCSV();

					// Render items:
				$this->CBnames=array();
				$this->duplicateStack=array();
				$this->eCounter=$this->firstElementNumber;

				$iOut = '';
				$cc = 0;

				foreach($accRows as $row)	{
					// Render item row if counter < limit
					if ($cc < $this->iLimit) {
						$cc++;
						$this->translations = FALSE;
						$iOut.= $this->renderListRow($table,$row,$cc,$titleCol,$thumbsCol);

							// If localization view is enabled it means that the selected records are either default or All language and here we will not select translations which point to the main record:
						if ($this->localizationView && $l10nEnabled)	{
								// For each available translation, render the record:
							if (is_array($this->translations)) {
								foreach ($this->translations as $lRow) {
										// $lRow isn't always what we want - if record was moved we've to work with the placeholder records otherwise the list is messed up a bit
									if ($row['_MOVE_PLH_uid'] && $row['_MOVE_PLH_pid']) {
										$tmpRow = t3lib_BEfunc::getRecordRaw($table, 't3ver_move_id="'.intval($lRow['uid']) . '" AND pid="' . $row['_MOVE_PLH_pid'] . '" AND t3ver_wsid=' . $row['t3ver_wsid'] . t3lib_beFunc::deleteClause($table), $selFieldList);
										$lRow = is_array($tmpRow)?$tmpRow:$lRow;
									}
										// In offline workspace, look for alternative record:
									t3lib_BEfunc::workspaceOL($table, $lRow, $GLOBALS['BE_USER']->workspace, TRUE);
									if (is_array($lRow) && $GLOBALS['BE_USER']->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
										$currentIdList[] = $lRow['uid'];
										$iOut.=$this->renderListRow($table,$lRow,$cc,$titleCol,$thumbsCol,18);
									}
								}
							}
						}
					}

						// Counter of total rows incremented:
					$this->eCounter++;
				}

					// Record navigation is added to the beginning and end of the table if in single table mode
				if ($this->table) {
					$iOut = $this->renderListNavigation('top') . $iOut . $this->renderListNavigation('bottom');
				} else {
						// show that there are more records than shown
					if ($this->totalItems > $this->itemsLimitPerTable) {
						$countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable ? $this->itemsLimitSingleTable : $this->totalItems;
						$hasMore = ($this->totalItems > $this->itemsLimitSingleTable);
						$iOut .= '<tr><td colspan="' . count($this->fieldArray) . '" style="padding:5px;">
								<a href="'.htmlspecialchars($this->listURL() . '&table=' . rawurlencode($table)) . '">' .
								'<img' . t3lib_iconWorks::skinImg($this->backPath,'gfx/pildown.gif', 'width="14" height="14"') .' alt="" />'.
								' <i>[1 - ' . $countOnFirstPage . ($hasMore ? '+' : '') . ']</i></a>
								</td></tr>';
						}

				}

					// The header row for the table is now created:
				$out .= $this->renderListHeader($table,$currentIdList);
			}

				// The list of records is added after the header:
			$out .= $iOut;
			unset($iOut);

				// ... and it is all wrapped in a table:
			$out='



			<!--
				DB listing of elements:	"'.htmlspecialchars($table).'"
			-->
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist'.($listOnlyInSingleTableMode?' typo3-dblist-overview':'').'">
					'.$out.'
				</table>';

				// Output csv if...
			if ($this->csvOutput)	$this->outputCSV($table);	// This ends the page with exit.
		}

			// Return content:
		return $out;
	}

	/**
	 * Rendering a single row for the list
	 *
	 * @param	string		Table name
	 * @param	array		Current record
	 * @param	integer		Counter, counting for each time an element is rendered (used for alternating colors)
	 * @param	string		Table field (column) where header value is found
	 * @param	string		Table field (column) where (possible) thumbnails can be found
	 * @param	integer		Indent from left.
	 * @return	string		Table row for the element
	 * @access private
	 * @see getTable()
	 */
	function renderListRow($table,$row,$cc,$titleCol,$thumbsCol,$indent=0)	{
		$iOut = '';

		if (strlen($this->searchString))	{	// If in search mode, make sure the preview will show the correct page
			$id_orig = $this->id;
			$this->id = $row['pid'];
		}

		if (is_array($row))	{
				// add special classes for first and last row
			$rowSpecial = '';
			if ($cc == 1 && $indent == 0) {
				$rowSpecial .= ' firstcol';
			}
			if ($cc == $this->totalRowCount || $cc == $this->iLimit) {
				$rowSpecial .= ' lastcol';
			}

				// Background color, if any:
			if ($this->alternateBgColors) {
				$row_bgColor = ($cc%2) ? ' class="db_list_normal'.$rowSpecial.'"' : ' class="db_list_alt'.$rowSpecial.'"';
			} else {
				$row_bgColor = ' class="db_list_normal'.$rowSpecial.'"';
			}
				// Overriding with versions background color if any:
			$row_bgColor = $row['_CSSCLASS'] ? ' class="'.$row['_CSSCLASS'].'"' : $row_bgColor;

				// Incr. counter.
			$this->counter++;

				// The icon with link
			$alttext = t3lib_BEfunc::getRecordIconAltText($row,$table);
			$iconImg = t3lib_iconWorks::getSpriteIconForRecord($table, $row, array('title' => htmlspecialchars($alttext), 'style' => ($indent ? ' margin-left: ' . $indent . 'px;' : '')));


			$theIcon = $this->clickMenuEnabled ? $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg,$table,$row['uid']) : $iconImg;

				// Preparing and getting the data-array
			$theData = Array();
			foreach($this->fieldArray as $fCol)	{
				if ($fCol==$titleCol)	{
					$recTitle = t3lib_BEfunc::getRecordTitle($table,$row,FALSE,TRUE);
						// If the record is edit-locked	by another user, we will show a little warning sign:
					if (($lockInfo = t3lib_BEfunc::isRecordLocked($table, $row['uid']))) {
						$warning = '<a href="#" onclick="' . htmlspecialchars('alert(' . $GLOBALS['LANG']->JScharCode($lockInfo['msg']) . '); return false;') . '" title="' . htmlspecialchars($lockInfo['msg']) . '">' .
								t3lib_iconWorks::getSpriteIcon('status-warning-in-use') .
							'</a>';
					}
					$theData[$fCol] = $warning . $this->linkWrapItems($table, $row['uid'], $recTitle, $row);

						// Render thumbsnails if a thumbnail column exists and there is content in it:
					if ($this->thumbs && trim($row[$thumbsCol])) {
						$theData[$fCol] .= '<br />' . $this->thumbCode($row,$table,$thumbsCol);
					}

					$localizationMarkerClass = '';
					if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
					&& $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] != 0
					&& $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0) {
							// it's a translated record with a language parent
						$localizationMarkerClass = ' localization';
					}
				} elseif ($fCol == 'pid') {
					$theData[$fCol]=$row[$fCol];
				} elseif ($fCol == '_PATH_') {
					$theData[$fCol]=$this->recPath($row['pid']);
				} elseif ($fCol == '_REF_') {
					$theData[$fCol] = $this->createReferenceHtml($table, $row['uid']);
				} elseif ($fCol == '_CONTROL_') {
					$theData[$fCol]=$this->makeControl($table,$row);
				} elseif ($fCol == '_AFTERCONTROL_' || $fCol == '_AFTERREF_') {
					$theData[$fCol] = '&nbsp;';
				} elseif ($fCol == '_CLIPBOARD_') {
					$theData[$fCol]=$this->makeClip($table,$row);
				} elseif ($fCol == '_LOCALIZATION_') {
					list($lC1, $lC2) = $this->makeLocalizationPanel($table,$row);
					$theData[$fCol] = $lC1;
					$theData[$fCol.'b'] = $lC2;
				} elseif ($fCol == '_LOCALIZATION_b') {
					// Do nothing, has been done above.
				} else {
					$tmpProc = t3lib_BEfunc::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid']);
					$theData[$fCol] = $this->linkUrlMail(htmlspecialchars($tmpProc), $row[$fCol]);
					if ($this->csvOutput) {
						$row[$fCol] = t3lib_BEfunc::getProcessedValueExtra($table, $fCol, $row[$fCol], 0, $row['uid']);
					}
				}
			}

			if (strlen($this->searchString))	{	// Reset the ID if it was overwritten
				$this->id = $id_orig;
			}

				// Add row to CSV list:
			if ($this->csvOutput) {
				$this->addToCSV($row,$table);
			}

			// Add classes to table cells
			$this->addElement_tdCssClass[$titleCol]         = 'col-title' . $localizationMarkerClass;
			if (!$this->dontShowClipControlPanels) {
				$this->addElement_tdCssClass['_CONTROL_']       = 'col-control';
				$this->addElement_tdCssClass['_AFTERCONTROL_']  = 'col-control-space';
				$this->addElement_tdCssClass['_CLIPBOARD_']     = 'col-clipboard';
			}
			$this->addElement_tdCssClass['_PATH_']          = 'col-path';
			$this->addElement_tdCssClass['_LOCALIZATION_']  = 'col-localizationa';
			$this->addElement_tdCssClass['_LOCALIZATION_b'] = 'col-localizationb';

				// Create element in table cells:
			$iOut.=$this->addelement(1,$theIcon,$theData,$row_bgColor);

				// Finally, return table row element:
			return $iOut;
		}
	}

	/**
	 * Write sys_refindex entries for current record to $this->references
	 *
	 * @param	string		Table name
	 * @param	integer		Uid of current record
	 * @return	void
	 *
	 * @deprecated since 4.4, will be removed in TYPO3 4.6 - Use getReferenceCount instead
	 */
	function setReferences($table, $uid) {
		t3lib_div::logDeprecatedFunction();

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tablename, recuid, field',
			'sys_refindex',
			'ref_table='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table,'sys_refindex').
				' AND ref_uid='.intval($uid).
				' AND deleted=0'
		);
		$this->references = $rows;
	}

	/**
	 * Gets the number of records referencing the record with the UID $uid in
	 * the table $tableName.
	 *
	 * @param string $tableName
	 *        table name of the referenced record, must not be empty
	 * @param integer $uid
	 *        UID of the referenced record, must be > 0
	 *
	 * @return integer the number of references to record $uid in table
	 *                 $tableName, will be >= 0
	 */
	protected function getReferenceCount($tableName, $uid) {
		if (!isset($this->referenceCount[$tableName][$uid])) {
			$numberOfReferences = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'*',
				'sys_refindex',
				'ref_table = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
					$tableName, 'sys_refindex'
				) .
					' AND ref_uid = ' . $uid .
					' AND deleted = 0'
			);

			$this->referenceCount[$tableName][$uid] = $numberOfReferences;
		}

		return $this->referenceCount[$tableName][$uid];
	}

	/**
	 * Rendering the header row for a table
	 *
	 * @param	string		Table name
	 * @param	array		Array of the currently displayed uids of the table
	 * @return	string		Header table row
	 * @access private
	 * @see getTable()
	 */
	function renderListHeader($table, $currentIdList)	{
		global $TYPO3_CONF_VARS;

			// Init:
		$theData = Array();

			// Traverse the fields:
		foreach($this->fieldArray as $fCol)	{

				// Calculate users permissions to edit records in the table:
			$permsEdit = $this->calcPerms & ($table=='pages'?2:16);

			switch((string)$fCol)	{
				case '_PATH_':			// Path
					$theData[$fCol] = '<i>[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels._PATH_', 1) . ']</i>';
				break;
				case '_REF_':			// References
					$theData[$fCol] = '<i>[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:c__REF_', 1) . ']</i>';
				break;
				case '_LOCALIZATION_':			// Path
					$theData[$fCol] = '<i>[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels._LOCALIZATION_', 1) . ']</i>';
				break;
				case '_LOCALIZATION_b':			// Path
					$theData[$fCol] = $GLOBALS['LANG']->getLL('Localize', 1);
				break;
				case '_CLIPBOARD_':		// Clipboard:
					$cells=array();

						// If there are elements on the clipboard for this table, then display the "paste into" icon:
					$elFromTable = $this->clipObj->elFromTable($table);
					if (count($elFromTable))	{
						$cells['pasteAfter'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl($table, $this->id)) .
							'" onclick="' . htmlspecialchars('return ' . $this->clipObj->confirmMsg('pages', $this->pageRow, 'into' ,$elFromTable)) .
							'" title="' . $GLOBALS['LANG']->getLL('clip_paste', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-paste-after') . '</a>';
					}

						// If the numeric clipboard pads are enabled, display the control icons for that:
					if ($this->clipObj->current!='normal')	{

							// The "select" link:
						$cells['copyMarked'] = $this->linkClipboardHeaderIcon(
							t3lib_iconWorks::getSpriteIcon(
								'actions-edit-copy',
								array('title' => $GLOBALS['LANG']->getLL('clip_selectMarked', TRUE))
							),
							$table,
							'setCB');

							// The "edit marked" link:
						$editIdList = implode(',',$currentIdList);
						$editIdList = "'+editList('".$table."','".$editIdList."')+'";
						$params='&edit['.$table.']['.$editIdList.']=edit&disHelp=1';
						$cells['edit'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->backPath, -1)) .
								'" title="' . $GLOBALS['LANG']->getLL('clip_editMarked', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-open') .
								'</a>';

							// The "Delete marked" link:
						$cells['delete'] = $this->linkClipboardHeaderIcon(
							t3lib_iconWorks::getSpriteIcon(
								'actions-edit-delete',
								array('title' => $GLOBALS['LANG']->getLL('clip_deleteMarked', TRUE))
							),
							$table,
							'delete',
							sprintf($GLOBALS['LANG']->getLL('clip_deleteMarkedWarning'), $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']))
						);

							// The "Select all" link:
						$cells['markAll'] = '<a class="cbcCheckAll" rel="" href="#" onclick="' .
								htmlspecialchars('checkOffCB(\'' . implode(',', $this->CBnames) . '\', this); return false;') .
								'" title="' . $GLOBALS['LANG']->getLL('clip_markRecords', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-select') .
								'</a>';
					} else {
						$cells['empty']='';
					}
					/**
					 * @hook			renderListHeaderActions: Allows to change the clipboard icons of the Web>List table headers
					 * @date			2007-11-20
					 * @request		Bernhard Kraft  <krafbt@kraftb.at>
					 * @usage		Above each listed table in Web>List a header row is shown. This hook allows to modify the icons responsible for the clipboard functions (shown above the clipboard checkboxes when a clipboard other than "Normal" is selected), or other "Action" functions which perform operations on the listed records.
					 */
					if(is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions']))	{
						foreach($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData)	{
							$hookObject = t3lib_div::getUserObj($classData);
							if(!($hookObject instanceof localRecordList_actionsHook))	{
								throw new UnexpectedValueException('$hookObject must implement interface localRecordList_actionsHook', 1195567850);
							}
							$cells = $hookObject->renderListHeaderActions($table, $currentIdList, $cells, $this);
						}
					}
					$theData[$fCol]=implode('',$cells);
				break;
				case '_CONTROL_':		// Control panel:
					if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {

							// If new records can be created on this page, add links:
						if ($this->calcPerms&($table=='pages'?8:16) && $this->showNewRecLink($table))	{
							if ($table=="tt_content" && $this->newWizards)	{
									//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
								$tmpTSc = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'],'mod.web_list');
								$tmpTSc = $tmpTSc['properties']['newContentWiz.']['overrideWithExtension'];
								$newContentWizScriptPath = $this->backPath.t3lib_extMgm::isLoaded($tmpTSc) ? (t3lib_extMgm::extRelPath($tmpTSc).'mod1/db_new_content_el.php') : 'sysext/cms/layout/db_new_content_el.php';

								$icon = '<a href="#" onclick="' . htmlspecialchars('return jumpExt(\'' . $newContentWizScriptPath . '?id=' . $this->id . '\');') . '" title="' . $GLOBALS['LANG']->getLL('new', TRUE) . '">'.
													($table == 'pages' ? t3lib_iconWorks::getSpriteIcon('actions-page-new') : t3lib_iconWorks::getSpriteIcon('actions-document-new')) .
												'</a>';
							} elseif ($table=='pages' && $this->newWizards)	{
								$icon = '<a href="' . htmlspecialchars($this->backPath . 'db_new.php?id=' . $this->id . '&pagesOnly=1&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '" title="' . $GLOBALS['LANG']->getLL('new', TRUE) . '">'.
													($table=='pages' ? t3lib_iconWorks::getSpriteIcon('actions-page-new') : t3lib_iconWorks::getSpriteIcon('actions-document-new')) .
												'</a>';

							} else {
								$params = '&edit['.$table.']['.$this->id.']=new';
								if ($table == 'pages_language_overlay') {
									$params .= '&overrideVals[pages_language_overlay][doktype]=' . (int) $this->pageRow['doktype'];
								}
								$icon   = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) . '" title="' . $GLOBALS['LANG']->getLL('new', TRUE) . '">'.
													($table=='pages' ? t3lib_iconWorks::getSpriteIcon('actions-page-new') : t3lib_iconWorks::getSpriteIcon('actions-document-new')) .
												'</a>';
							}
						}

							// If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
						if ($permsEdit && $this->table && is_array($currentIdList))	{
							$editIdList = implode(',',$currentIdList);
							if ($this->clipNumPane()) $editIdList = "'+editList('".$table."','".$editIdList."')+'";
							$params = '&edit['.$table.']['.$editIdList.']=edit&columnsOnly='.implode(',',$this->fieldArray).'&disHelp=1';
							$icon  .= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1 )) . '" title="' . $GLOBALS['LANG']->getLL('editShownColumns', TRUE) . '">' .
												t3lib_iconWorks::getSpriteIcon('actions-document-open') .
											'</a>';
						}
							// add an empty entry, so column count fits again after moving this into $icon
						$theData[$fCol] = '&nbsp;';
					}
				break;
				case '_AFTERCONTROL_':  // space column
				case '_AFTERREF_':	// space column
					$theData[$fCol] = '&nbsp;';
				break;
				default:			// Regular fields header:
					$theData[$fCol]='';
					if ($this->table && is_array($currentIdList))	{

							// If the numeric clipboard pads are selected, show duplicate sorting link:
						if ($this->clipNumPane()) {
							$theData[$fCol] .= '<a href="' . htmlspecialchars($this->listURL('', -1) . '&duplicateField=' . $fCol) . '" title="' . $GLOBALS['LANG']->getLL('clip_duplicates', TRUE) . '">' .
												t3lib_iconWorks::getSpriteIcon('actions-document-duplicates-select') .
											'</a>';
						}

							// If the table can be edited, add link for editing THIS field for all listed records:
						if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly'] && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
							$editIdList = implode(',',$currentIdList);
							if ($this->clipNumPane()) $editIdList = "'+editList('".$table."','".$editIdList."')+'";
							$params='&edit['.$table.']['.$editIdList.']=edit&columnsOnly='.$fCol.'&disHelp=1';
							$iTitle = sprintf($GLOBALS['LANG']->getLL('editThisColumn'), rtrim(trim($GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($table, $fCol))), ':'));
							$theData[$fCol].='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->backPath,-1)).'" title="'.htmlspecialchars($iTitle).'">'.
												t3lib_iconWorks::getSpriteIcon('actions-document-open') .
											'</a>';
						}
					}
					$theData[$fCol] .= $this->addSortLink($GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($table, $fCol, '<i>[|]</i>')), $fCol, $table);
				break;
			}

		}

		/**
		 * @hook			renderListHeader: Allows to change the contents of columns/cells of the Web>List table headers
		 * @date			2007-11-20
		 * @request		Bernhard Kraft  <krafbt@kraftb.at>
		 * @usage		Above each listed table in Web>List a header row is shown. Containing the labels of all shown fields and additional icons to create new records for this table or perform special clipboard tasks like mark and copy all listed records to clipboard, etc.
		 */
		if(is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions']))	{
			foreach($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData)	{
				$hookObject = t3lib_div::getUserObj($classData);
				if(!($hookObject instanceof localRecordList_actionsHook))	{
					throw new UnexpectedValueException('$hookObject must implement interface localRecordList_actionsHook', 1195567855);
				}
				$theData = $hookObject->renderListHeader($table, $currentIdList, $theData, $this);
			}
		}

			// Create and return header table row:
		return $this->addelement(1, $icon, $theData, ' class="c-headLine"', '');
	}

	/**
	 * Creates a page browser for tables with many records
	 *
	 * @param	string		Distinguish between 'top' and 'bottom' part of the navigation (above or below the records)
	 * @return	string	Navigation HTML
	 *
	 */
	protected function renderListNavigation($renderPart = 'top') {
		$totalPages = ceil($this->totalItems / $this->iLimit);

		$content = '';
		$returnContent = '';

			// Show page selector if not all records fit into one page
		if ($totalPages > 1) {
			$first = $previous = $next = $last = $reload = '';
			$listURL = $this->listURL('', $this->table);

				// 1 = first page
			$currentPage = floor(($this->firstElementNumber + 1) / $this->iLimit) + 1;

				// Compile first, previous, next, last and refresh buttons
			if ($currentPage > 1) {
				$labelFirst = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:first');

				$first = '<a href="' . $listURL . '&pointer=0">' .
					t3lib_iconWorks::getSpriteIcon('actions-view-paging-first', array('title'=> $labelFirst)) .
				'</a>';
			} else {
				$first = t3lib_iconWorks::getSpriteIcon('actions-view-paging-first-disabled');
			}

			if (($currentPage - 1) > 0) {
				$labelPrevious = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:previous');

				$previous = '<a href="' . $listURL . '&pointer=' . (($currentPage - 2) * $this->iLimit) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-view-paging-previous', array('title' => $labelPrevious)) .
					'</a>';
			} else {
				$previous = t3lib_iconWorks::getSpriteIcon('actions-view-paging-previous-disabled');
			}

			if (($currentPage + 1) <= $totalPages) {
				$labelNext = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:next');

				$next = '<a href="' . $listURL . '&pointer=' . (($currentPage) * $this->iLimit) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-view-paging-next', array('title' => $labelNext)) .
					'</a>';
			} else {
				$next = t3lib_iconWorks::getSpriteIcon('actions-view-paging-next-disabled');
			}

			if ($currentPage != $totalPages) {
				$labelLast = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:last');

				$last = '<a href="' . $listURL . '&pointer=' . (($totalPages - 1) * $this->iLimit) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-view-paging-last', array('title' => $labelLast)) .
					'</a>';
			} else {
				$last = t3lib_iconWorks::getSpriteIcon('actions-view-paging-last-disabled');
			}

			$reload = '<a href="#" onclick="document.dblistForm.action=\''
				. $listURL . '&pointer=\'+calculatePointer(document.getElementById(\'jumpPage-' . $renderPart .'\').value); document.dblistForm.submit(); return true;" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:reload', TRUE) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-system-refresh') .
				'</a>';

			if ($renderPart === 'top') {
					// Add js to traverse a page select input to a pointer value
				$content = '
<script type="text/JavaScript">
/*<![CDATA[*/

	function calculatePointer(page) {
		if (page > ' . $totalPages . ') {
			page = ' . $totalPages . ';
		}

		if (page < 1) {
			page = 1;
		}

		pointer = (page - 1) * ' . $this->iLimit . ';

		return pointer;
	}

/*]]>*/
</script>
';
			}
			$pageNumberInput = '<span>
				<input type="text" value="' . $currentPage
				. '" size="3" id="jumpPage-' . $renderPart . '" name="jumpPage-' . $renderPart . '" onkeyup="if (event.keyCode == Event.KEY_RETURN) { document.dblistForm.action=\'' . $listURL . '&pointer=\'+calculatePointer(this.value); document.dblistForm.submit(); } return true;" />
				</span>';
			$pageIndicator = '<span class="pageIndicator">'
				. sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xml:pageIndicator'), $pageNumberInput, $totalPages)
				. '</span>';

			if ($this->totalItems > ($this->firstElementNumber + $this->iLimit)) {
				$lastElementNumber = $this->firstElementNumber + $this->iLimit;
			} else {
				$lastElementNumber = $this->totalItems;
			}
			$rangeIndicator = '<span class="pageIndicator">'
				. sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xml:rangeIndicator'), $this->firstElementNumber + 1, $lastElementNumber)
				. '</span>';

			$content .= '<div id="typo3-dblist-pagination">'
				. $first . $previous
				. '<span class="bar">&nbsp;</span>'
				. $rangeIndicator . '<span class="bar">&nbsp;</span>'
				. $pageIndicator . '<span class="bar">&nbsp;</span>'
				. $next . $last . '<span class="bar">&nbsp;</span>'
				. $reload
				. '</div>';

			$data = Array();
			$titleColumn = $this->fieldArray[0];
			$data[$titleColumn] = $content;

			$returnContent = $this->addElement(1, '', $data);
		} // end of if pages > 1

		return $returnContent;
	}






	/*********************************
	 *
	 * Rendering of various elements
	 *
	 *********************************/

	/**
	 * Creates the control panel for a single record in the listing.
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the control panel.
	 * @return	string		HTML table with the control panel (unless disabled)
	 */
	function makeControl($table,$row)	{
		global $SOBE, $TYPO3_CONF_VARS;

		if ($this->dontShowClipControlPanels)	return '';

			// Initialize:
		t3lib_div::loadTCA($table);
		$cells=array();

			// If the listed table is 'pages' we have to request the permission settings for each page:
		if ($table=='pages')	{
			$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$row['uid']));
		}

			// This expresses the edit permissions for this particular element:
		$permsEdit = ($table=='pages' && ($localCalcPerms&2)) || ($table!='pages' && ($this->calcPerms&16));

			// "Show" link (only pages and tt_content elements)
		if ($table=='pages' || $table=='tt_content')	{
			$params='&edit['.$table.']['.$row['uid'].']=edit';
			$cells['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick(
					$table=='tt_content' ? $this->id . '#' . $row['uid'] : $row['uid'],
					$this->backPath)
				) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-view') . '</a>';
		} elseif(!$this->table) {
			$cells['view'] = $this->spaceIcon;
		}

			// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		if ($permsEdit)	{
			$params='&edit['.$table.']['.$row['uid'].']=edit';
			$cells['edit'] = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) .
				'" title="' . $GLOBALS['LANG']->getLL('edit', TRUE) . '">' .
				( $GLOBALS['TCA'][$table]['ctrl']['readOnly'] ? t3lib_iconWorks::getSpriteIcon('actions-document-open-read-only') : t3lib_iconWorks::getSpriteIcon('actions-document-open')) .
				'</a>';
		} elseif(!$this->table) {
			$cells['edit'] = $this->spaceIcon;
		}

			// "Move" wizard link for pages/tt_content elements:
		if (($table=="tt_content" && $permsEdit) || ($table=='pages'))	{
			$cells['move'] = '<a href="#" onclick="' .
				htmlspecialchars(
					'return jumpExt(\'' . $this->backPath . 'move_el.php?table=' . $table . '&uid='.$row['uid'] . '\');'
				) .'" title="' . $GLOBALS['LANG']->getLL('move_' . ($table == 'tt_content' ? 'record' : 'page'), TRUE) . '">' .
						($table == 'tt_content' ? t3lib_iconWorks::getSpriteIcon('actions-document-move') : t3lib_iconWorks::getSpriteIcon('actions-page-move')) .
					'</a>';
		} elseif(!$this->table) {
			$cells['move'] = $this->spaceIcon;
		}

			// If the extended control panel is enabled OR if we are seeing a single table:
		if ($SOBE->MOD_SETTINGS['bigControlPanel'] || $this->table)	{

				// "Info": (All records)
			$cells['viewBig'] = '<a href="#" onclick="' . htmlspecialchars(
					'top.launchView(\'' . $table . '\', \''.$row['uid'] . '\'); return false;'
				) . '" title="' . $GLOBALS['LANG']->getLL('showInfo', TRUE) . '">'.
				t3lib_iconWorks::getSpriteIcon('actions-document-info') .
				'</a>';

				// If the table is NOT a read-only table, then show these links:
			if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {

					// "Revert" link (history/undo)
				$cells['history'] = '<a href="#" onclick="' . htmlspecialchars(
					'return jumpExt(\'' . $this->backPath . 'show_rechis.php?element=' . rawurlencode($table . ':' . $row['uid']) . '\',\'#latest\');') .
					'" title="' . $GLOBALS['LANG']->getLL('history', TRUE) . '">'.
					t3lib_iconWorks::getSpriteIcon('actions-document-history-open') .
					'</a>';

					// Versioning:
				if (t3lib_extMgm::isLoaded('version'))	{
					$vers = t3lib_BEfunc::selectVersionsOfRecord($table, $row['uid'], 'uid', $GLOBALS['BE_USER']->workspace, FALSE, $row);
					if (is_array($vers))	{	// If table can be versionized.
						$versionIcon = 'no-version';
						if (count($vers) > 1) {
							$versionIcon = count($vers) - 1;
						}

						$cells['version'] = '<a href="' . htmlspecialchars($this->backPath . t3lib_extMgm::extRelPath('version') . 'cm1/index.php?table=' . rawurlencode($table) . '&uid=' . rawurlencode($row['uid'])) . '" title="' . $GLOBALS['LANG']->getLL('displayVersions', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('status-version-' . $versionIcon) .
								'</a>';
					} elseif(!$this->table) {
						$cells['version'] = $this->spaceIcon;
					}
				}

					// "Edit Perms" link:
				if ($table == 'pages' && $GLOBALS['BE_USER']->check('modules','web_perm') && t3lib_extMgm::isLoaded('perm'))	{
					$cells['perms'] =
						'<a href="' .
							htmlspecialchars(
								t3lib_extMgm::extRelPath('perm') . 'mod1/index.php' .
								'?id=' . $row['uid'] . '&return_id=' . $row['uid'] . '&edit=1'
							) .
							'" title="' . $GLOBALS['LANG']->getLL('permissions', TRUE) .
						'">'.
							t3lib_iconWorks::getSpriteIcon('status-status-locked') .
						'</a>';
				} elseif(!$this->table && $GLOBALS['BE_USER']->check('modules','web_perm')) {
					$cells['perms'] = $this->spaceIcon;
				}

					// "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
				if ($GLOBALS['TCA'][$table]['ctrl']['sortby'] || $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']) {
					if (
						($table!='pages' && ($this->calcPerms&16)) || 	// For NON-pages, must have permission to edit content on this parent page
						($table=='pages' && ($this->calcPerms&8))		// For pages, must have permission to create new pages here.
						)	{
						if ($this->showNewRecLink($table))	{
							$params='&edit['.$table.']['.(-($row['_MOVE_PLH']?$row['_MOVE_PLH_uid']:$row['uid'])).']=new';
							$cells['new'] = '<a href="#" onclick="' . htmlspecialchars(
								t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) .
								'" title="' . $GLOBALS['LANG']->getLL('new' . ($table == 'pages '? 'Page' : 'Record'), TRUE) . '">' .
								($table == 'pages' ? t3lib_iconWorks::getSpriteIcon('actions-page-new') : t3lib_iconWorks::getSpriteIcon('actions-document-new')) .
								'</a>';
						}
					}
				} elseif(!$this->table) {
					$cells['new'] = $this->spaceIcon;
				}

					// "Up/Down" links
				if ($permsEdit && $GLOBALS['TCA'][$table]['ctrl']['sortby']  && !$this->sortField && !$this->searchLevels) {
					if (isset($this->currentTable['prev'][$row['uid']]))	{	// Up
						$params='&cmd['.$table.']['.$row['uid'].'][move]='.$this->currentTable['prev'][$row['uid']];
						$cells['moveUp'] = '<a href="#" onclick="' . htmlspecialchars(
									'return jumpToUrl(\'' . $SOBE->doc->issueCommand($params, -1) . '\');'
								) .'" title="'.$GLOBALS['LANG']->getLL('moveUp', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-move-up') .
								'</a>';
					} else {
						$cells['moveUp'] = $this->spaceIcon;
					}
					if ($this->currentTable['next'][$row['uid']])	{	// Down
						$params='&cmd['.$table.']['.$row['uid'].'][move]='.$this->currentTable['next'][$row['uid']];
						$cells['moveDown']='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$SOBE->doc->issueCommand($params,-1).'\');').'" title="'.$GLOBALS['LANG']->getLL('moveDown', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-move-down') .
								'</a>';
					} else {
						$cells['moveDown'] = $this->spaceIcon;
					}
				} elseif(!$this->table) {
					$cells['moveUp']  = $this->spaceIcon;
					$cells['moveDown'] = $this->spaceIcon;
				}

					// "Hide/Unhide" links:
				$hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
				if ($permsEdit && $hiddenField && $GLOBALS['TCA'][$table]['columns'][$hiddenField] && (!$GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $table . ':' . $hiddenField))) {
					if ($row[$hiddenField])	{
						$params='&data['.$table.']['.$row['uid'].']['.$hiddenField.']=0';
						$cells['hide']='<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $SOBE->doc->issueCommand($params, -1) . '\');') . '" title="'.$GLOBALS['LANG']->getLL('unHide' . ($table == 'pages' ? 'Page' : ''), TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-edit-unhide') .
								'</a>';
					} else {
						$params='&data['.$table.']['.$row['uid'].']['.$hiddenField.']=1';
						$cells['hide']='<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $SOBE->doc->issueCommand($params, -1) . '\');') . '" title="' . $GLOBALS['LANG']->getLL('hide' . ($table == 'pages' ? 'Page' : ''), TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-edit-hide') .
								'</a>';
					}
				} elseif(!$this->table) {
					$cells['hide'] = $this->spaceIcon;
				}

					// "Delete" link:
				if (($table=='pages' && ($localCalcPerms&4)) || ($table!='pages' && ($this->calcPerms&16))) {
					$titleOrig = t3lib_BEfunc::getRecordTitle($table,$row,FALSE,TRUE);
					$title = t3lib_div::slashJS(t3lib_div::fixed_lgd_cs($titleOrig, $this->fixedL), 1);
					$params = '&cmd['.$table.']['.$row['uid'].'][delete]=1';

					$refCountMsg = t3lib_BEfunc::referenceCount(
						$table,
						$row['uid'],
						' ' . $GLOBALS['LANG']->sL(
							'LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'
						),
						$this->getReferenceCount($table, $row['uid'])
					) .
						t3lib_BEfunc::translationCount($table, $row['uid'], ' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.translationsOfRecord'));
					$cells['delete'] = '<a href="#" onclick="' . htmlspecialchars('if (confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('deleteWarning') . ' "' .  $title . '" ' . $refCountMsg) . ')) {jumpToUrl(\'' . $SOBE->doc->issueCommand($params, -1) . '\');} return false;') . '" title="' . $GLOBALS['LANG']->getLL('delete', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-edit-delete') .
							'</a>';
				} elseif(!$this->table) {
					$cells['delete'] = $this->spaceIcon;
				}

					// "Levels" links: Moving pages into new levels...
				if ($permsEdit && $table=='pages' && !$this->searchLevels)	{

						// Up (Paste as the page right after the current parent page)
					if ($this->calcPerms&8)	{
						$params='&cmd['.$table.']['.$row['uid'].'][move]='.-$this->id;
						$cells['moveLeft'] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $SOBE->doc->issueCommand($params, -1) . '\');') . '" title="' . $GLOBALS['LANG']->getLL('prevLevel', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-move-left') .
								'</a>';
					}
						// Down (Paste as subpage to the page right above)
					if ($this->currentTable['prevUid'][$row['uid']])	{
						$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$this->currentTable['prevUid'][$row['uid']]));
						if ($localCalcPerms&8)	{
							$params='&cmd['.$table.']['.$row['uid'].'][move]='.$this->currentTable['prevUid'][$row['uid']];
							$cells['moveRight'] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $SOBE->doc->issueCommand($params, -1) . '\');') . '" title="' . $GLOBALS['LANG']->getLL('nextLevel', TRUE) . '">' .
										t3lib_iconWorks::getSpriteIcon('actions-move-right') .
									'</a>';
						} else {
							$cells['moveRight'] = $this->spaceIcon;
						}
					} else {
						$cells['moveRight'] = $this->spaceIcon;
					}
				} elseif(!$this->table) {
					$cells['moveLeft'] = $this->spaceIcon;
					$cells['moveRight'] = $this->spaceIcon;
				}
			}
		}


		/**
		 * @hook			recStatInfoHooks: Allows to insert HTML before record icons on various places
		 * @date			2007-09-22
		 * @request		Kasper Skårhøj  <kasper2007@typo3.com>
		 */
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']))	{
			$stat='';
			$_params = array($table,$row['uid']);
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef)	{
				$stat.=t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
			$cells['stat'] = $stat;
		}
		/**
		 * @hook			makeControl: Allows to change control icons of records in list-module
		 * @date			2007-11-20
		 * @request		Bernhard Kraft  <krafbt@kraftb.at>
		 * @usage		This hook method gets passed the current $cells array as third parameter. This array contains values for the icons/actions generated for each record in Web>List. Each array entry is accessible by an index-key. The order of the icons is dependend on the order of those array entries.
		 */
		if(is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);
				if(!($hookObject instanceof localRecordList_actionsHook))	{
					throw new UnexpectedValueException('$hookObject must implement interface localRecordList_actionsHook', 1195567840);
				}
				$cells = $hookObject->makeControl($table, $row, $cells, $this);
			}
		}

			// Compile items into a DIV-element:
		return '
											<!-- CONTROL PANEL: '.$table.':'.$row['uid'].' -->
											<div class="typo3-DBctrl">'.implode('',$cells).'</div>';
	}

	/**
	 * Creates the clipboard panel for a single record in the listing.
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the clipboard panel.
	 * @return	string		HTML table with the clipboard panel (unless disabled)
	 */
	function makeClip($table,$row)	{
		global $TYPO3_CONF_VARS;

			// Return blank, if disabled:
		if ($this->dontShowClipControlPanels)	return '';
		$cells=array();

		$cells['pasteAfter'] = $cells['pasteInto'] = $this->spaceIcon;
			//enables to hide the copy, cut and paste icons for localized records - doesn't make much sense to perform these options for them
		$isL10nOverlay = $this->localizationView && $table != 'pages_language_overlay' && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
			// Return blank, if disabled:
			// Whether a numeric clipboard pad is active or the normal pad we will see different content of the panel:
		if ($this->clipObj->current=='normal')	{	// For the "Normal" pad:

				// Show copy/cut icons:
			$isSel = (string)$this->clipObj->isSelected($table,$row['uid']);
			$cells['copy'] = $isL10nOverlay ? $this->spaceIcon : '<a href="#" onclick="' . htmlspecialchars('return jumpSelf(\'' . $this->clipObj->selUrlDB($table, $row['uid'], 1, ($isSel=='copy'), array('returnUrl'=>'')) . '\');') . '" title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.copy', TRUE) . '">' .
						((!$isSel=='copy') ? t3lib_iconWorks::getSpriteIcon('actions-edit-copy') : t3lib_iconWorks::getSpriteIcon('actions-edit-copy-release')) .
					'</a>';
			$cells['cut'] = $isL10nOverlay ? $this->spaceIcon : '<a href="#" onclick="' . htmlspecialchars('return jumpSelf(\'' . $this->clipObj->selUrlDB($table, $row['uid'], 0, ($isSel == 'cut'), array('returnUrl'=>'')) . '\');') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.cut', TRUE) . '">' .
						((!$isSel=='cut') ? t3lib_iconWorks::getSpriteIcon('actions-edit-cut') : t3lib_iconWorks::getSpriteIcon('actions-edit-cut-release')) .
					'</a>';

		} else {	// For the numeric clipboard pads (showing checkboxes where one can select elements on/off)

				// Setting name of the element in ->CBnames array:
			$n=$table.'|'.$row['uid'];
			$this->CBnames[]=$n;

				// Check if the current element is selected and if so, prepare to set the checkbox as selected:
			$checked = ($this->clipObj->isSelected($table,$row['uid'])?' checked="checked"':'');

				// If the "duplicateField" value is set then select all elements which are duplicates...
			if ($this->duplicateField && isset($row[$this->duplicateField]))	{
				$checked='';
				if (in_array($row[$this->duplicateField], $this->duplicateStack))	{
					$checked=' checked="checked"';
				}
				$this->duplicateStack[] = $row[$this->duplicateField];
			}

				// Adding the checkbox to the panel:
			$cells['select'] = $isL10nOverlay ? $this->spaceIcon : '<input type="hidden" name="CBH['.$n.']" value="0" /><input type="checkbox" name="CBC['.$n.']" value="1" class="smallCheckboxes"'.$checked.' />';
		}

			// Now, looking for selected elements from the current table:
		$elFromTable = $this->clipObj->elFromTable($table);
		if (count($elFromTable) && $GLOBALS['TCA'][$table]['ctrl']['sortby']){
				// IF elements are found and they can be individually ordered, then add a "paste after" icon:
			$cells['pasteAfter'] = $isL10nOverlay ? $this->spaceIcon : '<a href="' . htmlspecialchars($this->clipObj->pasteUrl($table, -$row['uid'])) . '" onclick="' . htmlspecialchars('return '. $this->clipObj->confirmMsg($table, $row, 'after', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_pasteAfter', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-paste-after') .
					'</a>';
		}

			// Now, looking for elements in general:
		$elFromTable = $this->clipObj->elFromTable('');
		if ($table=='pages' && count($elFromTable))	{
			$cells['pasteInto'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('', $row['uid'])) . '" onclick="' . htmlspecialchars('return ' . $this->clipObj->confirmMsg($table, $row, 'into', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_pasteInto', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-paste-into') .
					'</a>';
		}

		/*
		 * @hook			makeClip: Allows to change clip-icons of records in list-module
		 * @date			2007-11-20
		 * @request		Bernhard Kraft  <krafbt@kraftb.at>
		 * @usage		This hook method gets passed the current $cells array as third parameter. This array contains values for the clipboard icons generated for each record in Web>List. Each array entry is accessible by an index-key. The order of the icons is dependend on the order of those array entries.
		 */
		if(is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);
				if(!($hookObject instanceof localRecordList_actionsHook))	{
					throw new UnexpectedValueException('$hookObject must implement interface localRecordList_actionsHook', 1195567845);
				}
				$cells = $hookObject->makeClip($table, $row, $cells, $this);
			}
		}

			// Compile items into a DIV-element:
		return '							<!-- CLIPBOARD PANEL: '.$table.':'.$row['uid'].' -->
											<div class="typo3-clipCtrl">'.implode('',$cells).'</div>';
	}

	/**
	 * Make reference count
	 *
	 * @param	string		Table name
	 * @param	integer		UID of record
	 * @return	string		HTML-table
	 *
	 * @deprecated since 4.4, will be removed in TYPO3 4.6 - Use createReferenceHtml() instead
	 */
	function makeRef($table,$uid)	{
		t3lib_div::logDeprecatedFunction();

			// Compile information for title tag:
		$infoData=array();
		if (is_array($this->references)) {
			foreach ($this->references as $row) {
				$infoData[]=$row['tablename'].':'.$row['recuid'].':'.$row['field'];
			}
		}

		return count($infoData) ? '<a href="#" onclick="'.htmlspecialchars('top.launchView(\''.$table.'\', \''.$uid.'\'); return false;').'" title="'.htmlspecialchars(t3lib_div::fixed_lgd_cs(implode(' / ',$infoData),100)).'">'.count($infoData).'</a>' : '';
	}

	/**
	 * Creates the HTML for a reference count for the record with the UID $uid
	 * in the table $tableName.
	 *
	 * @param string $tableName
	 *        table name of the referenced record, must not be empty
	 * @param integer $uid
	 *        UID of the referenced record, must be > 0
	 *
	 * @return string HTML of reference a link, will be empty if there are no
	 *                references to the corresponding record
	 */
	protected function createReferenceHtml($tableName, $uid) {
		$referenceCount = $this->getReferenceCount($tableName, $uid);
		if ($referenceCount == 0) {
			return '';
		}

		$queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tablename, recuid, field',
			'sys_refindex',
			'ref_table = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
				$tableName, 'sys_refindex'
			) .
				' AND ref_uid = ' . $uid .
				' AND deleted = 0',
			'',
			'',
			'0,20'
		);

		$referenceTitles = array();

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
			$referenceTitles[] = $row['tablename'] . ':' . $row['recuid'] .
				':' . $row['field'];
			if (strlen(implode(' / ', $referenceTitles)) >= 100) {
				break;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($queryResult);

		return '<a href="#" ' .
			'onclick="' . htmlspecialchars(
				'top.launchView(\'' . $tableName . '\', \'' . $uid .
				'\'); return false;'
			) . '" ' .
			'title="' . htmlspecialchars(
				t3lib_div::fixed_lgd_cs(implode(' / ', $referenceTitles), 100)
			) . '">' . $referenceCount . '</a>';
	}

	/**
	 * Creates the localization panel
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the localization panel.
	 * @return	array		Array with key 0/1 with content for column 1 and 2
	 */
	function makeLocalizationPanel($table,$row)	{
		$out = array(
			0 => '',
			1 => '',
		);

		$translations = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $this->selFieldList);
		$this->translations = $translations['translations'];

			// Language title and icon:
		$out[0] = $this->languageFlag($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);

		if (is_array($translations))	{

				// Traverse page translations and add icon for each language that does NOT yet exist:
			$lNew = '';
			foreach($this->pageOverlays as $lUid_OnPage => $lsysRec)	{
				if (!isset($translations['translations'][$lUid_OnPage]) && $GLOBALS['BE_USER']->checkLanguageAccess($lUid_OnPage))	{
					$url = substr($this->listURL(), strlen($this->backPath));
					$href = $GLOBALS['SOBE']->doc->issueCommand(
						'&cmd[' . $table . '][' . $row['uid'] . '][localize]=' . $lUid_OnPage,
						$url . '&justLocalized=' . rawurlencode($table . ':' . $row['uid'] . ':' . $lUid_OnPage)
					);
					$language = t3lib_BEfunc::getRecord('sys_language', $lUid_OnPage, 'title');
					if ($this->languageIconTitles[$lUid_OnPage]['flagIcon']) {
						$lC = t3lib_iconWorks::getSpriteIcon($this->languageIconTitles[$lUid_OnPage]['flagIcon']);
					} else {
						$lC = $this->languageIconTitles[$lUid_OnPage]['title'];
					}
					$lC = '<a href="' . htmlspecialchars($href) . '" title="' . htmlspecialchars($language['title']) . '">' . $lC . '</a> ';

					$lNew.=$lC;
				}
			}

			if ($lNew)	$out[1].= $lNew;
		} elseif ($row['l18n_parent']) {
			$out[0] = '&nbsp;&nbsp;&nbsp;&nbsp;'.$out[0];
		}


		return $out;
	}

	/**
	 * Create the selector box for selecting fields to display from a table:
	 *
	 * @param	string		Table name
	 * @param	boolean		If TRUE, form-fields will be wrapped around the table.
	 * @return	string		HTML table with the selector box (name: displayFields['.$table.'][])
	 */
	function fieldSelectBox($table,$formFields=1)	{

			// Init:
		t3lib_div::loadTCA($table);
		$formElements=array('','');
		if ($formFields)	{
			$formElements=array('<form action="'.htmlspecialchars($this->listURL()).'" method="post">','</form>');
		}

			// Load already selected fields, if any:
		$setFields=is_array($this->setFields[$table]) ? $this->setFields[$table] : array();

			// Request fields from table:
		$fields = $this->makeFieldList($table, FALSE, TRUE);

			// Add pseudo "control" fields
		$fields[]='_PATH_';
		$fields[]='_REF_';
		$fields[]='_LOCALIZATION_';
		$fields[]='_CONTROL_';
		$fields[]='_CLIPBOARD_';

			// Create an option for each field:
		$opt=array();
		$opt[] = '<option value=""></option>';
		foreach($fields as $fN)	{
				// Field label
			$fL = (is_array($GLOBALS['TCA'][$table]['columns'][$fN])
				? rtrim($GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['columns'][$fN]['label']), ':')
				: '[' . $fN . ']');
			$opt[] = '
											<option value="'.$fN.'"'.(in_array($fN,$setFields)?' selected="selected"':'').'>'.htmlspecialchars($fL).'</option>';
		}

			// Compile the options into a multiple selector box:
		$lMenu = '
										<select size="'.t3lib_div::intInRange(count($fields)+1,3,20).'" multiple="multiple" name="displayFields['.$table.'][]">'.implode('',$opt).'
										</select>
				';

			// Table with the field selector::
		$content.= '
			'.$formElements[0].'

				<!--
					Field selector for extended table view:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-dblist-fieldSelect">
					<tr>
						<td>'.$lMenu.'</td>
						<td><input type="submit" name="search" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.setFields', 1) . '" /></td>
					</tr>
				</table>
			'.$formElements[1];
		return $content;
	}











	/*********************************
	 *
	 * Helper functions
	 *
	 *********************************/

	/**
	 * Creates a link around $string. The link contains an onclick action which submits the script with some clipboard action.
	 * Currently, this is used for setting elements / delete elements.
	 *
	 * @param	string		The HTML content to link (image/text)
	 * @param	string		Table name
	 * @param	string		Clipboard command (eg. "setCB" or "delete")
	 * @param	string		Warning text, if any ("delete" uses this for confirmation)
	 * @return	string		<a> tag wrapped link.
	 */
	function linkClipboardHeaderIcon($string,$table,$cmd,$warning='')	{
		$onClickEvent = 'document.dblistForm.cmd.value=\''.$cmd.'\';document.dblistForm.cmd_table.value=\''.$table.'\';document.dblistForm.submit();';
		if ($warning)	$onClickEvent = 'if (confirm('.$GLOBALS['LANG']->JScharCode($warning).')){'.$onClickEvent.'}';
		return '<a href="#" onclick="'.htmlspecialchars($onClickEvent.'return false;').'">'.$string.'</a>';
	}

	/**
	 * Returns TRUE if a numeric clipboard pad is selected/active
	 *
	 * @return	boolean
	 */
	function clipNumPane()	{
		return in_Array('_CLIPBOARD_',$this->fieldArray) && $this->clipObj->current!='normal';
	}

	/**
	 * Creates a sort-by link on the input string ($code).
	 * It will automatically detect if sorting should be ascending or descending depending on $this->sortRev.
	 * Also some fields will not be possible to sort (including if single-table-view is disabled).
	 *
	 * @param	string		The string to link (text)
	 * @param	string		The fieldname represented by the title ($code)
	 * @param	string		Table name
	 * @return	string		Linked $code variable
	 */
	function addSortLink($code,$field,$table)	{

			// Certain circumstances just return string right away (no links):
		if ($field=='_CONTROL_' || $field=='_LOCALIZATION_' || $field=='_CLIPBOARD_' || $field=='_REF_' || $this->disableSingleTableView)	return $code;

			// If "_PATH_" (showing record path) is selected, force sorting by pid field (will at least group the records!)
		if ($field=='_PATH_')	$field=pid;

			//	 Create the sort link:
		$sortUrl = $this->listURL('', -1, 'sortField,sortRev,table,firstElementNumber') . '&table=' . $table . '&sortField=' . $field . '&sortRev=' . ($this->sortRev || ($this->sortField != $field) ? 0 : 1);
		$sortArrow = $this->sortField == $field ?
				t3lib_iconWorks::getSpriteIcon('status-status-sort-' . ($this->sortRev ? 'desc' : 'asc')) :
				'';
			// Return linked field:
		return '<a href="'.htmlspecialchars($sortUrl).'">'.$code.
				$sortArrow.
				'</a>';
	}

	/**
	 * Returns the path for a certain pid
	 * The result is cached internally for the session, thus you can call this function as much as you like without performance problems.
	 *
	 * @param	integer		The page id for which to get the path
	 * @return	string		The path.
	 */
	function recPath($pid)	{
		if (!isset($this->recPath_cache[$pid]))	{
			$this->recPath_cache[$pid] = t3lib_BEfunc::getRecordPath($pid,$this->perms_clause,20);
		}
		return $this->recPath_cache[$pid];
	}

	/**
	 * Returns TRUE if a link for creating new records should be displayed for $table
	 *
	 * @param	string		Table name
	 * @return	boolean		Returns TRUE if a link for creating new records should be displayed for $table
	 * @see		SC_db_new::showNewRecLink
	 */
	function showNewRecLink($table)	{
			// No deny/allow tables are set:
		if (!count($this->allowedNewTables) && !count($this->deniedNewTables)) {
			return TRUE;
			// If table is not denied (which takes precedence over allowed tables):
		} elseif (!in_array($table, $this->deniedNewTables) && (!count($this->allowedNewTables) || in_array($table, $this->allowedNewTables))) {
			return TRUE;
			// If table is denied or allowed tables are set, but table is not part of:
		} else {
			return FALSE;
		}
	}

	/**
	 * Creates the "&returnUrl" parameter for links - this is used when the script links to other scripts and passes its own URL with the link so other scripts can return to the listing again.
	 * Uses REQUEST_URI as value.
	 *
	 * @return	string
	 */
	function makeReturnUrl()	{
		return '&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
	}











	/************************************
	 *
	 * CSV related functions
	 *
	 ************************************/

	/**
	 * Initializes internal csvLines array with the header of field names
	 *
	 * @return	void
	 */
	protected function initCSV() {
		$this->addHeaderRowToCSV();
	}

	/**
	 * Add header line with field names as CSV line
	 *
	 * @return void
	 */
	protected function addHeaderRowToCSV() {
			// Add header row, control fields will be reduced inside addToCSV()
		$this->addToCSV(array_combine($this->fieldArray, $this->fieldArray));
	}

	/**
	 * Adds selected columns of one table row as CSV line.
	 *
	 * @param	array		Record array, from which the values of fields found in $this->fieldArray will be listed in the CSV output.
	 * @param	string		Table name @deprecated since 4.4
	 * @return	void
	 */
	protected function addToCSV(array $row = array(), $table = '') {
		$rowReducedByControlFields = self::removeControlFieldsFromFieldRow($row);
		$rowReducedToSelectedColumns = array_intersect_key($rowReducedByControlFields, array_flip($this->fieldArray));
		$this->setCsvRow($rowReducedToSelectedColumns);
	}

	/**
	 * Remove control fields from row for CSV export
	 *
	 * @param array fieldNames => fieldValues
	 * @return array Input array reduces by control fields
	 */
	protected static function removeControlFieldsFromFieldRow(array $row = array()) {
			// Possible control fields in a list row
		$controlFields = array(
			'_PATH_',
			'_REF_',
			'_CONTROL_',
			'_AFTERCONTROL_',
			'_AFTERREF_',
			'_CLIPBOARD_',
			'_LOCALIZATION_',
			'_LOCALIZATION_b',
		);
		return array_diff_key($row, array_flip($controlFields));
	}


	/**
	 * Adds input row of values to the internal csvLines array as a CSV formatted line
	 *
	 * @param	array		Array with values to be listed.
	 * @return	void
	 */
	function setCsvRow($csvRow)	{
		$this->csvLines[] = t3lib_div::csvValues($csvRow);
	}

	/**
	 * Compiles the internal csvLines array to a csv-string and outputs it to the browser.
	 * This function exits!
	 *
	 * @param	string		Filename prefix:
	 * @return	void		EXITS php execusion!
	 */
	function outputCSV($prefix)	{

			// Setting filename:
		$filename=$prefix.'_'.date('dmy-Hi').'.csv';

			// Creating output header:
		$mimeType = 'application/octet-stream';
		Header('Content-Type: '.$mimeType);
		Header('Content-Disposition: attachment; filename='.$filename);

			// Printing the content of the CSV lines:
		echo implode(chr(13).chr(10),$this->csvLines);

			// Exits:
		exit;
	}
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc']);
}

?>