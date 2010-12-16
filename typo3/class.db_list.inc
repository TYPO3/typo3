<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Include file extending t3lib_recordList
 * Shared between Web>List (db_list.php) and Web>Page (sysext/cms/layout/db_layout.php)
 *
 * $Id$
 * Revised for TYPO3 3.6 December/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   86: class recordList extends t3lib_recordList
 *  148:     function start($id,$table,$pointer,$search="",$levels="",$showLimit=0)
 *  211:     function generateList()
 *  275:     function getSearchBox($formFields=1)
 *  319:     function showSysNotesForPage()
 *
 *              SECTION: Various helper functions
 *  396:     function setDispFields()
 *  421:     function thumbCode($row,$table,$field)
 *  434:     function makeQueryArray($table, $id, $addWhere="",$fieldList='*')
 *  481:     function setTotalItems($queryParts)
 *  497:     function makeSearchString($table)
 *  536:     function linkWrapTable($table,$code)
 *  553:     function linkWrapItems($table,$uid,$code,$row)
 *  617:     function linkUrlMail($code,$testString)
 *  644:     function listURL($altId='',$table=-1,$exclList='')
 *  663:     function requestUri()
 *  674:     function makeFieldList($table,$dontCheckUser=0)
 *  721:     function getTreeObject($id,$depth,$perms_clause)
 *  739:     function localizationRedirect($justLocalized)
 *
 * TOTAL FUNCTIONS: 17
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */











/**
 * Child class for rendering of Web > List (not the final class. see class.db_list_extra)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @see localRecordList
 */
class recordList extends t3lib_recordList {

		// External, static:
	var $tableList='';				// Specify a list of tables which are the only ones allowed to be displayed.
	var $returnUrl='';				// Return URL
	var $thumbs = 0;				// Boolean. Thumbnails on records containing files (pictures)
	var $itemsLimitPerTable = 20;			// default Max items shown per table in "multi-table mode", may be overridden by tables.php
	var $itemsLimitSingleTable = 100;		// default Max items shown per table in "single-table mode", may be overridden by tables.php
	var $widthGif = '<img src="clear.gif" width="1" height="4" hspace="160" alt="" />';
	var $script = 'db_list.php';			// Current script name
	var $allFields=0;				// Indicates if all available fields for a user should be selected or not.
	var $localizationView=FALSE;			// Whether to show localization view or not.

		// Internal, static: GPvar:
	var $csvOutput=FALSE;				// If set, csvList is outputted.
	var $sortField;					// Field, to sort list by
	var $sortRev;					// Field, indicating to sort in reverse order.
	var $displayFields;				// Array, containing which fields to display in extended mode
	var $duplicateField;				// String, can contain the field name from a table which must have duplicate values marked.

		// Internal, static:
	var $id;					// Page id
	var $table='';					// Tablename if single-table mode
	var $listOnlyInSingleTableMode=FALSE;		// If true, records are listed only if a specific table is selected.
	var $firstElementNumber=0;			// Pointer for browsing list
	var $searchString='';				// Search string
	var $searchLevels='';				// Levels to search down.
	var $showLimit=0;				// Number of records to show
	var $pidSelect='';				// List of ids from which to select/search etc. (when search-levels are set high). See start()
	var $perms_clause='';				// Page select permissions
	var $calcPerms=0;				// Some permissions...
	var $clickTitleMode = '';			// Mode for what happens when a user clicks the title of a record.
	var $modSharedTSconfig = array();		// Shared module configuration, used by localization features
	var $pageRecord = array();		// Loaded with page record with version overlay if any.
	var $hideTables = '';			// Tables which should not get listed
	var $tableTSconfigOverTCA = array(); //TSconfig which overwrites TCA-Settings
	var $tablesCollapsed = array(); // Array of collapsed / uncollapsed tables in multi table view

		// Internal, dynamic:
	var $JScode = '';				// JavaScript code accumulation
	var $HTMLcode = '';				// HTML output
	var $iLimit=0;					// "LIMIT " in SQL...
	var $eCounter=0;				// Counting the elements no matter what...
	var $totalItems='';				// Set to the total number of items for a table when selecting.
	var $recPath_cache=array();			// Cache for record path
	var $setFields=array();				// Fields to display for the current table
	var $currentTable = array();			// Used for tracking next/prev uids
	var $duplicateStack=array();			// Used for tracking duplicate values of fields

	var $modTSconfig;				// module configuratio



	/**
	 * Initializes the list generation
	 *
	 * @param	integer		Page id for which the list is rendered. Must be >= 0
	 * @param	string		Tablename - if extended mode where only one table is listed at a time.
	 * @param	integer		Browsing pointer.
	 * @param	string		Search word, if any
	 * @param	integer		Number of levels to search down the page tree
	 * @param	integer		Limit of records to be listed.
	 * @return	void
	 */
	function start($id,$table,$pointer,$search="",$levels="",$showLimit=0)	{
		global $TCA;

			// Setting internal variables:
		$this->id=intval($id);					// sets the parent id
		if ($TCA[$table])	$this->table=$table;		// Setting single table mode, if table exists:
		$this->firstElementNumber=$pointer;
		$this->searchString=trim($search);
		$this->searchLevels=trim($levels);
		$this->showLimit=t3lib_div::intInRange($showLimit,0,10000);

			// Setting GPvars:
		$this->csvOutput = t3lib_div::_GP('csv') ? TRUE : FALSE;
		$this->sortField = t3lib_div::_GP('sortField');
		$this->sortRev = t3lib_div::_GP('sortRev');
		$this->displayFields = t3lib_div::_GP('displayFields');
		$this->duplicateField = t3lib_div::_GP('duplicateField');

		if (t3lib_div::_GP('justLocalized'))	{
			$this->localizationRedirect(t3lib_div::_GP('justLocalized'));
		}

			// If thumbnails are disabled, set the "notfound" icon as default:
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'])	{
			$this->thumbScript='gfx/notfound_thumb.gif';
		}

			// Init dynamic vars:
		$this->counter=0;
		$this->JScode='';
		$this->HTMLcode='';

			// limits
		if(isset($this->modTSconfig['properties']['itemsLimitPerTable'])) {
			$this->itemsLimitPerTable = t3lib_div::intInRange(intval($this->modTSconfig['properties']['itemsLimitPerTable']), 1, 10000);
		}
		if(isset($this->modTSconfig['properties']['itemsLimitSingleTable'])) {
			$this->itemsLimitSingleTable = t3lib_div::intInRange(intval($this->modTSconfig['properties']['itemsLimitSingleTable']), 1, 10000);
		}

			// Set select levels:
		$sL=intval($this->searchLevels);
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);

			// this will hide records from display - it has nothing todo with user rights!!
		if ($pidList = $GLOBALS['BE_USER']->getTSConfigVal('options.hideRecords.pages')) {
			if ($pidList = $GLOBALS['TYPO3_DB']->cleanIntList($pidList)) {
				$this->perms_clause .= ' AND pages.uid NOT IN ('.$pidList.')';
			}
		}

		// Get configuration of collapsed tables from user uc and merge with sanitized GP vars
		$this->tablesCollapsed = is_array($GLOBALS['BE_USER']->uc['moduleData']['db_list.php']) ? $GLOBALS['BE_USER']->uc['moduleData']['db_list.php'] : array();
		$collapseOverride = t3lib_div::_GP('collapse');
		if (is_array($collapseOverride)) {
			foreach($collapseOverride as $collapseTable => $collapseValue) {
				if (is_array($GLOBALS['TCA'][$collapseTable]) && ($collapseValue == 0 || $collapseValue == 1)) {
					$this->tablesCollapsed[$collapseTable] = $collapseValue;
				}
			}
			// Save modified user uc
			$GLOBALS['BE_USER']->uc['moduleData']['db_list.php'] = $this->tablesCollapsed;
			$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
			if (t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'))) {
				$location = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));
				t3lib_utility_Http::redirect($location);
			}
		}

		if ($sL>0)	{
			$tree = $this->getTreeObject($this->id, $sL, $this->perms_clause);
			$pidList = implode(',', $GLOBALS['TYPO3_DB']->cleanIntArray($tree->ids));
			$this->pidSelect = 'pid IN (' . $pidList . ')';
		} else {
			$this->pidSelect = 'pid='.intval($id);
		}

			// Initialize languages:
		if ($this->localizationView)	{
			$this->initializeLanguages();
		}
	}

	/**
	 * Traverses the table(s) to be listed and renders the output code for each:
	 * The HTML is accumulated in $this->HTMLcode
	 * Finishes off with a stopper-gif
	 *
	 * @return	void
	 */
	function generateList()	{
		global $TCA;

			// Set page record in header
		$this->pageRecord = t3lib_BEfunc::getRecordWSOL('pages',$this->id);

			// Traverse the TCA table array:
		reset($TCA);
		while (list($tableName)=each($TCA))	{

				// Checking if the table should be rendered:
			if ((!$this->table || $tableName==$this->table) && (!$this->tableList || t3lib_div::inList($this->tableList,$tableName)) && $GLOBALS['BE_USER']->check('tables_select',$tableName))	{		// Checks that we see only permitted/requested tables:

					// Load full table definitions:
				t3lib_div::loadTCA($tableName);

					// Don't show table if hidden by TCA ctrl section
				$hideTable = $GLOBALS['TCA'][$tableName]['ctrl']['hideTable'] ? TRUE : FALSE;
					// Don't show table if hidden by pageTSconfig mod.web_list.hideTables
				if (in_array($tableName, t3lib_div::trimExplode(',', $this->hideTables))) {
					$hideTable = TRUE;
				}
					// Override previous selection if table is enabled or hidden by TSconfig TCA override mod.web_list.table
				if (isset($this->tableTSconfigOverTCA[$tableName.'.']['hideTable'])) {
					$hideTable = $this->tableTSconfigOverTCA[$tableName.'.']['hideTable'] ? TRUE : FALSE;
				}
				if ($hideTable) {
					continue;
				}

					// iLimit is set depending on whether we're in single- or multi-table mode
				if ($this->table)	{
					$this->iLimit=(isset($TCA[$tableName]['interface']['maxSingleDBListItems'])?intval($TCA[$tableName]['interface']['maxSingleDBListItems']):$this->itemsLimitSingleTable);
				} else {
					$this->iLimit=(isset($TCA[$tableName]['interface']['maxDBListItems'])?intval($TCA[$tableName]['interface']['maxDBListItems']):$this->itemsLimitPerTable);
				}
				if ($this->showLimit)	$this->iLimit = $this->showLimit;

					// Setting fields to select:
				if ($this->allFields)	{
					$fields = $this->makeFieldList($tableName);
					$fields[]='tstamp';
					$fields[]='crdate';
					$fields[]='_PATH_';
					$fields[]='_CONTROL_';
					if (is_array($this->setFields[$tableName]))	{
						$fields = array_intersect($fields,$this->setFields[$tableName]);
					} else {
						$fields = array();
					}
				} else {
					$fields = array();
				}

					// Find ID to use (might be different for "versioning_followPages" tables)
				if (intval($this->searchLevels)==0)	{
					if ($TCA[$tableName]['ctrl']['versioning_followPages'] && $this->pageRecord['_ORIG_pid']==-1 && $this->pageRecord['t3ver_swapmode']==0)	{
						$this->pidSelect = 'pid='.intval($this->pageRecord['_ORIG_uid']);
					} else {
						$this->pidSelect = 'pid='.intval($this->id);
					}
				}
#debug($this->pidSelect,$tableName);
					// Finally, render the list:
				$this->HTMLcode.=$this->getTable($tableName, $this->id, implode(',',$fields));
			}
		}
	}

	/**
	 * Creates the search box
	 *
	 * @param	boolean		If true, the search box is wrapped in its own form-tags
	 * @return	string		HTML for the search box
	 */
	function getSearchBox($formFields=1)	{

			// Setting form-elements, if applicable:
		$formElements=array('','');
		if ($formFields)	{
			$formElements=array('<form action="'.htmlspecialchars($this->listURL()).'" method="post">','</form>');
		}

			// Make level selector:
		$opt=array();
		$parts = explode('|',$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.enterSearchLevels'));
		while(list($kv,$label)=each($parts))	{
			$opt[] = '<option value="'.$kv.'"'.($kv==intval($this->searchLevels)?' selected="selected"':'').'>'.htmlspecialchars($label).'</option>';
		}
		$lMenu = '<select name="search_levels">'.implode('',$opt).'</select>';

			// Table with the search box:
		$content.= '
			'.$formElements[0].'

				<!--
					Search box:
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="bgColor4" id="typo3-dblist-search">
					<tr>
						<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.enterSearchString',1).'<input type="text" name="search_field" value="'.htmlspecialchars($this->searchString).'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(10).' /></td>
						<td>'.$lMenu.'</td>
						<td><input type="submit" name="search" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.search',1).'" /></td>
					</tr>
					<tr>
						<td colspan="3">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showRecords',1).':<input type="text" name="showLimit" value="'.htmlspecialchars($this->showLimit?$this->showLimit:'').'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td>
					</tr>
				</table>
			'.$formElements[1];
		$content.=t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'list_searchbox', $GLOBALS['BACK_PATH'],'|<br/>');
		return $content;
	}

	/**
	 * Creates the display of sys_notes for the page.
	 * Relies on the "sys_note" extension to be loaded.
	 *
	 * @return	string		HTML for the sys-notes (if any)
	 */
	function showSysNotesForPage()	{
		global $TCA;

		$out='';

			// Checking if extension is loaded:
		if (!t3lib_extMgm::isLoaded('sys_note'))	return '';

			// Create query for selecting the notes:
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','sys_note','pid IN ('.$this->id.') AND (personal=0 OR cruser='.intval($GLOBALS['BE_USER']->user['uid']).')'.t3lib_BEfunc::deleteClause('sys_note').t3lib_BEfunc::versioningPlaceholderClause('sys_note'));

			// Executing query:
		$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);

			// If some notes were found, render them:
		if ($dbCount)	{
			$cat = array();

				// Load full table description:
			t3lib_div::loadTCA('sys_note');

				// Traverse note-types and get labels:
			if ($TCA['sys_note'] && $TCA['sys_note']['columns']['category'] && is_array($TCA['sys_note']['columns']['category']['config']['items']))	{
				foreach($TCA['sys_note']['columns']['category']['config']['items'] as $el)	{
					$cat[$el[1]]=$GLOBALS['LANG']->sL($el[0]);
				}
			}

				// For each note found, make rendering:
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{

					// Create content:
				$iconImg = t3lib_iconWorks::getIconImage('sys_note',$row,$this->backPath,'');
				$subject = htmlspecialchars($row['subject']);
				$fields = array();
				$fields['Author:'] = htmlspecialchars($row['author'].($row['email'] && $row['author'] ? ', ':'').$row['email']);
				$fields['Category:'] = htmlspecialchars($cat[$row['category']]);
				$fields['Note:'] = nl2br(htmlspecialchars($row['message']));

					// Compile content:
				$out.='


				<!--
					Sys-notes for the db_list.php:
				-->
					<table border="0" cellpadding="1" cellspacing="1" id="typo3-dblist-sysnotes">
						<tr><td colspan="2" class="bgColor2">'.$iconImg.'<strong>'.$subject.'</strong></td></tr>
						<tr><td class="bgColor4">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.category',1).'</td><td class="bgColor4">'.$fields['Category:'].'</td></tr>
						<tr><td class="bgColor4">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.author',1).'</td><td class="bgColor4">'.$fields['Author:'].'</td></tr>
						<tr><td class="bgColor4">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.note',1).'</td><td class="bgColor4">'.$fields['Note:'].'</td></tr>
					</table>
				';
			}
		}
		return $out;
	}








	/******************************
	 *
	 * Various helper functions
	 *
	 ******************************/

	/**
	 * Setting the field names to display in extended list.
	 * Sets the internal variable $this->setFields
	 *
	 * @return	void
	 */
	function setDispFields()	{

			// Getting from session:
		$dispFields = $GLOBALS['BE_USER']->getModuleData('db_list.php/displayFields');

			// If fields has been inputted, then set those as the value and push it to session variable:
		if (is_array($this->displayFields))	{
			reset($this->displayFields);
			$tKey = key($this->displayFields);
			$dispFields[$tKey]=$this->displayFields[$tKey];
			$GLOBALS['BE_USER']->pushModuleData('db_list.php/displayFields',$dispFields);
		}

			// Setting result:
		$this->setFields=$dispFields;
	}

	/**
	 * Create thumbnail code for record/field
	 *
	 * @param	array		Record array
	 * @param	string		Table (record is from)
	 * @param	string		Field name for which thumbsnail are to be rendered.
	 * @return	string		HTML for thumbnails, if any.
	 */
	function thumbCode($row,$table,$field)	{
		return t3lib_BEfunc::thumbCode($row,$table,$field,$this->backPath,$this->thumbScript);
	}

	/**
	 * Returns the SQL-query array to select the records from a table $table with pid = $id
	 *
	 * @param	string		Table name
	 * @param	integer		Page id (NOT USED! $this->pidSelect is used instead)
	 * @param	string		Additional part for where clause
	 * @param	string		Field list to select, * for all (for "SELECT [fieldlist] FROM ...")
	 * @return	array		Returns query array
	 */
	function makeQueryArray($table, $id, $addWhere='', $fieldList='*')	{
		global $TCA, $TYPO3_CONF_VARS;

		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'] as $classRef) {
				$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
			}
		}

			// Set ORDER BY:
		$orderBy = ($TCA[$table]['ctrl']['sortby']) ? 'ORDER BY '.$TCA[$table]['ctrl']['sortby'] : $TCA[$table]['ctrl']['default_sortby'];
		if ($this->sortField)	{
			if (in_array($this->sortField,$this->makeFieldList($table,1)))	{
				$orderBy = 'ORDER BY '.$this->sortField;
				if ($this->sortRev)	$orderBy.=' DESC';
			}
		}

			// Set LIMIT:
		$limit = $this->iLimit ? ($this->firstElementNumber ? $this->firstElementNumber.',' : '').($this->iLimit+1) : '';

			// Filtering on displayable pages (permissions):
		$pC = ($table=='pages' && $this->perms_clause)?' AND '.$this->perms_clause:'';

			// Adding search constraints:
		$search = $this->makeSearchString($table);

			// Compiling query array:
		$queryParts = array(
			'SELECT' => $fieldList,
			'FROM' => $table,
			'WHERE' => $this->pidSelect.
						' '.$pC.
						t3lib_BEfunc::deleteClause($table).
						t3lib_BEfunc::versioningPlaceholderClause($table).
						' '.$addWhere.
						' '.$search,
			'GROUPBY' => '',
			'ORDERBY' => $GLOBALS['TYPO3_DB']->stripOrderBy($orderBy),
			'LIMIT' => $limit
		);

			// Apply hook as requested in http://bugs.typo3.org/view.php?id=4361
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'makeQueryArray_post')) {
				$_params = array(
					'orderBy' => $orderBy,
					'limit' => $limit,
					'pC' => $pC,
					'search' => $search,
				);
				$hookObj->makeQueryArray_post($queryParts, $this, $table, $id, $addWhere, $fieldList, $_params);
			}
		}

			// Return query:
		return $queryParts;
	}

	/**
	 * Based on input query array (query for selecting count(*) from a table) it will select the number of records and set the value in $this->totalItems
	 *
	 * @param	array		Query array
	 * @return	void
	 * @see makeQueryArray()
	 */
	function setTotalItems($queryParts)	{
		$this->totalItems = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'*',
			$queryParts['FROM'],
			$queryParts['WHERE']
		);
	}

	/**
	 * Creates part of query for searching after a word ($this->searchString) fields in input table
	 *
	 * @param	string		Table, in which the fields are being searched.
	 * @return	string		Returns part of WHERE-clause for searching, if applicable.
	 */
	function makeSearchString($table)	{
		global $TCA;

			// Make query, only if table is valid and a search string is actually defined:
		if ($TCA[$table] && $this->searchString)	{

				// Loading full table description - we need to traverse fields:
			t3lib_div::loadTCA($table);

				// Initialize field array:
			$sfields=array();
			$sfields[]='uid';	// Adding "uid" by default.

				// Traverse the configured columns and add all columns that can be searched:
			foreach($TCA[$table]['columns'] as $fieldName => $info)	{
				if ($info['config']['type']=='text' || ($info['config']['type']=='input' && !preg_match('/date|time|int/',$info['config']['eval'])))	{
					$sfields[]=$fieldName;
				}
			}

				// If search-fields were defined (and there always are) we create the query:
			if (count($sfields))	{
				$like = ' LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($this->searchString, $table).'%\'';		// Free-text searching...
				$queryPart = ' AND ('.implode($like.' OR ',$sfields).$like.')';

					// Return query:
				return $queryPart;
			}
		}
	}

	/**
	 * Returns the title (based on $code) of a table ($table) with the proper link around. For headers over tables.
	 * The link will cause the display of all extended mode or not for the table.
	 *
	 * @param	string		Table name
	 * @param	string		Table label
	 * @return	string		The linked table label
	 */
	function linkWrapTable($table,$code)	{
		if ($this->table!=$table)	{
			return '<a href="'.htmlspecialchars($this->listURL('',$table)).'">'.$code.'</a>';
		} else {
			return '<a href="'.htmlspecialchars($this->listURL('','','sortField,sortRev,table')).'">'.$code.'</a>';
		}
	}

	/**
	 * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for 'pages'-records a link to the level of that record...)
	 *
	 * @param	string		Table name
	 * @param	integer		Item uid
	 * @param	string		Item title (not htmlspecialchars()'ed yet)
	 * @param	array		Item row
	 * @return	string		The item title. Ready for HTML output (is htmlspecialchars()'ed)
	 */
	function linkWrapItems($table,$uid,$code,$row)	{
		global $TCA, $LANG;

		$origCode = $code;

			// If the title is blank, make a "no title" label:
		if (!strcmp($code,'')) {
			$code = '<i>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</i> - '.htmlspecialchars(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table,$row),$GLOBALS['BE_USER']->uc['titleLen']));
		} else {
			$code = htmlspecialchars(t3lib_div::fixed_lgd_cs($code, $this->fixedL));
			if ($code != htmlspecialchars($origCode)) {
				$code = '<span title="'.htmlspecialchars($origCode).'">'.$code.'</span>';
			}
		}

		switch((string)$this->clickTitleMode)	{
			case 'edit':
					// If the listed table is 'pages' we have to request the permission settings for each page:
				if ($table=='pages')	{
					$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$row['uid']));
					$permsEdit = $localCalcPerms&2;
				} else {
					$permsEdit = $this->calcPerms&16;
				}

					// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
				if ($permsEdit)	{
					$params='&edit['.$table.']['.$row['uid'].']=edit';
					$code = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->backPath,-1)).'" title="'.$LANG->getLL('edit',1).'">'.
							$code.
							'</a>';
				}
			break;
			case 'show':
					// "Show" link (only pages and tt_content elements)
				if ($table=='pages' || $table=='tt_content')	{
					$code = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($table=='tt_content'?$this->id.'#'.$row['uid']:$row['uid'])).'" title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showPage',1).'">'.
							$code.
							'</a>';
				}
			break;
			case 'info':
				// "Info": (All records)
				$code = '<a href="#" onclick="'.htmlspecialchars('top.launchView(\''.$table.'\', \''.$row['uid'].'\'); return false;').'" title="'.$LANG->getLL('showInfo',1).'">'.
					$code.
					'</a>';
			break;
			default:
					// Output the label now:
				if ($table=='pages')	{
					$code = '<a href="'.htmlspecialchars($this->listURL($uid,'')).'" onclick="setHighlight('.$uid.')">'.$code.'</a>';
				} else {
					$code = $this->linkUrlMail($code,$origCode);
				}
			break;
		}

		return $code;
	}

	/**
	 * Wrapping input code in link to URL or email if $testString is either.
	 *
	 * @param	string		code to wrap
	 * @param	string		String which is tested for being a URL or email and which will be used for the link if so.
	 * @return	string		Link-Wrapped $code value, if $testString was URL or email.
	 */
	function linkUrlMail($code,$testString)	{

			// Check for URL:
		$schema = parse_url($testString);
		if ($schema['scheme'] && t3lib_div::inList('http,https,ftp',$schema['scheme']))	{
			return '<a href="'.htmlspecialchars($testString).'" target="_blank">'.$code.'</a>';
		}

			// Check for email:
		if (t3lib_div::validEmail($testString))	{
			return '<a href="mailto:'.htmlspecialchars($testString).'" target="_blank">'.$code.'</a>';
		}

			// Return if nothing else...
		return $code;
	}

	/**
	 * Creates the URL to this script, including all relevant GPvars
	 * Fixed GPvars are id, table, imagemode, returlUrl, search_field, search_levels and showLimit
	 * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
	 *
	 * @param	string		Alternative id value. Enter blank string for the current id ($this->id)
	 * @param	string		Tablename to display. Enter "-1" for the current table.
	 * @param	string		Commalist of fields NOT to include ("sortField" or "sortRev")
	 * @return	string		URL
	 */
	function listURL($altId='',$table=-1,$exclList='')	{
		return $GLOBALS['BACK_PATH'] . $this->script.
			'?id='.(strcmp($altId,'')?$altId:$this->id).
			'&table='.rawurlencode($table==-1?$this->table:$table).
			($this->thumbs?'&imagemode='.$this->thumbs:'').
			($this->returnUrl?'&returnUrl='.rawurlencode($this->returnUrl):'').
			($this->searchString?'&search_field='.rawurlencode($this->searchString):'').
			($this->searchLevels?'&search_levels='.rawurlencode($this->searchLevels):'').
			($this->showLimit?'&showLimit='.rawurlencode($this->showLimit):'').
			($this->firstElementNumber?'&pointer='.rawurlencode($this->firstElementNumber):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortField')) && $this->sortField?'&sortField='.rawurlencode($this->sortField):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortRev')) && $this->sortRev?'&sortRev='.rawurlencode($this->sortRev):'')
			;
	}

	/**
	 * Returns "requestUri" - which is basically listURL
	 *
	 * @return	string		Content of ->listURL()
	 */
	function requestUri()	{
		return $this->listURL();
	}

	/**
	 * Makes the list of fields to select for a table
	 *
	 * @param	string		Table name
	 * @param	boolean		If set, users access to the field (non-exclude-fields) is NOT checked.
	 * @param	boolean		If set, also adds crdate and tstamp fields (note: they will also be added if user is admin or dontCheckUser is set)
	 * @return	array		Array, where values are fieldnames to include in query
	 */
	function makeFieldList($table,$dontCheckUser=0,$addDateFields=0)	{
		global $TCA,$BE_USER;

			// Init fieldlist array:
		$fieldListArr = array();

			// Check table:
		if (is_array($TCA[$table]))	{
			t3lib_div::loadTCA($table);

				// Traverse configured columns and add them to field array, if available for user.
			foreach($TCA[$table]['columns'] as $fN => $fieldValue)	{
				if ($dontCheckUser ||
					((!$fieldValue['exclude'] || $BE_USER->check('non_exclude_fields',$table.':'.$fN)) && $fieldValue['config']['type']!='passthrough'))	{
					$fieldListArr[]=$fN;
				}
			}

				// Add special fields:
			if ($dontCheckUser || $BE_USER->isAdmin())	{
				$fieldListArr[]='uid';
				$fieldListArr[]='pid';
			}

				// Add date fields
			if ($dontCheckUser || $BE_USER->isAdmin() || $addDateFields)	{
				if ($TCA[$table]['ctrl']['tstamp'])	$fieldListArr[]=$TCA[$table]['ctrl']['tstamp'];
				if ($TCA[$table]['ctrl']['crdate'])	$fieldListArr[]=$TCA[$table]['ctrl']['crdate'];
			}

				// Add more special fields:
			if ($dontCheckUser || $BE_USER->isAdmin())	{
				if ($TCA[$table]['ctrl']['cruser_id'])	$fieldListArr[]=$TCA[$table]['ctrl']['cruser_id'];
				if ($TCA[$table]['ctrl']['sortby'])	$fieldListArr[]=$TCA[$table]['ctrl']['sortby'];
				if ($TCA[$table]['ctrl']['versioningWS'])	{
					$fieldListArr[]='t3ver_id';
					$fieldListArr[]='t3ver_state';
					$fieldListArr[]='t3ver_wsid';
					if ($table==='pages')	{
						$fieldListArr[]='t3ver_swapmode';
					}
				}
			}
		}
		return $fieldListArr;
	}

	/**
	 * Creates an instance of t3lib_pageTree which will select a page tree to $depth and return the object. In that object we will find the ids of the tree.
	 *
	 * @param	integer		Page id.
	 * @param	integer		Depth to go down.
	 * @param	string		Select clause
	 * @return	object		t3lib_pageTree instance with created list of ids.
	 */
	function getTreeObject($id,$depth,$perms_clause)	{
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$tree->init('AND '.$perms_clause);
		$tree->makeHTML=0;
		$tree->fieldArray = Array('uid','php_tree_stop');
		if ($depth)	{
			$tree->getTree($id, $depth, '');
		}
		$tree->ids[]=$id;
		return $tree;
	}

	/**
	 * Redirects to TCEforms (alt_doc) if a record is just localized.
	 *
	 * @param	string		string with table, orig uid and language separated by ":"
	 * @return	void
	 */
	function localizationRedirect($justLocalized)	{
		global $TCA;

		list($table,$orig_uid,$language) = explode(':',$justLocalized);

		if ($TCA[$table] && $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField'])	{
			list($localizedRecord) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid',
					$table,
					$TCA[$table]['ctrl']['languageField'].'='.intval($language).' AND '.
						$TCA[$table]['ctrl']['transOrigPointerField'].'='.intval($orig_uid).
						t3lib_BEfunc::deleteClause($table).
						t3lib_BEfunc::versioningPlaceholderClause($table)
				);

			if (is_array($localizedRecord))	{
					// Create parameters and finally run the classic page module for creating a new page translation
				$params = '&edit['.$table.']['.$localizedRecord['uid'].']=edit';
				$returnUrl = '&returnUrl='.rawurlencode($this->listURL());
				$location = $GLOBALS['BACK_PATH'].'alt_doc.php?'.$params.$returnUrl;

				t3lib_utility_Http::redirect($location);
			}
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.db_list.inc'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.db_list.inc']);
}

?>
