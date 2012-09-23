<?php
namespace TYPO3\CMS\Backend\Utility;

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
 * Standard functions available for the TYPO3 backend.
 * You are encouraged to use this class in your own applications (Backend Modules)
 * Don't instantiate - call functions with "\TYPO3\CMS\Backend\Utility\BackendUtility::" prefixed the function name.
 *
 * Call ALL methods without making an object!
 * Eg. to get a page-record 51 do this: '\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages',51)'
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class BackendUtility {

	/*******************************************
	 *
	 * SQL-related, selecting records, searching
	 *
	 *******************************************/
	/**
	 * Returns the WHERE clause " AND NOT [tablename].[deleted-field]" if a deleted-field is configured in $GLOBALS['TCA'] for the tablename, $table
	 * This function should ALWAYS be called in the backend for selection on tables which are configured in $GLOBALS['TCA'] since it will ensure consistent selection of records, even if they are marked deleted (in which case the system must always treat them as non-existent!)
	 * In the frontend a function, ->enableFields(), is known to filter hidden-field, start- and endtime and fe_groups as well. But that is a job of the frontend, not the backend. If you need filtering on those fields as well in the backend you can use ->BEenableFields() though.
	 *
	 * @param string $table Table name present in $GLOBALS['TCA']
	 * @param string $tableAlias Table alias if any
	 * @return string WHERE clause for filtering out deleted records, eg " AND tablename.deleted=0
	 */
	static public function deleteClause($table, $tableAlias = '') {
		if ($GLOBALS['TCA'][$table]['ctrl']['delete']) {
			return ' AND ' . ($tableAlias ? $tableAlias : $table) . '.' . $GLOBALS['TCA'][$table]['ctrl']['delete'] . '=0';
		} else {
			return '';
		}
	}

	/**
	 * Gets record with uid = $uid from $table
	 * You can set $field to a list of fields (default is '*')
	 * Additional WHERE clauses can be added by $where (fx. ' AND blabla = 1')
	 * Will automatically check if records has been deleted and if so, not return anything.
	 * $table must be found in $GLOBALS['TCA']
	 *
	 * @param string $table Table name present in $GLOBALS['TCA']
	 * @param integer $uid UID of record
	 * @param string $fields List of fields to select
	 * @param string $where Additional WHERE clause, eg. " AND blablabla = 0
	 * @param boolean $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
	 * @return array Returns the row if found, otherwise nothing
	 */
	static public function getRecord($table, $uid, $fields = '*', $where = '', $useDeleteClause = TRUE) {
		if ($GLOBALS['TCA'][$table]) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, 'uid=' . intval($uid) . ($useDeleteClause ? self::deleteClause($table) : '') . $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			if ($row) {
				return $row;
			}
		}
	}

	/**
	 * Like getRecord(), but overlays workspace version if any.
	 *
	 * @param string $table Table name present in $GLOBALS['TCA']
	 * @param integer $uid UID of record
	 * @param string $fields List of fields to select
	 * @param string $where Additional WHERE clause, eg. " AND blablabla = 0
	 * @param boolean $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
	 * @param boolean $unsetMovePointers If TRUE the function does not return a "pointer" row for moved records in a workspace
	 * @return array Returns the row if found, otherwise nothing
	 */
	static public function getRecordWSOL($table, $uid, $fields = '*', $where = '', $useDeleteClause = TRUE, $unsetMovePointers = FALSE) {
		if ($fields !== '*') {
			$internalFields = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($fields . ',uid,pid');
			$row = self::getRecord($table, $uid, $internalFields, $where, $useDeleteClause);
			self::workspaceOL($table, $row, -99, $unsetMovePointers);
			if (is_array($row)) {
				foreach (array_keys($row) as $key) {
					if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($fields, $key) && $key[0] !== '_') {
						unset($row[$key]);
					}
				}
			}
		} else {
			$row = self::getRecord($table, $uid, $fields, $where, $useDeleteClause);
			self::workspaceOL($table, $row, -99, $unsetMovePointers);
		}
		return $row;
	}

	/**
	 * Returns the first record found from $table with $where as WHERE clause
	 * This function does NOT check if a record has the deleted flag set.
	 * $table does NOT need to be configured in $GLOBALS['TCA']
	 * The query used is simply this:
	 * $query = 'SELECT ' . $fields . ' FROM ' . $table . ' WHERE ' . $where;
	 *
	 * @param string $table Table name (not necessarily in TCA)
	 * @param string $where WHERE clause
	 * @param string $fields $fields is a list of fields to select, default is '*'
	 * @return array First row found, if any, FALSE otherwise
	 */
	static public function getRecordRaw($table, $where = '', $fields = '*') {
		$row = FALSE;
		if (FALSE !== ($res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where, '', '', '1'))) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $row;
	}

	/**
	 * Returns records from table, $theTable, where a field ($theField) equals the value, $theValue
	 * The records are returned in an array
	 * If no records were selected, the function returns nothing
	 *
	 * @param string $theTable Table name present in $GLOBALS['TCA']
	 * @param string $theField Field to select on
	 * @param string $theValue Value that $theField must match
	 * @param string $whereClause Optional additional WHERE clauses put in the end of the query. DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param string $groupBy Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy Optional ORDER BY field(s), if none, supply blank string.
	 * @param string $limit Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @param boolean $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
	 * @return mixed Multidimensional array with selected records (if any is selected)
	 */
	static public function getRecordsByField($theTable, $theField, $theValue, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '', $useDeleteClause = TRUE) {
		if (is_array($GLOBALS['TCA'][$theTable])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$theTable,
				$theField . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($theValue, $theTable) .
					($useDeleteClause ? self::deleteClause($theTable) . ' ' : '') .
					self::versioningPlaceholderClause($theTable) . ' ' .
					$whereClause,
				$groupBy,
				$orderBy,
				$limit
			);
			$rows = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$rows[] = $row;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			if (count($rows)) {
				return $rows;
			}
		}
	}

	/**
	 * Makes an backwards explode on the $str and returns an array with ($table, $uid).
	 * Example: tt_content_45 => array('tt_content', 45)
	 *
	 * @param string $str [tablename]_[uid] string to explode
	 * @return array
	 */
	static public function splitTable_Uid($str) {
		list($uid, $table) = explode('_', strrev($str), 2);
		return array(strrev($table), strrev($uid));
	}

	/**
	 * Returns a list of pure integers based on $in_list being a list of records with table-names prepended.
	 * Ex: $in_list = "pages_4,tt_content_12,45" would result in a return value of "4,45" if $tablename is "pages" and $default_tablename is 'pages' as well.
	 *
	 * @param string $in_list Input list
	 * @param string $tablename Table name from which ids is returned
	 * @param string $default_tablename $default_tablename denotes what table the number '45' is from (if nothing is prepended on the value)
	 * @return string List of ids
	 */
	static public function getSQLselectableList($in_list, $tablename, $default_tablename) {
		$list = array();
		if ((string) trim($in_list) != '') {
			$tempItemArray = explode(',', trim($in_list));
			foreach ($tempItemArray as $key => $val) {
				$val = strrev($val);
				$parts = explode('_', $val, 2);
				if ((string) trim($parts[0]) != '') {
					$theID = intval(strrev($parts[0]));
					$theTable = trim($parts[1]) ? strrev(trim($parts[1])) : $default_tablename;
					if ($theTable == $tablename) {
						$list[] = $theID;
					}
				}
			}
		}
		return implode(',', $list);
	}

	/**
	 * Backend implementation of enableFields()
	 * Notice that "fe_groups" is not selected for - only disabled, starttime and endtime.
	 * Notice that deleted-fields are NOT filtered - you must ALSO call deleteClause in addition.
	 * $GLOBALS["SIM_ACCESS_TIME"] is used for date.
	 *
	 * @param string $table The table from which to return enableFields WHERE clause. Table name must have a 'ctrl' section in $GLOBALS['TCA'].
	 * @param boolean $inv Means that the query will select all records NOT VISIBLE records (inverted selection)
	 * @return string WHERE clause part
	 */
	static public function BEenableFields($table, $inv = 0) {
		$ctrl = $GLOBALS['TCA'][$table]['ctrl'];
		$query = array();
		$invQuery = array();
		if (is_array($ctrl)) {
			if (is_array($ctrl['enablecolumns'])) {
				if ($ctrl['enablecolumns']['disabled']) {
					$field = $table . '.' . $ctrl['enablecolumns']['disabled'];
					$query[] = $field . '=0';
					$invQuery[] = $field . '<>0';
				}
				if ($ctrl['enablecolumns']['starttime']) {
					$field = $table . '.' . $ctrl['enablecolumns']['starttime'];
					$query[] = '(' . $field . '<=' . $GLOBALS['SIM_ACCESS_TIME'] . ')';
					$invQuery[] = '(' . $field . '<>0 AND ' . $field . '>' . $GLOBALS['SIM_ACCESS_TIME'] . ')';
				}
				if ($ctrl['enablecolumns']['endtime']) {
					$field = $table . '.' . $ctrl['enablecolumns']['endtime'];
					$query[] = '(' . $field . '=0 OR ' . $field . '>' . $GLOBALS['SIM_ACCESS_TIME'] . ')';
					$invQuery[] = '(' . $field . '<>0 AND ' . $field . '<=' . $GLOBALS['SIM_ACCESS_TIME'] . ')';
				}
			}
		}
		$outQ = $inv ? '(' . implode(' OR ', $invQuery) . ')' : implode(' AND ', $query);
		return $outQ ? ' AND ' . $outQ : '';
	}

	/**
	 * Fetches the localization for a given record.
	 *
	 * @param string $table Table name present in $GLOBALS['TCA']
	 * @param integer $uid The uid of the record
	 * @param integer $language The uid of the language record in sys_language
	 * @param string $andWhereClause Optional additional WHERE clause (default: '')
	 * @return mixed Multidimensional array with selected records; if none exist, FALSE is returned
	 */
	static public function getRecordLocalization($table, $uid, $language, $andWhereClause = '') {
		$recordLocalization = FALSE;
		if (self::isTableLocalizable($table)) {
			$tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
			$recordLocalization = self::getRecordsByField($table, $tcaCtrl['transOrigPointerField'], $uid, 'AND ' . $tcaCtrl['languageField'] . '=' . intval($language) . ($andWhereClause ? ' ' . $andWhereClause : ''), '', '', '1');
		}
		return $recordLocalization;
	}

	/*******************************************
	 *
	 * Page tree, TCA related
	 *
	 *******************************************/
	/**
	 * Returns what is called the 'RootLine'. That is an array with information about the page records from a page id ($uid) and back to the root.
	 * By default deleted pages are filtered.
	 * This RootLine will follow the tree all the way to the root. This is opposite to another kind of root line known from the frontend where the rootline stops when a root-template is found.
	 *
	 * @param integer $uid Page id for which to create the root line.
	 * @param string $clause Clause can be used to select other criteria. It would typically be where-clauses that stops the process if we meet a page, the user has no reading access to.
	 * @param boolean $workspaceOL If TRUE, version overlay is applied. This must be requested specifically because it is usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
	 * @return array Root line array, all the way to the page tree root (or as far as $clause allows!)
	 */
	static public function BEgetRootLine($uid, $clause = '', $workspaceOL = FALSE) {
		static $BEgetRootLine_cache = array();
		$output = array();
		$pid = $uid;
		$ident = $pid . '-' . $clause . '-' . $workspaceOL;
		if (is_array($BEgetRootLine_cache[$ident])) {
			$output = $BEgetRootLine_cache[$ident];
		} else {
			$loopCheck = 100;
			$theRowArray = array();
			while ($uid != 0 && $loopCheck) {
				$loopCheck--;
				$row = self::getPageForRootline($uid, $clause, $workspaceOL);
				if (is_array($row)) {
					$uid = $row['pid'];
					$theRowArray[] = $row;
				} else {
					break;
				}
			}
			if ($uid == 0) {
				$theRowArray[] = array('uid' => 0, 'title' => '');
			}
			$c = count($theRowArray);
			foreach ($theRowArray as $val) {
				$c--;
				$output[$c] = array(
					'uid' => $val['uid'],
					'pid' => $val['pid'],
					'title' => $val['title'],
					'TSconfig' => $val['TSconfig'],
					'is_siteroot' => $val['is_siteroot'],
					'storage_pid' => $val['storage_pid'],
					't3ver_oid' => $val['t3ver_oid'],
					't3ver_wsid' => $val['t3ver_wsid'],
					't3ver_state' => $val['t3ver_state'],
					't3ver_stage' => $val['t3ver_stage'],
					'backend_layout_next_level' => $val['backend_layout_next_level']
				);
				if (isset($val['_ORIG_pid'])) {
					$output[$c]['_ORIG_pid'] = $val['_ORIG_pid'];
				}
			}
			$BEgetRootLine_cache[$ident] = $output;
		}
		return $output;
	}

	/**
	 * Gets the cached page record for the rootline
	 *
	 * @param integer $uid Page id for which to create the root line.
	 * @param string $clause Clause can be used to select other criteria. It would typically be where-clauses that stops the process if we meet a page, the user has no reading access to.
	 * @param boolean $workspaceOL If TRUE, version overlay is applied. This must be requested specifically because it is usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
	 * @return array Cached page record for the rootline
	 * @see BEgetRootLine
	 */
	static protected function getPageForRootline($uid, $clause, $workspaceOL) {
		static $getPageForRootline_cache = array();
		$ident = $uid . '-' . $clause . '-' . $workspaceOL;
		if (is_array($getPageForRootline_cache[$ident])) {
			$row = $getPageForRootline_cache[$ident];
		} else {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid,uid,title,TSconfig,is_siteroot,storage_pid,t3ver_oid,t3ver_wsid,t3ver_state,t3ver_stage,backend_layout_next_level', 'pages', 'uid=' . intval($uid) . ' ' . self::deleteClause('pages') . ' ' . $clause);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if ($row) {
				if ($workspaceOL) {
					self::workspaceOL('pages', $row);
				}
				if (is_array($row)) {
					self::fixVersioningPid('pages', $row);
					$getPageForRootline_cache[$ident] = $row;
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $row;
	}

	/**
	 * Opens the page tree to the specified page id
	 *
	 * @param integer $pid Page id.
	 * @param boolean $clearExpansion If set, then other open branches are closed.
	 * @return void
	 */
	static public function openPageTree($pid, $clearExpansion) {
		// Get current expansion data:
		if ($clearExpansion) {
			$expandedPages = array();
		} else {
			$expandedPages = unserialize($GLOBALS['BE_USER']->uc['browseTrees']['browsePages']);
		}
		// Get rootline:
		$rL = self::BEgetRootLine($pid);
		// First, find out what mount index to use (if more than one DB mount exists):
		$mountIndex = 0;
		$mountKeys = array_flip($GLOBALS['BE_USER']->returnWebmounts());
		foreach ($rL as $rLDat) {
			if (isset($mountKeys[$rLDat['uid']])) {
				$mountIndex = $mountKeys[$rLDat['uid']];
				break;
			}
		}
		// Traverse rootline and open paths:
		foreach ($rL as $rLDat) {
			$expandedPages[$mountIndex][$rLDat['uid']] = 1;
		}
		// Write back:
		$GLOBALS['BE_USER']->uc['browseTrees']['browsePages'] = serialize($expandedPages);
		$GLOBALS['BE_USER']->writeUC();
	}

	/**
	 * Returns the path (visually) of a page $uid, fx. "/First page/Second page/Another subpage"
	 * Each part of the path will be limited to $titleLimit characters
	 * Deleted pages are filtered out.
	 *
	 * @param integer $uid Page uid for which to create record path
	 * @param string $clause Clause is additional where clauses, eg.
	 * @param integer $titleLimit Title limit
	 * @param integer $fullTitleLimit Title limit of Full title (typ. set to 1000 or so)
	 * @return mixed Path of record (string) OR array with short/long title if $fullTitleLimit is set.
	 */
	static public function getRecordPath($uid, $clause, $titleLimit, $fullTitleLimit = 0) {
		if (!$titleLimit) {
			$titleLimit = 1000;
		}
		$loopCheck = 100;
		$output = ($fullOutput = '/');
		$clause = trim($clause);
		if ($clause !== '' && substr($clause, 0, 3) !== 'AND') {
			$clause = 'AND ' . $clause;
		}
		$data = self::BEgetRootLine($uid, $clause);
		foreach ($data as $record) {
			if ($record['uid'] === 0) {
				continue;
			}
			$output = '/' . \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(strip_tags($record['title']), $titleLimit) . $output;
			if ($fullTitleLimit) {
				$fullOutput = '/' . \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(strip_tags($record['title']), $fullTitleLimit) . $fullOutput;
			}
		}
		if ($fullTitleLimit) {
			return array($output, $fullOutput);
		} else {
			return $output;
		}
	}

	/**
	 * Returns an array with the exclude-fields as defined in TCA and FlexForms
	 * Used for listing the exclude-fields in be_groups forms
	 *
	 * @return array Array of arrays with excludeFields (fieldname, table:fieldname) from all TCA entries and from FlexForms (fieldname, table:extkey;sheetname;fieldname)
	 */
	static public function getExcludeFields() {
		$finalExcludeArray = array();

		// All TCA keys
		$tableNamesFromTca = array_keys($GLOBALS['TCA']);
		// Fetch translations for table names
		foreach ($tableNamesFromTca as $table) {
			$tableNamesFromTca[$table] = $GLOBALS['LANG']->sl($GLOBALS['TCA'][$table]['ctrl']['title']);
		}
		// Sort by translations
		asort($tableNamesFromTca);
		foreach ($tableNamesFromTca as $table => $translatedTable) {
			$excludeArrayTable = array();

			// All field names configured and not restricted to admins
			if (is_array($GLOBALS['TCA'][$table]['columns'])
					&& empty($GLOBALS['TCA'][$table]['ctrl']['adminOnly'])
					&& (empty($GLOBALS['TCA'][$table]['ctrl']['rootLevel']) || !empty($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreRootLevelRestriction']))
			) {
				$fieldKeys = array_keys($GLOBALS['TCA'][$table]['columns']);
				foreach ($fieldKeys as $field) {
					if ($GLOBALS['TCA'][$table]['columns'][$field]['exclude']) {
						// Get human readable names of fields
						$translatedField = $GLOBALS['LANG']->sl($GLOBALS['TCA'][$table]['columns'][$field]['label']);
						// Add entry
						$excludeArrayTable[] = array($translatedTable . ': ' . $translatedField, $table . ':' . $field);
					}
				}
			}
			// All FlexForm fields
			$flexFormArray = self::getRegisteredFlexForms($table);
			foreach ($flexFormArray as $tableField => $flexForms) {
				// Prefix for field label, e.g. "Plugin Options:"
				$labelPrefix = '';
				if (!empty($GLOBALS['TCA'][$table]['columns'][$tableField]['label'])) {
					$labelPrefix = $GLOBALS['LANG']->sl($GLOBALS['TCA'][$table]['columns'][$tableField]['label']);
				}
				// Get all sheets and title
				foreach ($flexForms as $extIdent => $extConf) {
					$extTitle = $GLOBALS['LANG']->sl($extConf['title']);
					// Get all fields in sheet
					foreach ($extConf['ds']['sheets'] as $sheetName => $sheet) {
						if (empty($sheet['ROOT']['el']) || !is_array($sheet['ROOT']['el'])) {
							continue;
						}
						foreach ($sheet['ROOT']['el'] as $fieldName => $field) {
							// Use only excludeable fields
							if (empty($field['TCEforms']['exclude'])) {
								continue;
							}
							$fieldLabel = !empty($field['TCEforms']['label']) ? $GLOBALS['LANG']->sl($field['TCEforms']['label']) : $fieldName;
							$fieldIdent = $table . ':' . $tableField . ';' . $extIdent . ';' . $sheetName . ';' . $fieldName;
							$excludeArrayTable[] = array(trim(($labelPrefix . ' ' . $extTitle), ': ') . ': ' . $fieldLabel, $fieldIdent);
						}
					}
				}
			}
			// Sort fields by the translated value
			if (count($excludeArrayTable) > 0) {
				usort($excludeArrayTable, array('TYPO3\\CMS\\Backend\\Form\\FlexFormsHelper', 'compareArraysByFirstValue'));
				$finalExcludeArray = array_merge($finalExcludeArray, $excludeArrayTable);
			}
		}

		return $finalExcludeArray;
	}

	/**
	 * Returns an array with explicit Allow/Deny fields.
	 * Used for listing these field/value pairs in be_groups forms
	 *
	 * @return array Array with information from all of $GLOBALS['TCA']
	 */
	static public function getExplicitAuthFieldValues() {
		// Initialize:
		$adLabel = array(
			'ALLOW' => $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_core.xlf:labels.allow'),
			'DENY' => $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_core.xlf:labels.deny')
		);
		// All TCA keys:
		$allowDenyOptions = array();
		$tc_keys = array_keys($GLOBALS['TCA']);
		foreach ($tc_keys as $table) {
			// All field names configured:
			if (is_array($GLOBALS['TCA'][$table]['columns'])) {
				$f_keys = array_keys($GLOBALS['TCA'][$table]['columns']);
				foreach ($f_keys as $field) {
					$fCfg = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
					if ($fCfg['type'] == 'select' && $fCfg['authMode']) {
						// Check for items:
						if (is_array($fCfg['items'])) {
							// Get Human Readable names of fields and table:
							$allowDenyOptions[$table . ':' . $field]['tableFieldLabel'] = $GLOBALS['LANG']->sl($GLOBALS['TCA'][$table]['ctrl']['title']) . ': ' . $GLOBALS['LANG']->sl($GLOBALS['TCA'][$table]['columns'][$field]['label']);
							// Check for items:
							foreach ($fCfg['items'] as $iVal) {
								// Values '' is not controlled by this setting.
								if (strcmp($iVal[1], '')) {
									// Find iMode
									$iMode = '';
									switch ((string) $fCfg['authMode']) {
									case 'explicitAllow':
										$iMode = 'ALLOW';
										break;
									case 'explicitDeny':
										$iMode = 'DENY';
										break;
									case 'individual':
										if (!strcmp($iVal[4], 'EXPL_ALLOW')) {
											$iMode = 'ALLOW';
										} elseif (!strcmp($iVal[4], 'EXPL_DENY')) {
											$iMode = 'DENY';
										}
										break;
									}
									// Set iMode
									if ($iMode) {
										$allowDenyOptions[$table . ':' . $field]['items'][$iVal[1]] = array($iMode, $GLOBALS['LANG']->sl($iVal[0]), $adLabel[$iMode]);
									}
								}
							}
						}
					}
				}
			}
		}
		return $allowDenyOptions;
	}

	/**
	 * Returns an array with system languages:
	 *
	 * Since TYPO3 4.5 the flagIcon is not returned as a filename in "gfx/flags/*" anymore,
	 * but as a string <flags-xx>. The calling party should call
	 * \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(<flags-xx>) to get an HTML
	 * which will represent the flag of this language.
	 *
	 * @return array Array with languages (title, uid, flagIcon)
	 */
	static public function getSystemLanguages() {
		$languages = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TranslationConfigurationProvider')->getSystemLanguages();
		$sysLanguages = array();
		foreach ($languages as $language) {
			if ($language['uid'] !== -1) {
				$sysLanguages[] = array(
					0 => htmlspecialchars($language['title']) . ' [' . $language['uid'] . ']',
					1 => $language['uid'],
					2 => $language['flagIcon']
				);
			}
		}
		return $sysLanguages;
	}

	/**
	 * Gets the original translation pointer table.
	 * For e.g. pages_language_overlay this would be pages.
	 *
	 * @param string $table Name of the table
	 * @return string Pointer table (if any)
	 */
	static public function getOriginalTranslationTable($table) {
		if (!empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'])) {
			$table = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'];
		}

		return $table;
	}

	/**
	 * Determines whether a table is localizable and has the languageField and transOrigPointerField set in $GLOBALS['TCA'].
	 *
	 * @param string $table The table to check
	 * @return boolean Whether a table is localizable
	 */
	static public function isTableLocalizable($table) {
		$isLocalizable = FALSE;
		if (isset($GLOBALS['TCA'][$table]['ctrl']) && is_array($GLOBALS['TCA'][$table]['ctrl'])) {
			$tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
			$isLocalizable = isset($tcaCtrl['languageField']) && $tcaCtrl['languageField'] && isset($tcaCtrl['transOrigPointerField']) && $tcaCtrl['transOrigPointerField'];
		}
		return $isLocalizable;
	}

	/**
	 * Returns the value of the property localizationMode in the given $config array ($GLOBALS['TCA'][<table>]['columns'][<field>]['config']).
	 * If the table is prepared for localization and no localizationMode is set, 'select' is returned by default.
	 * If the table is not prepared for localization or not defined at all in $GLOBALS['TCA'], FALSE is returned.
	 *
	 * @param string $table The name of the table to lookup in TCA
	 * @param mixed $fieldOrConfig The fieldname (string) or the configuration of the field to check (array)
	 * @return mixed If table is localizable, the set localizationMode is returned (if property is not set, 'select' is returned by default); if table is not localizable, FALSE is returned
	 */
	static public function getInlineLocalizationMode($table, $fieldOrConfig) {
		$localizationMode = FALSE;
		if (is_array($fieldOrConfig) && count($fieldOrConfig)) {
			$config = $fieldOrConfig;
		} elseif (is_string($fieldOrConfig) && isset($GLOBALS['TCA'][$table]['columns'][$fieldOrConfig]['config'])) {
			$config = $GLOBALS['TCA'][$table]['columns'][$fieldOrConfig]['config'];
		}
		if (is_array($config) && isset($config['type']) && $config['type'] == 'inline' && self::isTableLocalizable($table)) {
			$localizationMode = isset($config['behaviour']['localizationMode']) && $config['behaviour']['localizationMode'] ? $config['behaviour']['localizationMode'] : 'select';
			// The mode 'select' is not possible when child table is not localizable at all:
			if ($localizationMode == 'select' && !self::isTableLocalizable($config['foreign_table'])) {
				$localizationMode = FALSE;
			}
		}
		return $localizationMode;
	}

	/**
	 * Returns a page record (of page with $id) with an extra field "_thePath" set to the record path IF the WHERE clause, $perms_clause, selects the record. Thus is works as an access check that returns a page record if access was granted, otherwise not.
	 * If $id is zero a pseudo root-page with "_thePath" set is returned IF the current BE_USER is admin.
	 * In any case ->isInWebMount must return TRUE for the user (regardless of $perms_clause)
	 *
	 * @param integer $id Page uid for which to check read-access
	 * @param string $perms_clause This is typically a value generated with $GLOBALS['BE_USER']->getPagePermsClause(1);
	 * @return array Returns page record if OK, otherwise FALSE.
	 */
	static public function readPageAccess($id, $perms_clause) {
		if ((string) $id != '') {
			$id = intval($id);
			if (!$id) {
				if ($GLOBALS['BE_USER']->isAdmin()) {
					$path = '/';
					$pageinfo['_thePath'] = $path;
					return $pageinfo;
				}
			} else {
				$pageinfo = self::getRecord('pages', $id, '*', $perms_clause ? ' AND ' . $perms_clause : '');
				if ($pageinfo['uid'] && $GLOBALS['BE_USER']->isInWebMount($id, $perms_clause)) {
					self::workspaceOL('pages', $pageinfo);
					if (is_array($pageinfo)) {
						self::fixVersioningPid('pages', $pageinfo);
						list($pageinfo['_thePath'], $pageinfo['_thePathFull']) = self::getRecordPath(intval($pageinfo['uid']), $perms_clause, 15, 1000);
						return $pageinfo;
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * Returns the "types" configuration parsed into an array for the record, $rec, from table, $table
	 *
	 * @param string $table Table name (present in TCA)
	 * @param array $rec Record from $table
	 * @param boolean $useFieldNameAsKey If $useFieldNameAsKey is set, then the fieldname is associative keys in the return array, otherwise just numeric keys.
	 * @return array
	 */
	static public function getTCAtypes($table, $rec, $useFieldNameAsKey = 0) {
		if ($GLOBALS['TCA'][$table]) {
			// Get type value:
			$fieldValue = self::getTCAtypeValue($table, $rec);
			// Get typesConf
			$typesConf = $GLOBALS['TCA'][$table]['types'][$fieldValue];
			// Get fields list and traverse it
			$fieldList = explode(',', $typesConf['showitem']);
			$altFieldList = array();
			// Traverse fields in types config and parse the configuration into a nice array:
			foreach ($fieldList as $k => $v) {
				list($pFieldName, $pAltTitle, $pPalette, $pSpec) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $v);
				$defaultExtras = is_array($GLOBALS['TCA'][$table]['columns'][$pFieldName]) ? $GLOBALS['TCA'][$table]['columns'][$pFieldName]['defaultExtras'] : '';
				$specConfParts = self::getSpecConfParts($pSpec, $defaultExtras);
				$fieldList[$k] = array(
					'field' => $pFieldName,
					'title' => $pAltTitle,
					'palette' => $pPalette,
					'spec' => $specConfParts,
					'origString' => $v
				);
				if ($useFieldNameAsKey) {
					$altFieldList[$fieldList[$k]['field']] = $fieldList[$k];
				}
			}
			if ($useFieldNameAsKey) {
				$fieldList = $altFieldList;
			}
			// Return array:
			return $fieldList;
		}
	}

	/**
	 * Returns the "type" value of $rec from $table which can be used to look up the correct "types" rendering section in $GLOBALS['TCA']
	 * If no "type" field is configured in the "ctrl"-section of the $GLOBALS['TCA'] for the table, zero is used.
	 * If zero is not an index in the "types" section of $GLOBALS['TCA'] for the table, then the $fieldValue returned will default to 1 (no matter if that is an index or not)
	 *
	 * Note: This method is very similar to \TYPO3\CMS\Backend\Form\FormEngine::getRTypeNum(),
	 * however, it has two differences:
	 * 1) The method in TCEForms also takes care of localization (which is difficult to do here as the whole infrastructure for language overlays is only in TCEforms).
	 * 2) The $rec array looks different in TCEForms, as in there it's not the raw record but the \TYPO3\CMS\Backend\Form\DataPreprocessor version of it, which changes e.g. how "select"
	 * and "group" field values are stored, which makes different processing of the "foreign pointer field" type field variant necessary.
	 *
	 * @param string $table Table name present in TCA
	 * @param array $row Record from $table
	 * @return string Field value
	 * @see getTCAtypes()
	 */
	static public function getTCAtypeValue($table, $row) {
		$typeNum = 0;
		if ($GLOBALS['TCA'][$table]) {
			$field = $GLOBALS['TCA'][$table]['ctrl']['type'];
			if (strpos($field, ':') !== FALSE) {
				list($pointerField, $foreignTableTypeField) = explode(':', $field);
				// Get field value from database if field is not in the $row array
				if (!isset($row[$pointerField])) {
					$localRow = self::getRecord($table, $row['uid'], $pointerField);
					$foreignUid = $localRow[$pointerField];
				} else {
					$foreignUid = $row[$pointerField];
				}
				if ($foreignUid) {
					$fieldConfig = $GLOBALS['TCA'][$table]['columns'][$pointerField]['config'];
					$relationType = $fieldConfig['type'];
					if ($relationType === 'select') {
						$foreignTable = $fieldConfig['foreign_table'];
					} elseif ($relationType === 'group') {
						$allowedTables = explode(',', $fieldConfig['allowed']);
						$foreignTable = $allowedTables[0];
					} else {
						throw new \RuntimeException('TCA foreign field pointer fields are only allowed to be used with group or select field types.', 1325862240);
					}
					$foreignRow = self::getRecord($foreignTable, $foreignUid, $foreignTableTypeField);
					if ($foreignRow[$foreignTableTypeField]) {
						$typeNum = $foreignRow[$foreignTableTypeField];
					}
				}
			} else {
				$typeNum = $row[$field];
			}
			// If that value is an empty string, set it to "0" (zero)
			if (!strcmp($typeNum, '')) {
				$typeNum = 0;
			}
		}
		// If current typeNum doesn't exist, set it to 0 (or to 1 for historical reasons, if 0 doesn't exist)
		if (!$GLOBALS['TCA'][$table]['types'][$typeNum]) {
			$typeNum = $GLOBALS['TCA'][$table]['types']['0'] ? 0 : 1;
		}
		// Force to string. Necessary for eg '-1' to be recognized as a type value.
		$typeNum = (string) $typeNum;
		return $typeNum;
	}

	/**
	 * Parses a part of the field lists in the "types"-section of $GLOBALS['TCA'] arrays, namely the "special configuration" at index 3 (position 4)
	 * Elements are splitted by ":" and within those parts, parameters are splitted by "|".
	 * Everything is returned in an array and you should rather see it visually than listen to me anymore now...  Check out example in Inside TYPO3
	 *
	 * @param string $str Content from the "types" configuration of TCA (the special configuration) - see description of function
	 * @param string $defaultExtras The ['defaultExtras'] value from field configuration
	 * @return array
	 */
	static public function getSpecConfParts($str, $defaultExtras) {
		// Add defaultExtras:
		$specConfParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $defaultExtras . ':' . $str, 1);
		$reg = array();
		if (count($specConfParts)) {
			foreach ($specConfParts as $k2 => $v2) {
				unset($specConfParts[$k2]);
				if (preg_match('/(.*)\\[(.*)\\]/', $v2, $reg)) {
					$specConfParts[trim($reg[1])] = array(
						'parameters' => \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $reg[2], 1)
					);
				} else {
					$specConfParts[trim($v2)] = 1;
				}
			}
		} else {
			$specConfParts = array();
		}
		return $specConfParts;
	}

	/**
	 * Takes an array of "[key] = [value]" strings and returns an array with the keys set as keys pointing to the value.
	 * Better see it in action! Find example in Inside TYPO3
	 *
	 * @param array $pArr Array of "[key] = [value]" strings to convert.
	 * @return array
	 */
	static public function getSpecConfParametersFromArray($pArr) {
		$out = array();
		if (is_array($pArr)) {
			foreach ($pArr as $k => $v) {
				$parts = explode('=', $v, 2);
				if (count($parts) == 2) {
					$out[trim($parts[0])] = trim($parts[1]);
				} else {
					$out[$k] = $v;
				}
			}
		}
		return $out;
	}

	/**
	 * Finds the Data Structure for a FlexForm field
	 * NOTE ON data structures for deleted records: This function may fail to deliver the data structure for a record for a few reasons: a) The data structure could be deleted (either with deleted-flagged or hard-deleted), b) the data structure is fetched using the ds_pointerField_searchParent in which case any deleted record on the route to the final location of the DS will make it fail. In theory, we can solve the problem in the case where records that are deleted-flagged keeps us from finding the DS - this is done at the markers ###NOTE_A### where we make sure to also select deleted records. However, we generally want the DS lookup to fail for deleted records since for the working website we expect a deleted-flagged record to be as inaccessible as one that is completely deleted from the DB. Any way we look at it, this may lead to integrity problems of the reference index and even lost files if attached. However, that is not really important considering that a single change to a data structure can instantly invalidate large amounts of the reference index which we do accept as a cost for the flexform features. Other than requiring a reference index update, deletion of/changes in data structure or the failure to look them up when completely deleting records may lead to lost files in the uploads/ folders since those are now without a proper reference.
	 *
	 * @param array $conf Field config array
	 * @param array $row Record data
	 * @param string $table The table name
	 * @param string $fieldName Optional fieldname passed to hook object
	 * @param boolean $WSOL Boolean; If set, workspace overlay is applied to records. This is correct behaviour for all presentation and export, but NOT if you want a TRUE reflection of how things are in the live workspace.
	 * @param integer $newRecordPidValue SPECIAL CASES: Use this, if the DataStructure may come from a parent record and the INPUT row doesn't have a uid yet (hence, the pid cannot be looked up). Then it is necessary to supply a PID value to search recursively in for the DS (used from TCEmain)
	 * @return mixed If array, the data structure was found and returned as an array. Otherwise (string) it is an error message.
	 * @see \TYPO3\CMS\Backend\Form\FormEngine::getSingleField_typeFlex()
	 */
	static public function getFlexFormDS($conf, $row, $table, $fieldName = '', $WSOL = TRUE, $newRecordPidValue = 0) {
		// Get pointer field etc from TCA-config:
		$ds_pointerField = $conf['ds_pointerField'];
		$ds_array = $conf['ds'];
		$ds_tableField = $conf['ds_tableField'];
		$ds_searchParentField = $conf['ds_pointerField_searchParent'];
		// Find source value:
		$dataStructArray = '';
		// If there is a data source array, that takes precedence
		if (is_array($ds_array)) {
			// If a pointer field is set, take the value from that field in the $row array and use as key.
			if ($ds_pointerField) {
				// Up to two pointer fields can be specified in a comma separated list.
				$pointerFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $ds_pointerField);
				// If we have two pointer fields, the array keys should contain both field values separated by comma. The asterisk "*" catches all values. For backwards compatibility, it's also possible to specify only the value of the first defined ds_pointerField.
				if (count($pointerFields) == 2) {
					if ($ds_array[$row[$pointerFields[0]] . ',' . $row[$pointerFields[1]]]) {
						// Check if we have a DS for the combination of both pointer fields values
						$srcPointer = $row[$pointerFields[0]] . ',' . $row[$pointerFields[1]];
					} elseif ($ds_array[$row[$pointerFields[1]] . ',*']) {
						// Check if we have a DS for the value of the first pointer field suffixed with ",*"
						$srcPointer = $row[$pointerFields[1]] . ',*';
					} elseif ($ds_array['*,' . $row[$pointerFields[1]]]) {
						// Check if we have a DS for the value of the second pointer field prefixed with "*,"
						$srcPointer = '*,' . $row[$pointerFields[1]];
					} elseif ($ds_array[$row[$pointerFields[0]]]) {
						// Check if we have a DS for just the value of the first pointer field (mainly for backwards compatibility)
						$srcPointer = $row[$pointerFields[0]];
					}
				} else {
					$srcPointer = $row[$pointerFields[0]];
				}
				$srcPointer = isset($ds_array[$srcPointer]) ? $srcPointer : 'default';
			} else {
				$srcPointer = 'default';
			}
			// Get Data Source: Detect if it's a file reference and in that case read the file and parse as XML. Otherwise the value is expected to be XML.
			if (substr($ds_array[$srcPointer], 0, 5) == 'FILE:') {
				$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(substr($ds_array[$srcPointer], 5));
				if ($file && @is_file($file)) {
					$dataStructArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file));
				} else {
					$dataStructArray = 'The file "' . substr($ds_array[$srcPointer], 5) . '" in ds-array key "' . $srcPointer . '" was not found ("' . $file . '")';
				}
			} else {
				$dataStructArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($ds_array[$srcPointer]);
			}
		} elseif ($ds_pointerField) {
			// If pointer field AND possibly a table/field is set:
			// Value of field pointed to:
			$srcPointer = $row[$ds_pointerField];
			// Searching recursively back if 'ds_pointerField_searchParent' is defined (typ. a page rootline, or maybe a tree-table):
			if ($ds_searchParentField && !$srcPointer) {
				$rr = self::getRecord($table, $row['uid'], 'uid,' . $ds_searchParentField);
				// Get the "pid" field - we cannot know that it is in the input record! ###NOTE_A###
				if ($WSOL) {
					self::workspaceOL($table, $rr);
					self::fixVersioningPid($table, $rr, TRUE);
				}
				$uidAcc = array();
				// Used to avoid looping, if any should happen.
				$subFieldPointer = $conf['ds_pointerField_searchParent_subField'];
				while (!$srcPointer) {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,' . $ds_pointerField . ',' . $ds_searchParentField . ($subFieldPointer ? ',' . $subFieldPointer : ''), $table, 'uid=' . intval(($newRecordPidValue ? $newRecordPidValue : $rr[$ds_searchParentField])) . self::deleteClause($table));
					$newRecordPidValue = 0;
					$rr = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
					// Break if no result from SQL db or if looping...
					if (!is_array($rr) || isset($uidAcc[$rr['uid']])) {
						break;
					}
					$uidAcc[$rr['uid']] = 1;
					if ($WSOL) {
						self::workspaceOL($table, $rr);
						self::fixVersioningPid($table, $rr, TRUE);
					}
					$srcPointer = $subFieldPointer && $rr[$subFieldPointer] ? $rr[$subFieldPointer] : $rr[$ds_pointerField];
				}
			}
			// If there is a srcPointer value:
			if ($srcPointer) {
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($srcPointer)) {
					// If integer, then its a record we will look up:
					list($tName, $fName) = explode(':', $ds_tableField, 2);
					if ($tName && $fName && is_array($GLOBALS['TCA'][$tName])) {
						$dataStructRec = self::getRecord($tName, $srcPointer);
						if ($WSOL) {
							self::workspaceOL($tName, $dataStructRec);
						}
						if (strpos($dataStructRec[$fName], '<') === FALSE) {
							if (is_file(PATH_site . $dataStructRec[$fName])) {
								// The value is a pointer to a file
								$dataStructArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(PATH_site . $dataStructRec[$fName]));
							} else {
								$dataStructArray = sprintf('File \'%s\' was not found', $dataStructRec[$fName]);
							}
						} else {
							// No file pointer, handle as being XML (default behaviour)
							$dataStructArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($dataStructRec[$fName]);
						}
					} else {
						$dataStructArray = 'No tablename (' . $tName . ') or fieldname (' . $fName . ') was found an valid!';
					}
				} else {
					// Otherwise expect it to be a file:
					$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($srcPointer);
					if ($file && @is_file($file)) {
						$dataStructArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file));
					} else {
						// Error message.
						$dataStructArray = 'The file "' . $srcPointer . '" was not found ("' . $file . '")';
					}
				}
			} else {
				// Error message.
				$dataStructArray = 'No source value in fieldname "' . $ds_pointerField . '"';
			}
		} else {
			$dataStructArray = 'No proper configuration!';
		}
		// Hook for post-processing the Flexform DS. Introduces the possibility to configure Flexforms via TSConfig
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass'] as $classRef) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'getFlexFormDS_postProcessDS')) {
					$hookObj->getFlexFormDS_postProcessDS($dataStructArray, $conf, $row, $table, $fieldName);
				}
			}
		}
		return $dataStructArray;
	}

	/**
	 * Returns all registered FlexForm definitions with title and fields
	 *
	 * @param string $table The content table
	 * @return array The data structures with speaking extension title
	 * @see \TYPO3\CMS\Backend\Utility\BackendUtility::getExcludeFields()
	 */
	static public function getRegisteredFlexForms($table = 'tt_content') {
		if (empty($table) || empty($GLOBALS['TCA'][$table]['columns'])) {
			return array();
		}
		$flexForms = array();
		foreach ($GLOBALS['TCA'][$table]['columns'] as $tableField => $fieldConf) {
			if (!empty($fieldConf['config']['type']) && !empty($fieldConf['config']['ds']) && $fieldConf['config']['type'] == 'flex') {
				$flexForms[$tableField] = array();
				unset($fieldConf['config']['ds']['default']);
				// Get pointer fields
				$pointerFields = !empty($fieldConf['config']['ds_pointerField']) ? $fieldConf['config']['ds_pointerField'] : 'list_type,CType';
				$pointerFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $pointerFields);
				// Get FlexForms
				foreach ($fieldConf['config']['ds'] as $flexFormKey => $dataStruct) {
					// Get extension identifier (uses second value if it's not empty, "list" or "*", else first one)
					$identFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $flexFormKey);
					$extIdent = $identFields[0];
					if (!empty($identFields[1]) && $identFields[1] != 'list' && $identFields[1] != '*') {
						$extIdent = $identFields[1];
					}
					// Load external file references
					if (!is_array($dataStruct)) {
						$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(str_ireplace('FILE:', '', $dataStruct));
						if ($file && @is_file($file)) {
							$dataStruct = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file);
						}
						$dataStruct = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($dataStruct);
						if (!is_array($dataStruct)) {
							continue;
						}
					}
					// Get flexform content
					$dataStruct = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveAllSheetsInDS($dataStruct);
					if (empty($dataStruct['sheets']) || !is_array($dataStruct['sheets'])) {
						continue;
					}
					// Use DS pointer to get extension title from TCA
					$title = $extIdent;
					$keyFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $flexFormKey);
					foreach ($pointerFields as $pointerKey => $pointerName) {
						if (empty($keyFields[$pointerKey]) || $keyFields[$pointerKey] == '*' || $keyFields[$pointerKey] == 'list') {
							continue;
						}
						if (!empty($GLOBALS['TCA'][$table]['columns'][$pointerName]['config']['items'])) {
							$items = $GLOBALS['TCA'][$table]['columns'][$pointerName]['config']['items'];
							if (!is_array($items)) {
								continue;
							}
							foreach ($items as $itemConf) {
								if (!empty($itemConf[0]) && !empty($itemConf[1]) && $itemConf[1] == $keyFields[$pointerKey]) {
									$title = $itemConf[0];
									break 2;
								}
							}
						}
					}
					$flexForms[$tableField][$extIdent] = array(
						'title' => $title,
						'ds' => $dataStruct
					);
				}
			}
		}
		return $flexForms;
	}

	/*******************************************
	 *
	 * Caching related
	 *
	 *******************************************/
	/**
	 * Stores the string value $data in the 'cache_hash' cache with the
	 * hash key, $hash, and visual/symbolic identification, $ident
	 * IDENTICAL to the function by same name found in \TYPO3\CMS\Frontend\Page\PageRepository
	 *
	 * @param string $hash 32 bit hash string (eg. a md5 hash of a serialized array identifying the data being stored)
	 * @param string $data The data string. If you want to store an array, then just serialize it first.
	 * @param string $ident $ident is just a textual identification in order to inform about the content!
	 * @return 	void
	 */
	static public function storeHash($hash, $data, $ident) {
		$GLOBALS['typo3CacheManager']->getCache('cache_hash')->set($hash, $data, array('ident_' . $ident), 0);
	}

	/**
	 * Returns string value stored for the hash string in the cache "cache_hash"
	 * Can be used to retrieved a cached value
	 *
	 * IDENTICAL to the function by same name found in \TYPO3\CMS\Frontend\Page\PageRepository
	 *
	 * @param string $hash The hash-string which was used to store the data value
	 * @param integer $expTime Variabele is not used in the function
	 * @return string
	 */
	static public function getHash($hash, $expTime = 0) {
		$hashContent = NULL;
		$cacheEntry = $GLOBALS['typo3CacheManager']->getCache('cache_hash')->get($hash);
		if ($cacheEntry) {
			$hashContent = $cacheEntry;
		}
		return $hashContent;
	}

	/*******************************************
	 *
	 * TypoScript related
	 *
	 *******************************************/
	/**
	 * Returns the Page TSconfig for page with id, $id
	 *
	 * @param $id integer Page uid for which to create Page TSconfig
	 * @param $rootLine array If $rootLine is an array, that is used as rootline, otherwise rootline is just calculated
	 * @param boolean $returnPartArray If $returnPartArray is set, then the array with accumulated Page TSconfig is returned non-parsed. Otherwise the output will be parsed by the TypoScript parser.
	 * @return array Page TSconfig
	 * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
	 */
	static public function getPagesTSconfig($id, $rootLine = '', $returnPartArray = 0) {
		$id = intval($id);
		if (!is_array($rootLine)) {
			$rootLine = self::BEgetRootLine($id, '', TRUE);
		}
		// Order correctly
		ksort($rootLine);
		$TSdataArray = array();
		// Setting default configuration
		$TSdataArray['defaultPageTSconfig'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'];
		foreach ($rootLine as $k => $v) {
			$TSdataArray['uid_' . $v['uid']] = $v['TSconfig'];
		}
		$TSdataArray = \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines_array($TSdataArray);
		if ($returnPartArray) {
			return $TSdataArray;
		}
		// Parsing the page TS-Config (or getting from cache)
		$pageTS = implode(LF . '[GLOBAL]' . LF, $TSdataArray);
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['TSconfigConditions']) {
			/* @var $parseObj \TYPO3\CMS\Backend\Configuration\TsConfigParser */
			$parseObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TsConfigParser');
			$res = $parseObj->parseTSconfig($pageTS, 'PAGES', $id, $rootLine);
			if ($res) {
				$TSconfig = $res['TSconfig'];
			}
		} else {
			$hash = md5('pageTS:' . $pageTS);
			$cachedContent = self::getHash($hash);
			$TSconfig = array();
			if (isset($cachedContent)) {
				$TSconfig = unserialize($cachedContent);
			} else {
				$parseObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
				$parseObj->parse($pageTS);
				$TSconfig = $parseObj->setup;
				self::storeHash($hash, serialize($TSconfig), 'PAGES_TSconfig');
			}
		}
		// Get User TSconfig overlay
		$userTSconfig = $GLOBALS['BE_USER']->userTS['page.'];
		if (is_array($userTSconfig)) {
			$TSconfig = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($TSconfig, $userTSconfig);
		}
		return $TSconfig;
	}

	/**
	 * Updates Page TSconfig for a page with $id
	 * The function seems to take $pageTS as an array with properties and compare the values with those that already exists for the "object string", $TSconfPrefix, for the page, then sets those values which were not present.
	 * $impParams can be supplied as already known Page TSconfig, otherwise it's calculated.
	 *
	 * THIS DOES NOT CHECK ANY PERMISSIONS. SHOULD IT?
	 * More documentation is needed.
	 *
	 * @param integer $id Page id
	 * @param array $pageTS Page TS array to write
	 * @param string $TSconfPrefix Prefix for object paths
	 * @param array $impParams [Description needed.]
	 * @return 	void
	 * @internal
	 * @see implodeTSParams(), getPagesTSconfig()
	 */
	static public function updatePagesTSconfig($id, $pageTS, $TSconfPrefix, $impParams = '') {
		$id = intval($id);
		if (is_array($pageTS) && $id > 0) {
			if (!is_array($impParams)) {
				$impParams = self::implodeTSParams(self::getPagesTSconfig($id));
			}
			$set = array();
			foreach ($pageTS as $f => $v) {
				$f = $TSconfPrefix . $f;
				if (!isset($impParams[$f]) && trim($v) || strcmp(trim($impParams[$f]), trim($v))) {
					$set[$f] = trim($v);
				}
			}
			if (count($set)) {
				// Get page record and TS config lines
				$pRec = self::getRecord('pages', $id);
				$TSlines = explode(LF, $pRec['TSconfig']);
				$TSlines = array_reverse($TSlines);
				// Reset the set of changes.
				foreach ($set as $f => $v) {
					$inserted = 0;
					foreach ($TSlines as $ki => $kv) {
						if (substr($kv, 0, strlen($f) + 1) == $f . '=') {
							$TSlines[$ki] = $f . '=' . $v;
							$inserted = 1;
							break;
						}
					}
					if (!$inserted) {
						$TSlines = array_reverse($TSlines);
						$TSlines[] = $f . '=' . $v;
						$TSlines = array_reverse($TSlines);
					}
				}
				$TSlines = array_reverse($TSlines);
				// Store those changes
				$TSconf = implode(LF, $TSlines);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid=' . intval($id), array('TSconfig' => $TSconf));
			}
		}
	}

	/**
	 * Implodes a multi dimensional TypoScript array, $p, into a one-dimentional array (return value)
	 *
	 * @param array $p TypoScript structure
	 * @param string $k Prefix string
	 * @return array Imploded TypoScript objectstring/values
	 */
	static public function implodeTSParams($p, $k = '') {
		$implodeParams = array();
		if (is_array($p)) {
			foreach ($p as $kb => $val) {
				if (is_array($val)) {
					$implodeParams = array_merge($implodeParams, self::implodeTSParams($val, $k . $kb));
				} else {
					$implodeParams[$k . $kb] = $val;
				}
			}
		}
		return $implodeParams;
	}

	/*******************************************
	 *
	 * Users / Groups related
	 *
	 *******************************************/
	/**
	 * Returns an array with be_users records of all user NOT DELETED sorted by their username
	 * Keys in the array is the be_users uid
	 *
	 * @param string $fields Optional $fields list (default: username,usergroup,usergroup_cached_list,uid) can be used to set the selected fields
	 * @param string $where Optional $where clause (fx. "AND username='pete'") can be used to limit query
	 * @return array
	 */
	static public function getUserNames($fields = 'username,usergroup,usergroup_cached_list,uid', $where = '') {
		$be_user_Array = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'be_users', 'pid=0 ' . $where . self::deleteClause('be_users'), '', 'username');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$be_user_Array[$row['uid']] = $row;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $be_user_Array;
	}

	/**
	 * Returns an array with be_groups records (title, uid) of all groups NOT DELETED sorted by their title
	 *
	 * @param string $fields Field list
	 * @param string $where WHERE clause
	 * @return 	array
	 */
	static public function getGroupNames($fields = 'title,uid', $where = '') {
		$be_group_Array = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'be_groups', 'pid=0 ' . $where . self::deleteClause('be_groups'), '', 'title');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$be_group_Array[$row['uid']] = $row;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $be_group_Array;
	}

	/**
	 * Returns an array with be_groups records (like ->getGroupNames) but:
	 * - if the current BE_USER is admin, then all groups are returned, otherwise only groups that the current user is member of (usergroup_cached_list) will be returned.
	 *
	 * @param string $fields Field list; $fields specify the fields selected (default: title,uid)
	 * @return 	array
	 */
	static public function getListGroupNames($fields = 'title, uid') {
		$exQ = ' AND hide_in_lists=0';
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$exQ .= ' AND uid IN (' . ($GLOBALS['BE_USER']->user['usergroup_cached_list'] ? $GLOBALS['BE_USER']->user['usergroup_cached_list'] : 0) . ')';
		}
		return self::getGroupNames($fields, $exQ);
	}

	/**
	 * Returns the array $usernames with the names of all users NOT IN $groupArray changed to the uid (hides the usernames!).
	 * If $excludeBlindedFlag is set, then these records are unset from the array $usernames
	 * Takes $usernames (array made by \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames()) and a $groupArray (array with the groups a certain user is member of) as input
	 *
	 * @param array $usernames User names
	 * @param array $groupArray Group names
	 * @param boolean $excludeBlindedFlag If $excludeBlindedFlag is set, then these records are unset from the array $usernames
	 * @return array User names, blinded
	 */
	static public function blindUserNames($usernames, $groupArray, $excludeBlindedFlag = 0) {
		if (is_array($usernames) && is_array($groupArray)) {
			foreach ($usernames as $uid => $row) {
				$userN = $uid;
				$set = 0;
				if ($row['uid'] != $GLOBALS['BE_USER']->user['uid']) {
					foreach ($groupArray as $v) {
						if ($v && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($row['usergroup_cached_list'], $v)) {
							$userN = $row['username'];
							$set = 1;
						}
					}
				} else {
					$userN = $row['username'];
					$set = 1;
				}
				$usernames[$uid]['username'] = $userN;
				if ($excludeBlindedFlag && !$set) {
					unset($usernames[$uid]);
				}
			}
		}
		return $usernames;
	}

	/**
	 * Corresponds to blindUserNames but works for groups instead
	 *
	 * @param array $groups Group names
	 * @param array $groupArray Group names (reference)
	 * @param boolean $excludeBlindedFlag If $excludeBlindedFlag is set, then these records are unset from the array $usernames
	 * @return array
	 */
	static public function blindGroupNames($groups, $groupArray, $excludeBlindedFlag = 0) {
		if (is_array($groups) && is_array($groupArray)) {
			foreach ($groups as $uid => $row) {
				$groupN = $uid;
				$set = 0;
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inArray($groupArray, $uid)) {
					$groupN = $row['title'];
					$set = 1;
				}
				$groups[$uid]['title'] = $groupN;
				if ($excludeBlindedFlag && !$set) {
					unset($groups[$uid]);
				}
			}
		}
		return $groups;
	}

	/*******************************************
	 *
	 * Output related
	 *
	 *******************************************/
	/**
	 * Returns the difference in days between input $tstamp and $EXEC_TIME
	 *
	 * @param integer $tstamp Time stamp, seconds
	 * @return integer
	 */
	static public function daysUntil($tstamp) {
		$delta_t = $tstamp - $GLOBALS['EXEC_TIME'];
		return ceil($delta_t / (3600 * 24));
	}

	/**
	 * Returns $tstamp formatted as "ddmmyy" (According to $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'])
	 *
	 * @param integer $tstamp Time stamp, seconds
	 * @return string Formatted time
	 */
	static public function date($tstamp) {
		return date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], (int) $tstamp);
	}

	/**
	 * Returns $tstamp formatted as "ddmmyy hhmm" (According to $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] AND $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'])
	 *
	 * @param integer $value Time stamp, seconds
	 * @return string Formatted time
	 */
	static public function datetime($value) {
		return date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $value);
	}

	/**
	 * Returns $value (in seconds) formatted as hh:mm:ss
	 * For instance $value = 3600 + 60*2 + 3 should return "01:02:03"
	 *
	 * @param integer $value Time stamp, seconds
	 * @param boolean $withSeconds Output hh:mm:ss. If FALSE: hh:mm
	 * @return string Formatted time
	 */
	static public function time($value, $withSeconds = TRUE) {
		$hh = floor($value / 3600);
		$min = floor(($value - $hh * 3600) / 60);
		$sec = $value - $hh * 3600 - $min * 60;
		$l = sprintf('%02d', $hh) . ':' . sprintf('%02d', $min);
		if ($withSeconds) {
			$l .= ':' . sprintf('%02d', $sec);
		}
		return $l;
	}

	/**
	 * Returns the "age" in minutes / hours / days / years of the number of $seconds inputted.
	 *
	 * @param integer $seconds Seconds could be the difference of a certain timestamp and time()
	 * @param string $labels Labels should be something like ' min| hrs| days| yrs| min| hour| day| year'. This value is typically delivered by this function call: $GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears")
	 * @return string Formatted time
	 */
	static public function calcAge($seconds, $labels = ' min| hrs| days| yrs| min| hour| day| year') {
		$labelArr = explode('|', $labels);
		$absSeconds = abs($seconds);
		$sign = $seconds < 0 ? -1 : 1;
		if ($absSeconds < 3600) {
			$val = round($absSeconds / 60);
			$seconds = $sign * $val . ($val == 1 ? $labelArr[4] : $labelArr[0]);
		} elseif ($absSeconds < 24 * 3600) {
			$val = round($absSeconds / 3600);
			$seconds = $sign * $val . ($val == 1 ? $labelArr[5] : $labelArr[1]);
		} elseif ($absSeconds < 365 * 24 * 3600) {
			$val = round($absSeconds / (24 * 3600));
			$seconds = $sign * $val . ($val == 1 ? $labelArr[6] : $labelArr[2]);
		} else {
			$val = round($absSeconds / (365 * 24 * 3600));
			$seconds = $sign * $val . ($val == 1 ? $labelArr[7] : $labelArr[3]);
		}
		return $seconds;
	}

	/**
	 * Returns a formatted timestamp if $tstamp is set.
	 * The date/datetime will be followed by the age in parenthesis.
	 *
	 * @param integer $tstamp Time stamp, seconds
	 * @param integer $prefix 1/-1 depending on polarity of age.
	 * @param string $date $date=="date" will yield "dd:mm:yy" formatting, otherwise "dd:mm:yy hh:mm
	 * @return string
	 */
	static public function dateTimeAge($tstamp, $prefix = 1, $date = '') {
		return $tstamp ? ($date == 'date' ? self::date($tstamp) : self::datetime($tstamp)) . ' (' . self::calcAge($prefix * ($GLOBALS['EXEC_TIME'] - $tstamp), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')) . ')' : '';
	}

	/**
	 * Returns alt="" and title="" attributes with the value of $content.
	 *
	 * @param string $content Value for 'alt' and 'title' attributes (will be htmlspecialchars()'ed before output)
	 * @return string
	 */
	static public function titleAltAttrib($content) {
		$out = '';
		$out .= ' alt="' . htmlspecialchars($content) . '"';
		$out .= ' title="' . htmlspecialchars($content) . '"';
		return $out;
	}

	/**
	 * Returns a linked image-tag for thumbnail(s)/fileicons/truetype-font-previews from a database row with a list of image files in a field
	 * All $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] extension are made to thumbnails + ttf file (renders font-example)
	 * Thumbsnails are linked to the show_item.php script which will display further details.
	 *
	 * @param array $row Row is the database row from the table, $table.
	 * @param string $table Table name for $row (present in TCA)
	 * @param string $field Field is pointing to the list of image files
	 * @param string $backPath Back path prefix for image tag src="" field
	 * @param string $thumbScript Optional: $thumbScript - not used anymore since FAL
	 * @param string $uploaddir Optional: $uploaddir is the directory relative to PATH_site where the image files from the $field value is found (Is by default set to the entry in $GLOBALS['TCA'] for that field! so you don't have to!)
	 * @param boolean $abs If set, uploaddir is NOT prepended with "../
	 * @param string $tparams Optional: $tparams is additional attributes for the image tags
	 * @param integer $size Optional: $size is [w]x[h] of the thumbnail. 56 is default.
	 * @param boolean $linkInfoPopup Whether to wrap with a link opening the info popup
	 * @return string Thumbnail image tag.
	 */
	static public function thumbCode($row, $table, $field, $backPath, $thumbScript = '', $uploaddir = NULL, $abs = 0, $tparams = '', $size = '', $linkInfoPopup = TRUE) {
		$tcaConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
		// Check and parse the size parameter
		$sizeParts = array(64, 64);
		if ($size = trim($size)) {
			$sizeParts = explode('x', $size . 'x' . $size);
			if (!intval($sizeParts[0])) {
				$size = '';
			}
		}
		$thumbData = '';
		// FAL references
		if ($tcaConfig['type'] === 'inline') {
			$referenceUids = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'sys_file_reference', 'tablenames = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_file_reference') . ' AND fieldname=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($field, 'sys_file_reference') . ' AND uid_foreign=' . intval($row['uid']) . self::deleteClause('sys_file_reference') . self::versioningPlaceholderClause('sys_file_reference'));
			foreach ($referenceUids as $referenceUid) {
				$fileReferenceObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileReferenceObject($referenceUid['uid']);
				$fileObject = $fileReferenceObject->getOriginalFile();
				// Web image
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileReferenceObject->getExtension())) {
					$imageUrl = $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array(
						'width' => $sizeParts[0],
						'height' => $sizeParts[1]
					))->getPublicUrl(TRUE);
					$imgTag = '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($fileReferenceObject->getName()) . '" />';
				} else {
					// Icon
					$imgTag = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile(strtolower($fileObject->getExtension()), array('title' => $fileObject->getName()));
				}
				if ($linkInfoPopup) {
					$onClick = 'top.launchView(\'_FILE\',\'' . $fileObject->getUid() . '\',\'' . $backPath . '\'); return false;';
					$thumbData .= '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $imgTag . '</a> ';
				} else {
					$thumbData .= $imgTag;
				}
			}
		} else {
			// Find uploaddir automatically
			if (is_null($uploaddir)) {
				$uploaddir = $GLOBALS['TCA'][$table]['columns'][$field]['config']['uploadfolder'];
			}
			$uploaddir = rtrim($uploaddir, '/');
			// Traverse files:
			$thumbs = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $row[$field], TRUE);
			$thumbData = '';
			foreach ($thumbs as $theFile) {
				if ($theFile) {
					$fileName = trim($uploaddir . '/' . $theFile, '/');
					$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($fileName);
					$fileExtension = $fileObject->getExtension();
					if ($fileExtension == 'ttf' || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
						$imageUrl = $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array(
							'width' => $sizeParts[0],
							'height' => $sizeParts[1]
						))->getPublicUrl(TRUE);
						if (!$fileObject->checkActionPermission('read')) {
							/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
							$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_missing_text') . ' <abbr title="' . htmlspecialchars($fileObject->getName()) . '">' . htmlspecialchars($fileObject->getName()) . '</abbr>', $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_missing'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
							$thumbData .= $flashMessage->render();
							continue;
						}
						$image = '<img src="' . htmlspecialchars($imageUrl) . '" hspace="2" border="0" title="' . htmlspecialchars($fileObject->getName()) . '"' . $tparams . ' alt="" />';
						if ($linkInfoPopup) {
							$onClick = 'top.launchView(\'_FILE\', \'' . $fileName . '\',\'\',\'' . $backPath . '\');return false;';
							$thumbData .= '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $image . '</a> ';
						} else {
							$thumbData .= $image;
						}
					} else {
						// Gets the icon
						$fileIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($fileExtension, array('title' => $fileObject->getName()));
						if ($linkInfoPopup) {
							$onClick = 'top.launchView(\'_FILE\', \'' . $fileName . '\',\'\',\'' . $backPath . '\'); return false;';
							$thumbData .= '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $fileIcon . '</a> ';
						} else {
							$thumbData .= $fileIcon;
						}
					}
				}
			}
		}
		return $thumbData;
	}

	/**
	 * Returns single image tag to thumbnail using a thumbnail script (like thumbs.php)
	 *
	 * @param string $thumbScript Must point to "thumbs.php" relative to the script position
	 * @param string $theFile Must be the proper reference to the file that thumbs.php should show
	 * @param string $tparams The additional attributes for the image tag
	 * @param integer $size The size of the thumbnail send along to "thumbs.php
	 * @return string Image tag
	 */
	static public function getThumbNail($thumbScript, $theFile, $tparams = '', $size = '') {
		$check = basename($theFile) . ':' . filemtime($theFile) . ':' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
		$params = '&file=' . rawurlencode($theFile);
		$params .= trim($size) ? '&size=' . trim($size) : '';
		$params .= '&md5sum=' . md5($check);
		$url = $thumbScript . '?' . $params;
		$th = '<img src="' . htmlspecialchars($url) . '" title="' . trim(basename($theFile)) . '"' . ($tparams ? ' ' . $tparams : '') . ' alt="" />';
		return $th;
	}

	/**
	 * Returns title-attribute information for a page-record informing about id, alias, doktype, hidden, starttime, endtime, fe_group etc.
	 *
	 * @param array $row Input must be a page row ($row) with the proper fields set (be sure - send the full range of fields for the table)
	 * @param string $perms_clause This is used to get the record path of the shortcut page, if any (and doktype==4)
	 * @param boolean $includeAttrib If $includeAttrib is set, then the 'title=""' attribute is wrapped about the return value, which is in any case htmlspecialchar()'ed already
	 * @return string
	 */
	static public function titleAttribForPages($row, $perms_clause = '', $includeAttrib = 1) {
		$parts = array();
		$parts[] = 'id=' . $row['uid'];
		if ($row['alias']) {
			$parts[] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['alias']['label']) . ' ' . $row['alias'];
		}
		if ($row['pid'] < 0) {
			$parts[] = 'v#1.' . $row['t3ver_id'];
		}
		switch ($row['t3ver_state']) {
		case 1:
			$parts[] = 'PLH WSID#' . $row['t3ver_wsid'];
			break;
		case 2:
			$parts[] = 'Deleted element!';
			break;
		case 3:
			$parts[] = 'NEW LOCATION (PLH) WSID#' . $row['t3ver_wsid'];
			break;
		case 4:
			$parts[] = 'OLD LOCATION (PNT) WSID#' . $row['t3ver_wsid'];
			break;
		case -1:
			$parts[] = 'New element!';
			break;
		}
		if ($row['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK) {
			$parts[] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['url']['label']) . ' ' . $row['url'];
		} elseif ($row['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT) {
			if ($perms_clause) {
				$label = self::getRecordPath(intval($row['shortcut']), $perms_clause, 20);
			} else {
				$row['shortcut'] = intval($row['shortcut']);
				$lRec = self::getRecordWSOL('pages', $row['shortcut'], 'title');
				$label = $lRec['title'] . ' (id=' . $row['shortcut'] . ')';
			}
			if ($row['shortcut_mode'] != \TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_NONE) {
				$label .= ', ' . $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['shortcut_mode']['label']) . ' ' . $GLOBALS['LANG']->sL(self::getLabelFromItemlist('pages', 'shortcut_mode', $row['shortcut_mode']));
			}
			$parts[] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['shortcut']['label']) . ' ' . $label;
		} elseif ($row['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT) {
			if ($perms_clause) {
				$label = self::getRecordPath(intval($row['mount_pid']), $perms_clause, 20);
			} else {
				$lRec = self::getRecordWSOL('pages', intval($row['mount_pid']), 'title');
				$label = $lRec['title'];
			}
			$parts[] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['mount_pid']['label']) . ' ' . $label;
			if ($row['mount_pid_ol']) {
				$parts[] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['mount_pid_ol']['label']);
			}
		}
		if ($row['nav_hide']) {
			$parts[] = rtrim($GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['nav_hide']['label']), ':');
		}
		if ($row['hidden']) {
			$parts[] = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.hidden');
		}
		if ($row['starttime']) {
			$parts[] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['starttime']['label']) . ' ' . self::dateTimeAge($row['starttime'], -1, 'date');
		}
		if ($row['endtime']) {
			$parts[] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['endtime']['label']) . ' ' . self::dateTimeAge($row['endtime'], -1, 'date');
		}
		if ($row['fe_group']) {
			$fe_groups = array();
			foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $row['fe_group']) as $fe_group) {
				if ($fe_group < 0) {
					$fe_groups[] = $GLOBALS['LANG']->sL(self::getLabelFromItemlist('pages', 'fe_group', $fe_group));
				} else {
					$lRec = self::getRecordWSOL('fe_groups', $fe_group, 'title');
					$fe_groups[] = $lRec['title'];
				}
			}
			$label = implode(', ', $fe_groups);
			$parts[] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns']['fe_group']['label']) . ' ' . $label;
		}
		$out = htmlspecialchars(implode(' - ', $parts));
		return $includeAttrib ? 'title="' . $out . '"' : $out;
	}

	/**
	 * Returns title-attribute information for ANY record (from a table defined in TCA of course)
	 * The included information depends on features of the table, but if hidden, starttime, endtime and fe_group fields are configured for, information about the record status in regard to these features are is included.
	 * "pages" table can be used as well and will return the result of ->titleAttribForPages() for that page.
	 *
	 * @param array $row Table row; $row is a row from the table, $table
	 * @param string $table Table name
	 * @return 	string
	 */
	static public function getRecordIconAltText($row, $table = 'pages') {
		if ($table == 'pages') {
			$out = self::titleAttribForPages($row, '', 0);
		} else {
			$ctrl = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'];
			// Uid is added
			$out = 'id=' . $row['uid'];
			if ($table == 'pages' && $row['alias']) {
				$out .= ' / ' . $row['alias'];
			}
			if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS'] && $row['pid'] < 0) {
				$out .= ' - v#1.' . $row['t3ver_id'];
			}
			if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
				switch ($row['t3ver_state']) {
				case 1:
					$out .= ' - PLH WSID#' . $row['t3ver_wsid'];
					break;
				case 2:
					$out .= ' - Deleted element!';
					break;
				case 3:
					$out .= ' - NEW LOCATION (PLH) WSID#' . $row['t3ver_wsid'];
					break;
				case 4:
					$out .= ' - OLD LOCATION (PNT)  WSID#' . $row['t3ver_wsid'];
					break;
				case -1:
					$out .= ' - New element!';
					break;
				}
			}
			// Hidden
			if ($ctrl['disabled']) {
				$out .= $row[$ctrl['disabled']] ? ' - ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.hidden') : '';
			}
			if ($ctrl['starttime']) {
				if ($row[$ctrl['starttime']] > $GLOBALS['EXEC_TIME']) {
					$out .= ' - ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.starttime') . ':' . self::date($row[$ctrl['starttime']]) . ' (' . self::daysUntil($row[$ctrl['starttime']]) . ' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.days') . ')';
				}
			}
			if ($row[$ctrl['endtime']]) {
				$out .= ' - ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.endtime') . ': ' . self::date($row[$ctrl['endtime']]) . ' (' . self::daysUntil($row[$ctrl['endtime']]) . ' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.days') . ')';
			}
		}
		return htmlspecialchars($out);
	}

	/**
	 * Returns the label of the first found entry in an "items" array from $GLOBALS['TCA'] (tablename = $table/fieldname = $col) where the value is $key
	 *
	 * @param string $table Table name, present in $GLOBALS['TCA']
	 * @param string $col Field name, present in $GLOBALS['TCA']
	 * @param string $key items-array value to match
	 * @return string Label for item entry
	 */
	static public function getLabelFromItemlist($table, $col, $key) {
		// Check, if there is an "items" array:
		if (is_array($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]['columns'][$col]) && is_array($GLOBALS['TCA'][$table]['columns'][$col]['config']['items'])) {
			// Traverse the items-array...
			foreach ($GLOBALS['TCA'][$table]['columns'][$col]['config']['items'] as $k => $v) {
				// ... and return the first found label where the value was equal to $key
				if (!strcmp($v[1], $key)) {
					return $v[0];
				}
			}
		}
	}

	/**
	 * Return the label of a field by additionally checking TsConfig values
	 *
	 * @param integer $pageId Page id
	 * @param string $table Table name
	 * @param string $column Field Name
	 * @param string $key item value
	 * @return string Label for item entry
	 */
	static public function getLabelFromItemListMerged($pageId, $table, $column, $key) {
		$pageTsConfig = self::getPagesTSconfig($pageId);
		$label = '';
		if (is_array($pageTsConfig['TCEFORM.']) && is_array($pageTsConfig['TCEFORM.'][$table . '.']) && is_array($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.'])) {
			if (is_array($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['addItems.']) && isset($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['addItems.'][$key])) {
				$label = $pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['addItems.'][$key];
			} elseif (is_array($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['altLabels.']) && isset($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['altLabels.'][$key])) {
				$label = $pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['altLabels.'][$key];
			}
		}
		if (empty($label)) {
			$tcaValue = self::getLabelFromItemlist($table, $column, $key);
			if (!empty($tcaValue)) {
				$label = $tcaValue;
			}
		}
		return $label;
	}

	/**
	 * Splits the given key with commas and returns the list of all the localized items labels, separated by a comma.
	 * NOTE: this does not take itemsProcFunc into account
	 *
	 * @param string $table Table name, present in TCA
	 * @param string $column Field name
	 * @param string $key Key or comma-separated list of keys.
	 * @return string Comma-separated list of localized labels
	 */
	static public function getLabelsFromItemsList($table, $column, $key) {
		$labels = array();
		$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $key, TRUE);
		if (count($values) > 0) {
			// Check if there is an "items" array
			if (is_array($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]['columns'][$column]) && is_array($GLOBALS['TCA'][$table]['columns'][$column]['config']['items'])) {
				// Loop on all selected values
				foreach ($values as $aValue) {
					foreach ($GLOBALS['TCA'][$table]['columns'][$column]['config']['items'] as $itemConfiguration) {
						// Loop on all available items
						// Keep matches and move on to next value
						if ($aValue == $itemConfiguration[1]) {
							$labels[] = $GLOBALS['LANG']->sL($itemConfiguration[0]);
							break;
						}
					}
				}
			}
		}
		return implode(', ', $labels);
	}

	/**
	 * Returns the label-value for fieldname $col in table, $table
	 * If $printAllWrap is set (to a "wrap") then it's wrapped around the $col value IF THE COLUMN $col DID NOT EXIST in TCA!, eg. $printAllWrap = '<strong>|</strong>' and the fieldname was 'not_found_field' then the return value would be '<strong>not_found_field</strong>'
	 *
	 * @param string $table Table name, present in $GLOBALS['TCA']
	 * @param string $col Field name
	 * @param string $printAllWrap Wrap value - set function description
	 * @return string or NULL if $col is not found in the TCA table
	 */
	static public function getItemLabel($table, $col, $printAllWrap = '') {
		// Check if column exists
		if (is_array($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]['columns'][$col])) {
			return $GLOBALS['TCA'][$table]['columns'][$col]['label'];
		}

		return NULL;
	}

	/**
	 * Returns the "title"-value in record, $row, from table, $table
	 * The field(s) from which the value is taken is determined by the "ctrl"-entries 'label', 'label_alt' and 'label_alt_force'
	 *
	 * @param string $table Table name, present in TCA
	 * @param array $row Row from table
	 * @param boolean $prep If set, result is prepared for output: The output is cropped to a limited lenght (depending on BE_USER->uc['titleLen']) and if no value is found for the title, '<em>[No title]</em>' is returned (localized). Further, the output is htmlspecialchars()'ed
	 * @param boolean $forceResult If set, the function always returns an output. If no value is found for the title, '[No title]' is returned (localized).
	 * @return string
	 */
	static public function getRecordTitle($table, $row, $prep = FALSE, $forceResult = TRUE) {
		if (is_array($GLOBALS['TCA'][$table])) {
			// If configured, call userFunc
			if ($GLOBALS['TCA'][$table]['ctrl']['label_userFunc']) {
				$params['table'] = $table;
				$params['row'] = $row;
				$params['title'] = '';
				// Create NULL-reference
				$null = NULL;
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($GLOBALS['TCA'][$table]['ctrl']['label_userFunc'], $params, $null);
				$t = $params['title'];
			} else {
				// No userFunc: Build label
				$t = self::getProcessedValue($table, $GLOBALS['TCA'][$table]['ctrl']['label'], $row[$GLOBALS['TCA'][$table]['ctrl']['label']], 0, 0, FALSE, $row['uid'], $forceResult);
				if ($GLOBALS['TCA'][$table]['ctrl']['label_alt'] && ($GLOBALS['TCA'][$table]['ctrl']['label_alt_force'] || !strcmp($t, ''))) {
					$altFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], 1);
					$tA = array();
					if (!empty($t)) {
						$tA[] = $t;
					}
					foreach ($altFields as $fN) {
						$t = trim(strip_tags($row[$fN]));
						if (strcmp($t, '')) {
							$t = self::getProcessedValue($table, $fN, $t, 0, 0, FALSE, $row['uid']);
							if (!$GLOBALS['TCA'][$table]['ctrl']['label_alt_force']) {
								break;
							}
							$tA[] = $t;
						}
					}
					if ($GLOBALS['TCA'][$table]['ctrl']['label_alt_force']) {
						$t = implode(', ', $tA);
					}
				}
			}
			// If the current result is empty, set it to '[No title]' (localized) and prepare for output if requested
			if ($prep || $forceResult) {
				if ($prep) {
					$t = self::getRecordTitlePrep($t);
				}
				if (!strcmp(trim($t), '')) {
					$t = self::getNoRecordTitle($prep);
				}
			}
			return $t;
		}
	}

	/**
	 * Crops a title string to a limited lenght and if it really was cropped, wrap it in a <span title="...">|</span>,
	 * which offers a tooltip with the original title when moving mouse over it.
	 *
	 * @param string $title The title string to be cropped
	 * @param integer $titleLength Crop title after this length - if not set, BE_USER->uc['titleLen'] is used
	 * @return string The processed title string, wrapped in <span title="...">|</span> if cropped
	 */
	static public function getRecordTitlePrep($title, $titleLength = 0) {
		// If $titleLength is not a valid positive integer, use BE_USER->uc['titleLen']:
		if (!$titleLength || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($titleLength) || $titleLength < 0) {
			$titleLength = $GLOBALS['BE_USER']->uc['titleLen'];
		}
		$titleOrig = htmlspecialchars($title);
		$title = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, $titleLength));
		// If title was cropped, offer a tooltip:
		if ($titleOrig != $title) {
			$title = '<span title="' . $titleOrig . '">' . $title . '</span>';
		}
		return $title;
	}

	/**
	 * Get a localized [No title] string, wrapped in <em>|</em> if $prep is TRUE.
	 *
	 * @param boolean $prep Wrap result in <em>|</em>
	 * @return string Localized [No title] string
	 */
	static public function getNoRecordTitle($prep = FALSE) {
		$noTitle = '[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', 1) . ']';
		if ($prep) {
			$noTitle = '<em>' . $noTitle . '</em>';
		}
		return $noTitle;
	}

	/**
	 * Returns a human readable output of a value from a record
	 * For instance a database record relation would be looked up to display the title-value of that record. A checkbox with a "1" value would be "Yes", etc.
	 * $table/$col is tablename and fieldname
	 * REMEMBER to pass the output through htmlspecialchars() if you output it to the browser! (To protect it from XSS attacks and be XHTML compliant)
	 *
	 * @param string $table Table name, present in TCA
	 * @param string $col Field name, present in TCA
	 * @param string $value The value of that field from a selected record
	 * @param integer $fixed_lgd_chars The max amount of characters the value may occupy
	 * @param boolean $defaultPassthrough Flag means that values for columns that has no conversion will just be pass through directly (otherwise cropped to 200 chars or returned as "N/A")
	 * @param boolean $noRecordLookup If set, no records will be looked up, UIDs are just shown.
	 * @param integer $uid Uid of the current record
	 * @param boolean $forceResult If \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle is used to process the value, this parameter is forwarded.
	 * @return string
	 */
	static public function getProcessedValue($table, $col, $value, $fixed_lgd_chars = 0, $defaultPassthrough = 0, $noRecordLookup = FALSE, $uid = 0, $forceResult = TRUE) {
		if ($col == 'uid') {
			// No need to load TCA as uid is not in TCA-array
			return $value;
		}
		// Check if table and field is configured:
		if (is_array($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]['columns'][$col])) {
			// Depending on the fields configuration, make a meaningful output value.
			$theColConf = $GLOBALS['TCA'][$table]['columns'][$col]['config'];
			/*****************
			 *HOOK: pre-processing the human readable output from a record
			 ****************/
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['preProcessValue'])) {
				// Create NULL-reference
				$null = NULL;
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['preProcessValue'] as $_funcRef) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $theColConf, $null);
				}
			}
			$l = '';
			switch ((string) $theColConf['type']) {
			case 'radio':
				$l = self::getLabelFromItemlist($table, $col, $value);
				$l = $GLOBALS['LANG']->sL($l);
				break;
			case 'select':
				if ($theColConf['MM']) {
					if ($uid) {
						// Display the title of MM related records in lists
						if ($noRecordLookup) {
							$MMfield = $theColConf['foreign_table'] . '.uid';
						} else {
							$MMfields = array($theColConf['foreign_table'] . '.' . $GLOBALS['TCA'][$theColConf['foreign_table']]['ctrl']['label']);
							foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$theColConf['foreign_table']]['ctrl']['label_alt'], 1) as $f) {
								$MMfields[] = $theColConf['foreign_table'] . '.' . $f;
							}
							$MMfield = join(',', $MMfields);
						}
						/** @var $dbGroup \TYPO3\CMS\Core\Database\RelationHandler */
						$dbGroup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
						$dbGroup->start($value, $theColConf['foreign_table'], $theColConf['MM'], $uid, $table, $theColConf);
						$selectUids = $dbGroup->tableArray[$theColConf['foreign_table']];
						if (is_array($selectUids) && count($selectUids) > 0) {
							$MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, ' . $MMfield, $theColConf['foreign_table'], 'uid IN (' . implode(',', $selectUids) . ')' . self::deleteClause($theColConf['foreign_table']));
							while ($MMrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($MMres)) {
								$mmlA[] = $noRecordLookup ? $MMrow['uid'] : self::getRecordTitle($theColConf['foreign_table'], $MMrow, FALSE, $forceResult);
							}
							$GLOBALS['TYPO3_DB']->sql_free_result($MMres);
							if (is_array($mmlA)) {
								$l = implode('; ', $mmlA);
							} else {
								$l = 'N/A';
							}
						} else {
							$l = 'N/A';
						}
					} else {
						$l = 'N/A';
					}
				} else {
					$l = self::getLabelsFromItemsList($table, $col, $value);
					if ($theColConf['foreign_table'] && !$l && $GLOBALS['TCA'][$theColConf['foreign_table']]) {
						if ($noRecordLookup) {
							$l = $value;
						} else {
							$rParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, 1);
							$lA = array();
							foreach ($rParts as $rVal) {
								$rVal = intval($rVal);
								if ($rVal > 0) {
									$r = self::getRecordWSOL($theColConf['foreign_table'], $rVal);
								} else {
									$r = self::getRecordWSOL($theColConf['neg_foreign_table'], -$rVal);
								}
								if (is_array($r)) {
									$lA[] = $GLOBALS['LANG']->sL(($rVal > 0 ? $theColConf['foreign_table_prefix'] : $theColConf['neg_foreign_table_prefix'])) . self::getRecordTitle(($rVal > 0 ? $theColConf['foreign_table'] : $theColConf['neg_foreign_table']), $r, FALSE, $forceResult);
								} else {
									$lA[] = $rVal ? '[' . $rVal . '!]' : '';
								}
							}
							$l = implode(', ', $lA);
						}
					}
				}
				break;
			case 'group':
				// resolve the titles for DB records
				if ($theColConf['internal_type'] === 'db') {
					$finalValues = array();
					$relationTableName = $theColConf['allowed'];
					$explodedValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);

					foreach ($explodedValues as $explodedValue) {

						if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($explodedValue)) {
							$relationTableNameForField = $relationTableName;
						} else {
							list($relationTableNameForField, $explodedValue) = self::splitTable_Uid($explodedValue);
						}

						$relationRecord = static::getRecordWSOL($relationTableNameForField, $explodedValue);
						$finalValues[] = static::getRecordTitle($relationTableNameForField, $relationRecord);
					}

					$l = implode(', ', $finalValues);
				} else {
					$l = implode(', ', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE));
				}

				break;
			case 'check':
				if (!is_array($theColConf['items']) || count($theColConf['items']) == 1) {
					$l = $value ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:yes') : $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:no');
				} else {
					$lA = array();
					foreach ($theColConf['items'] as $key => $val) {
						if ($value & pow(2, $key)) {
							$lA[] = $GLOBALS['LANG']->sL($val[0]);
						}
					}
					$l = implode(', ', $lA);
				}
				break;
			case 'input':
				// Hide value 0 for dates, but show it for everything else
				if (isset($value)) {
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($theColConf['eval'], 'date')) {
						// Handle native date field
						if (isset($theColConf['dbType']) && $theColConf['dbType'] === 'date') {
							$dateTimeFormats = $GLOBALS['TYPO3_DB']->getDateTimeFormats($table);
							$emptyValue = $dateTimeFormats['date']['empty'];
							$value = $value !== $emptyValue ? strtotime($value) : 0;
						}
						if (!empty($value)) {
							$l = self::date($value) . ' (' . ($GLOBALS['EXEC_TIME'] - $value > 0 ? '-' : '') . self::calcAge(abs(($GLOBALS['EXEC_TIME'] - $value)), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')) . ')';
						}
					} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($theColConf['eval'], 'time')) {
						if (!empty($value)) {
							$l = self::time($value, FALSE);
						}
					} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($theColConf['eval'], 'timesec')) {
						if (!empty($value)) {
							$l = self::time($value);
						}
					} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($theColConf['eval'], 'datetime')) {
						// Handle native date/time field
						if (isset($theColConf['dbType']) && $theColConf['dbType'] === 'datetime') {
							$dateTimeFormats = $GLOBALS['TYPO3_DB']->getDateTimeFormats($table);
							$emptyValue = $dateTimeFormats['datetime']['empty'];
							$value = $value !== $emptyValue ? strtotime($value) : 0;
						}
						if (!empty($value)) {
							$l = self::datetime($value);
						}
					} else {
						$l = $value;
					}
				}
				break;
			case 'flex':
				$l = strip_tags($value);
				break;
			default:
				if ($defaultPassthrough) {
					$l = $value;
				} elseif ($theColConf['MM']) {
					$l = 'N/A';
				} elseif ($value) {
					$l = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(strip_tags($value), 200);
				}
				break;
			}
			// If this field is a password field, then hide the password by changing it to a random number of asterisk (*)
			if (stristr($theColConf['eval'], 'password')) {
				$l = '';
				$randomNumber = rand(5, 12);
				for ($i = 0; $i < $randomNumber; $i++) {
					$l .= '*';
				}
			}
			/*****************
			 *HOOK: post-processing the human readable output from a record
			 ****************/
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['postProcessValue'])) {
				// Create NULL-reference
				$null = NULL;
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['postProcessValue'] as $_funcRef) {
					$params = array(
						'value' => $l,
						'colConf' => $theColConf
					);
					$l = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $params, $null);
				}
			}
			if ($fixed_lgd_chars) {
				return \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($l, $fixed_lgd_chars);
			} else {
				return $l;
			}
		}
	}

	/**
	 * Same as ->getProcessedValue() but will go easy on fields like "tstamp" and "pid" which are not configured in TCA - they will be formatted by this function instead.
	 *
	 * @param string $table Table name, present in TCA
	 * @param string $fN Field name
	 * @param string $fV Field value
	 * @param integer $fixed_lgd_chars The max amount of characters the value may occupy
	 * @param integer $uid Uid of the current record
	 * @param boolean $forceResult If \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle is used to process the value, this parameter is forwarded.
	 * @return string
	 * @see getProcessedValue()
	 */
	static public function getProcessedValueExtra($table, $fN, $fV, $fixed_lgd_chars = 0, $uid = 0, $forceResult = TRUE) {
		$fVnew = self::getProcessedValue($table, $fN, $fV, $fixed_lgd_chars, 1, 0, $uid, $forceResult);
		if (!isset($fVnew)) {
			if (is_array($GLOBALS['TCA'][$table])) {
				if ($fN == $GLOBALS['TCA'][$table]['ctrl']['tstamp'] || $fN == $GLOBALS['TCA'][$table]['ctrl']['crdate']) {
					$fVnew = self::datetime($fV);
				} elseif ($fN == 'pid') {
					// Fetches the path with no regard to the users permissions to select pages.
					$fVnew = self::getRecordPath($fV, '1=1', 20);
				} else {
					$fVnew = $fV;
				}
			}
		}
		return $fVnew;
	}

	/**
	 * Returns file icon name (from $FILEICONS) for the fileextension $ext
	 *
	 * @param string $ext File extension, lowercase
	 * @return string File icon filename
	 */
	static public function getFileIcon($ext) {
		return $GLOBALS['FILEICONS'][$ext] ? $GLOBALS['FILEICONS'][$ext] : $GLOBALS['FILEICONS']['default'];
	}

	/**
	 * Returns fields for a table, $table, which would typically be interesting to select
	 * This includes uid, the fields defined for title, icon-field.
	 * Returned as a list ready for query ($prefix can be set to eg. "pages." if you are selecting from the pages table and want the table name prefixed)
	 *
	 * @param string $table Table name, present in $GLOBALS['TCA']
	 * @param string $prefix Table prefix
	 * @param array $fields Preset fields (must include prefix if that is used)
	 * @return string List of fields.
	 */
	static public function getCommonSelectFields($table, $prefix = '', $fields = array()) {
		$fields[] = $prefix . 'uid';
		if (isset($GLOBALS['TCA'][$table]['ctrl']['label']) && $GLOBALS['TCA'][$table]['ctrl']['label'] != '') {
			$fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['label'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
			$secondFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], 1);
			foreach ($secondFields as $fieldN) {
				$fields[] = $prefix . $fieldN;
			}
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			$fields[] = $prefix . 't3ver_id';
			$fields[] = $prefix . 't3ver_state';
			$fields[] = $prefix . 't3ver_wsid';
			$fields[] = $prefix . 't3ver_count';
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['selicon_field']) {
			$fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['selicon_field'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['typeicon_column']) {
			$fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
		}
		if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
			if ($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']) {
				$fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
			}
			if ($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime']) {
				$fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'];
			}
			if ($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime']) {
				$fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime'];
			}
			if ($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group']) {
				$fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group'];
			}
		}
		return implode(',', array_unique($fields));
	}

	/**
	 * Makes a form for configuration of some values based on configuration found in the array $configArray,
	 * with default values from $defaults and a data-prefix $dataPrefix
	 * <form>-tags must be supplied separately
	 * Needs more documentation and examples, in particular syntax for configuration array. See Inside TYPO3.
	 * That's were you can expect to find example, if anywhere.
	 *
	 * @param array $configArray Field configuration code.
	 * @param array $defaults Defaults
	 * @param string $dataPrefix Prefix for formfields
	 * @return string HTML for a form.
	 */
	static public function makeConfigForm($configArray, $defaults, $dataPrefix) {
		$params = $defaults;
		if (is_array($configArray)) {
			$lines = array();
			foreach ($configArray as $fname => $config) {
				if (is_array($config)) {
					$lines[$fname] = '<strong>' . htmlspecialchars($config[1]) . '</strong><br />';
					$lines[$fname] .= $config[2] . '<br />';
					switch ($config[0]) {
					case 'string':

					case 'short':
						$formEl = '<input type="text" name="' . $dataPrefix . '[' . $fname . ']" value="' . $params[$fname] . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(($config[0] == 'short' ? 24 : 48)) . ' />';
						break;
					case 'check':
						$formEl = '<input type="hidden" name="' . $dataPrefix . '[' . $fname . ']" value="0" /><input type="checkbox" name="' . $dataPrefix . '[' . $fname . ']" value="1"' . ($params[$fname] ? ' checked="checked"' : '') . ' />';
						break;
					case 'comment':
						$formEl = '';
						break;
					case 'select':
						$opt = array();
						foreach ($config[3] as $k => $v) {
							$opt[] = '<option value="' . htmlspecialchars($k) . '"' . ($params[$fname] == $k ? ' selected="selected"' : '') . '>' . htmlspecialchars($v) . '</option>';
						}
						$formEl = '<select name="' . $dataPrefix . '[' . $fname . ']">' . implode('', $opt) . '</select>';
						break;
					default:
						debug($config);
						break;
					}
					$lines[$fname] .= $formEl;
					$lines[$fname] .= '<br /><br />';
				} else {
					$lines[$fname] = '<hr />';
					if ($config) {
						$lines[$fname] .= '<strong>' . strtoupper(htmlspecialchars($config)) . '</strong><br />';
					}
					if ($config) {
						$lines[$fname] .= '<br />';
					}
				}
			}
		}
		$out = implode('', $lines);
		$out .= '<input type="submit" name="submit" value="Update configuration" />';
		return $out;
	}

	/*******************************************
	 *
	 * Backend Modules API functions
	 *
	 *******************************************/
	/**
	 * Returns help-text icon if configured for.
	 * TCA_DESCR must be loaded prior to this function and $GLOBALS['BE_USER'] must
	 * have 'edit_showFieldHelp' set to 'icon', otherwise nothing is returned
	 *
	 * Please note: since TYPO3 4.5 the UX team decided to not use CSH in its former way,
	 * but to wrap the given text (where before the help icon was, and you could hover over it)
	 * Please also note that since TYPO3 4.5 the option to enable help (none, icon only, full text)
	 * was completely removed.
	 *
	 * @param string $table Table name
	 * @param string $field Field name
	 * @param string $BACK_PATH Back path
	 * @param boolean $force Force display of icon no matter BE_USER setting for help
	 * @return string HTML content for a help icon/text
	 */
	static public function helpTextIcon($table, $field, $BACK_PATH, $force = 0) {
		if (is_array($GLOBALS['TCA_DESCR'][$table]) && is_array($GLOBALS['TCA_DESCR'][$table]['columns'][$field]) && (isset($GLOBALS['BE_USER']->uc['edit_showFieldHelp']) || $force)) {
			return self::wrapInHelp($table, $field);
		}
	}

	/**
	 * Returns CSH help text (description), if configured for, as an array (title, description)
	 *
	 * @param string $table Table name
	 * @param string $field Field name
	 * @return array With keys 'description' (raw, as available in locallang), 'title' (optional), 'moreInfo'
	 */
	static public function helpTextArray($table, $field) {
		if (!isset($GLOBALS['TCA_DESCR'][$table]['columns'])) {
			$GLOBALS['LANG']->loadSingleTableDescription($table);
		}
		$output = array(
			'description' => NULL,
			'title' => NULL,
			'moreInfo' => FALSE
		);
		if (is_array($GLOBALS['TCA_DESCR'][$table]) && is_array($GLOBALS['TCA_DESCR'][$table]['columns'][$field])) {
			$data = $GLOBALS['TCA_DESCR'][$table]['columns'][$field];
			// Add alternative title, if defined
			if ($data['alttitle']) {
				$output['title'] = $data['alttitle'];
			}
			// If we have more information to show
			if ($data['image_descr'] || $data['seeAlso'] || $data['details'] || $data['syntax']) {
				$output['moreInfo'] = TRUE;
			}
			// Add description
			if ($data['description']) {
				$output['description'] = $data['description'];
			}
		}
		return $output;
	}

	/**
	 * Returns CSH help text
	 *
	 * @param string $table Table name
	 * @param string $field Field name
	 * @return string HTML content for help text
	 * @see wrapInHelp()
	 */
	static public function helpText($table, $field) {
		$helpTextArray = self::helpTextArray($table, $field);
		$output = '';
		$arrow = '';
		// Put header before the rest of the text
		if ($helpTextArray['title'] !== NULL) {
			$output .= '<h2 class="t3-row-header">' . $helpTextArray['title'] . '</h2>';
		}
		// Add see also arrow if we have more info
		if ($helpTextArray['moreInfo']) {
			$arrow = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-forward');
		}
		// Wrap description and arrow in p tag
		if ($helpTextArray['description'] !== NULL || $arrow) {
			$output .= '<p class="t3-help-short">' . nl2br(htmlspecialchars($helpTextArray['description'])) . $arrow . '</p>';
		}
		return $output;
	}

	/**
	 * API function that wraps the text / html in help text, so if a user hovers over it
	 * the help text will show up
	 * This is the new help API function since TYPO3 4.5, and uses the new behaviour
	 * (hover over text, no icon, no fulltext option, no option to disable the help)
	 *
	 * @param string $table The table name for which the help should be shown
	 * @param string $field The field name for which the help should be shown
	 * @param string $text The text which should be wrapped with the help text
	 * @param array $overloadHelpText Array with text to overload help text
	 * @return string the HTML code ready to render
	 */
	static public function wrapInHelp($table, $field, $text = '', array $overloadHelpText = array()) {
		// Initialize some variables
		$helpText = '';
		$abbrClassAdd = '';
		$wrappedText = $text;
		$hasHelpTextOverload = count($overloadHelpText) > 0;
		// Get the help text that should be shown on hover
		if (!$hasHelpTextOverload) {
			$helpText = self::helpText($table, $field);
		}
		// If there's a help text or some overload information, proceed with preparing an output
		if (!empty($helpText) || $hasHelpTextOverload) {
			// If no text was given, just use the regular help icon
			if ($text == '') {
				$text = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-help-open');
				$abbrClassAdd = '-icon';
			}
			$text = '<abbr class="t3-help-teaser' . $abbrClassAdd . '">' . $text . '</abbr>';
			$wrappedText = '<span class="t3-help-link" href="#" data-table="' . $table . '" data-field="' . $field . '"';
			// The overload array may provide a title and a description
			// If either one is defined, add them to the "data" attributes
			if ($hasHelpTextOverload) {
				if (isset($overloadHelpText['title'])) {
					$wrappedText .= ' data-title="' . htmlspecialchars($overloadHelpText['title']) . '"';
				}
				if (isset($overloadHelpText['description'])) {
					$wrappedText .= ' data-description="' . htmlspecialchars($overloadHelpText['description']) . '"';
				}
			}
			$wrappedText .= '>' . $text . '</span>';
		}
		return $wrappedText;
	}

	/**
	 * API for getting CSH icons/text for use in backend modules.
	 * TCA_DESCR will be loaded if it isn't already
	 *
	 * @param string $table Table name ('_MOD_'+module name)
	 * @param string $field Field name (CSH locallang main key)
	 * @param string $BACK_PATH Back path
	 * @param string $wrap Wrap code for icon-mode, splitted by "|". Not used for full-text mode.
	 * @param boolean $onlyIconMode If set, the full text will never be shown (only icon). Useful for places where it will break the page if the table with full text is shown.
	 * @param string $styleAttrib Additional style-attribute content for wrapping table (full text mode only)
	 * @return string HTML content for help text
	 * @see helpTextIcon()
	 */
	static public function cshItem($table, $field, $BACK_PATH, $wrap = '', $onlyIconMode = FALSE, $styleAttrib = '') {
		if (!$GLOBALS['BE_USER']->uc['edit_showFieldHelp']) {
			return '';
		}
		$GLOBALS['LANG']->loadSingleTableDescription($table);
		if (is_array($GLOBALS['TCA_DESCR'][$table])) {
			// Creating CSH icon and short description:
			$output = self::helpTextIcon($table, $field, $BACK_PATH);
			if ($output && $wrap) {
				$wrParts = explode('|', $wrap);
				$output = $wrParts[0] . $output . $wrParts[1];
			}
			return $output;
		}
	}

	/**
	 * Returns a JavaScript string (for an onClick handler) which will load the alt_doc.php script that shows the form for editing of the record(s) you have send as params.
	 * REMEMBER to always htmlspecialchar() content in href-properties to ampersands get converted to entities (XHTML requirement and XSS precaution)
	 *
	 * @param string $params Parameters sent along to alt_doc.php. This requires a much more details description which you must seek in Inside TYPO3s documentation of the alt_doc.php API. And example could be '&edit[pages][123] = edit' which will show edit form for page record 123.
	 * @param string $backPath Must point back to the TYPO3_mainDir directory (where alt_doc.php is)
	 * @param string $requestUri An optional returnUrl you can set - automatically set to REQUEST_URI.
	 * @return string
	 * @see template::issueCommand()
	 */
	static public function editOnClick($params, $backPath = '', $requestUri = '') {
		$retUrl = 'returnUrl=' . ($requestUri == -1 ? '\'+T3_THIS_LOCATION+\'' : rawurlencode(($requestUri ? $requestUri : \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'))));
		return 'window.location.href=\'' . $backPath . 'alt_doc.php?' . $retUrl . $params . '\'; return false;';
	}

	/**
	 * Returns a JavaScript string for viewing the page id, $id
	 * It will detect the correct domain name if needed and provide the link with the right back path.
	 * Also it will re-use any window already open.
	 *
	 * @param integer $pageUid Page id
	 * @param string $backPath Must point back to TYPO3_mainDir (where the site is assumed to be one level above)
	 * @param array $rootLine If root line is supplied the function will look for the first found domain record and use that URL instead (if found)
	 * @param string $anchorSection Optional anchor to the URL
	 * @param string $alternativeUrl An alternative URL which - if set - will make all other parameters ignored: The function will just return the window.open command wrapped around this URL!
	 * @param string $additionalGetVars Additional GET variables.
	 * @param boolean $switchFocus If TRUE, then the preview window will gain the focus.
	 * @return string
	 */
	static public function viewOnClick($pageUid, $backPath = '', $rootLine = '', $anchorSection = '', $alternativeUrl = '', $additionalGetVars = '', $switchFocus = TRUE) {
		$viewScript = '/index.php?id=';
		if ($alternativeUrl) {
			$viewScript = $alternativeUrl;
		}
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass'] as $funcRef) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($funcRef);
				if (method_exists($hookObj, 'preProcess')) {
					$hookObj->preProcess($pageUid, $backPath, $rootLine, $anchorSection, $viewScript, $additionalGetVars, $switchFocus);
				}
			}
		}
		// Look if a fixed preview language should be added:
		$viewLanguageOrder = $GLOBALS['BE_USER']->getTSConfigVal('options.view.languageOrder');
		if (strlen($viewLanguageOrder)) {
			$suffix = '';
			// Find allowed languages (if none, all are allowed!)
			if (!$GLOBALS['BE_USER']->user['admin'] && strlen($GLOBALS['BE_USER']->groupData['allowed_languages'])) {
				$allowedLanguages = array_flip(explode(',', $GLOBALS['BE_USER']->groupData['allowed_languages']));
			}
			// Traverse the view order, match first occurence:
			$languageOrder = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $viewLanguageOrder);
			foreach ($languageOrder as $langUid) {
				if (is_array($allowedLanguages) && count($allowedLanguages)) {
					// Choose if set.
					if (isset($allowedLanguages[$langUid])) {
						$suffix = '&L=' . $langUid;
						break;
					}
				} else {
					// All allowed since no lang. are listed.
					$suffix = '&L=' . $langUid;
					break;
				}
			}
			// Add it
			$additionalGetVars .= $suffix;
		}
		// Check a mount point needs to be previewed
		$sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$sys_page->init(FALSE);
		$mountPointInfo = $sys_page->getMountPointInfo($pageUid);
		if ($mountPointInfo && $mountPointInfo['overlay']) {
			$pageUid = $mountPointInfo['mount_pid'];
			$additionalGetVars .= '&MP=' . $mountPointInfo['MPvar'];
		}
		$viewDomain = self::getViewDomain($pageUid, $rootLine);
		$previewUrl = $viewDomain . $viewScript . $pageUid . $additionalGetVars . $anchorSection;
		$onclickCode = 'var previewWin = window.open(\'' . $previewUrl . '\',\'newTYPO3frontendWindow\');' . ($switchFocus ? 'previewWin.focus();' : '');
		return $onclickCode;
	}

	/**
	 * Builds the frontend view domain for a given page ID with a given root
	 * line.
	 *
	 * @param integer $pageId The page ID to use, must be > 0
	 * @param array $rootLine The root line structure to use
	 * @return string The full domain including the protocol http:// or https://, but without the trailing '/'
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	static public function getViewDomain($pageId, $rootLine = NULL) {
		$domain = rtrim(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), '/');
		if (!is_array($rootLine)) {
			$rootLine = self::BEgetRootLine($pageId);
		}
		// Checks alternate domains
		if (count($rootLine) > 0) {
			$urlParts = parse_url($domain);
			/** @var \TYPO3\CMS\Frontend\Page\PageRepository $sysPage */
			$sysPage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			$page = (array) $sysPage->getPage($pageId);
			$protocol = 'http';
			if ($page['url_scheme'] == \TYPO3\CMS\Core\Utility\HttpUtility::SCHEME_HTTPS || $page['url_scheme'] == 0 && \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
				$protocol = 'https';
			}
			$domainName = self::firstDomainRecord($rootLine);
			if ($domainName) {
				$domain = $domainName;
			} else {
				$domainRecord = self::getDomainStartPage($urlParts['host'], $urlParts['path']);
				$domain = $domainRecord['domainName'];
			}
			if ($domain) {
				$domain = $protocol . '://' . $domain;
			} else {
				$domain = rtrim(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), '/');
			}
			// Append port number if lockSSLPort is not the standard port 443
			$portNumber = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort']);
			if ($portNumber > 0 && $portNumber !== 443 && $portNumber < 65536 && $protocol === 'https') {
				$domain .= ':' . strval($portNumber);
			}
		}
		return $domain;
	}

	/**
	 * Returns the merged User/Page TSconfig for page id, $id.
	 * Please read details about module programming elsewhere!
	 *
	 * @param integer $id Page uid
	 * @param string $TSref An object string which determines the path of the TSconfig to return.
	 * @return array
	 */
	static public function getModTSconfig($id, $TSref) {
		$pageTS_modOptions = $GLOBALS['BE_USER']->getTSConfig($TSref, self::getPagesTSconfig($id));
		$BE_USER_modOptions = $GLOBALS['BE_USER']->getTSConfig($TSref);
		$modTSconfig = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($pageTS_modOptions, $BE_USER_modOptions);
		return $modTSconfig;
	}

	/**
	 * Returns a selector box "function menu" for a module
	 * Requires the JS function jumpToUrl() to be available
	 * See Inside TYPO3 for details about how to use / make Function menus
	 *
	 * @param mixed $id The "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
	 * @param string $elementName The form elements name, probably something like "SET[...]
	 * @param string $currentValue The value to be selected currently.
	 * @param array	 $menuItems An array with the menu items for the selector box
	 * @param string $script The script to send the &id to, if empty it's automatically found
	 * @param string $addParams Additional parameters to pass to the script.
	 * @return string HTML code for selector box
	 */
	static public function getFuncMenu($mainParams, $elementName, $currentValue, $menuItems, $script = '', $addparams = '') {
		if (is_array($menuItems)) {
			if (!is_array($mainParams)) {
				$mainParams = array('id' => $mainParams);
			}
			$mainParams = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $mainParams);
			if (!$script) {
				$script = basename(PATH_thisScript);
				$mainParams .= \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M') ? '&M=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M')) : '';
			}
			$options = array();
			foreach ($menuItems as $value => $label) {
				$options[] = '<option value="' . htmlspecialchars($value) . '"' . (!strcmp($currentValue, $value) ? ' selected="selected"' : '') . '>' . \TYPO3\CMS\Core\Utility\GeneralUtility::deHSCentities(htmlspecialchars($label)) . '</option>';
			}
			if (count($options)) {
				$onChange = 'jumpToUrl(\'' . $script . '?' . $mainParams . $addparams . '&' . $elementName . '=\'+this.options[this.selectedIndex].value,this);';
				return '

					<!-- Function Menu of module -->
					<select name="' . $elementName . '" onchange="' . htmlspecialchars($onChange) . '">
						' . implode('
						', $options) . '
					</select>
							';
			}
		}
	}

	/**
	 * Checkbox function menu.
	 * Works like ->getFuncMenu() but takes no $menuItem array since this is a simple checkbox.
	 *
	 * @param mixed $mainParams $id is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
	 * @param string $elementName The form elements name, probably something like "SET[...]
	 * @param string $currentValue The value to be selected currently.
	 * @param string $script The script to send the &id to, if empty it's automatically found
	 * @param string $addParams Additional parameters to pass to the script.
	 * @param string $tagParams Additional attributes for the checkbox input tag
	 * @return string HTML code for checkbox
	 * @see getFuncMenu()
	 */
	static public function getFuncCheck($mainParams, $elementName, $currentValue, $script = '', $addparams = '', $tagParams = '') {
		if (!is_array($mainParams)) {
			$mainParams = array('id' => $mainParams);
		}
		$mainParams = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $mainParams);
		if (!$script) {
			$script = basename(PATH_thisScript);
			$mainParams .= \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M') ? '&M=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M')) : '';
		}
		$onClick = 'jumpToUrl(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($script . '?' . $mainParams . $addparams . '&' . $elementName . '=') . '+(this.checked?1:0),this);';

		return
		'<input' .
			' type="checkbox"' .
			' class="checkbox"' .
			' name="' . $elementName . '"' .
			($currentValue ? ' checked="checked"' : '') .
			' onclick="' . htmlspecialchars($onClick) . '"' .
			($tagParams ? ' ' . $tagParams : '') .
			' value="1"' .
		' />';
	}

	/**
	 * Input field function menu
	 * Works like ->getFuncMenu() / ->getFuncCheck() but displays a input field instead which updates the script "onchange"
	 *
	 * @param mixed $mainParams $id is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
	 * @param string $elementName The form elements name, probably something like "SET[...]
	 * @param string $currentValue The value to be selected currently.
	 * @param integer $size Relative size of input field, max is 48
	 * @param string $script The script to send the &id to, if empty it's automatically found
	 * @param string $addParams Additional parameters to pass to the script.
	 * @return string HTML code for input text field.
	 * @see getFuncMenu()
	 */
	static public function getFuncInput($mainParams, $elementName, $currentValue, $size = 10, $script = '', $addparams = '') {
		if (!is_array($mainParams)) {
			$mainParams = array('id' => $mainParams);
		}
		$mainParams = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $mainParams);
		if (!$script) {
			$script = basename(PATH_thisScript);
			$mainParams .= \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M') ? '&M=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M')) : '';
		}
		$onChange = 'jumpToUrl(\'' . $script . '?' . $mainParams . $addparams . '&' . $elementName . '=\'+escape(this.value),this);';
		return '<input type="text"' . $GLOBALS['TBE_TEMPLATE']->formWidth($size) . ' name="' . $elementName . '" value="' . htmlspecialchars($currentValue) . '" onchange="' . htmlspecialchars($onChange) . '" />';
	}

	/**
	 * Removes menu items from $itemArray if they are configured to be removed by TSconfig for the module ($modTSconfig)
	 * See Inside TYPO3 about how to program modules and use this API.
	 *
	 * @param array $modTSconfig Module TS config array
	 * @param array $itemArray Array of items from which to remove items.
	 * @param string $TSref $TSref points to the "object string" in $modTSconfig
	 * @return array The modified $itemArray is returned.
	 */
	static public function unsetMenuItems($modTSconfig, $itemArray, $TSref) {
		// Getting TS-config options for this module for the Backend User:
		$conf = $GLOBALS['BE_USER']->getTSConfig($TSref, $modTSconfig);
		if (is_array($conf['properties'])) {
			foreach ($conf['properties'] as $key => $val) {
				if (!$val) {
					unset($itemArray[$key]);
				}
			}
		}
		return $itemArray;
	}

	/**
	 * Call to update the page tree frame (or something else..?) after
	 * use 'updatePageTree' as a first parameter will set the page tree to be updated.
	 *
	 * @param string $set Key to set the update signal. When setting, this value contains strings telling WHAT to set. At this point it seems that the value "updatePageTree" is the only one it makes sense to set. If empty, all update signals will be removed.
	 * @param mixed $params Additional information for the update signal, used to only refresh a branch of the tree
	 * @return void
	 * @see 	\TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode()
	 */
	static public function setUpdateSignal($set = '', $params = '') {
		$modData = $GLOBALS['BE_USER']->getModuleData('TYPO3\\CMS\\Backend\\Utility\\BackendUtility::getUpdateSignal', 'ses');
		if ($set) {
			$modData[$set] = array(
				'set' => $set,
				'parameter' => $params
			);
		} else {
			// clear the module data
			$modData = array();
		}
		$GLOBALS['BE_USER']->pushModuleData('TYPO3\\CMS\\Backend\\Utility\\BackendUtility::getUpdateSignal', $modData);
	}

	/**
	 * Call to update the page tree frame (or something else..?) if this is set by the function
	 * setUpdateSignal(). It will return some JavaScript that does the update (called in the typo3/template.php file, end() function)
	 *
	 * @return string HTML javascript code
	 * @see 	\TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal()
	 */
	static public function getUpdateSignalCode() {
		$signals = array();
		$modData = $GLOBALS['BE_USER']->getModuleData('TYPO3\\CMS\\Backend\\Utility\\BackendUtility::getUpdateSignal', 'ses');
		if (!count($modData)) {
			return '';
		}
		// Hook: Allows to let TYPO3 execute your JS code
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook'])) {
			$updateSignals = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook'];
		} else {
			$updateSignals = array();
		}
		// Loop through all setUpdateSignals and get the JS code
		foreach ($modData as $set => $val) {
			if (isset($updateSignals[$set])) {
				$params = array('set' => $set, 'parameter' => $val['parameter'], 'JScode' => '');
				$ref = NULL;
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($updateSignals[$set], $params, $ref);
				$signals[] = $params['JScode'];
			} else {
				switch ($set) {
				case 'updatePageTree':
					$signals[] = '
							if (top && top.TYPO3.Backend.NavigationContainer.PageTree) {
								top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
							}
						';
					break;
				case 'updateFolderTree':
					$signals[] = '
							if (top && top.TYPO3.Backend.NavigationIframe) {
								top.TYPO3.Backend.NavigationIframe.refresh();
							}';
					break;
				case 'updateModuleMenu':
					$signals[] = '
							if (top && top.TYPO3.ModuleMenu.App) {
								top.TYPO3.ModuleMenu.App.refreshMenu();
							}';
					break;
				}
			}
		}
		$content = implode(LF, $signals);
		// For backwards compatibility, should be replaced
		self::setUpdateSignal();
		return $content;
	}

	/**
	 * Returns an array which is most backend modules becomes MOD_SETTINGS containing values from function menus etc. determining the function of the module.
	 * This is kind of session variable management framework for the backend users.
	 * If a key from MOD_MENU is set in the CHANGED_SETTINGS array (eg. a value is passed to the script from the outside), this value is put into the settings-array
	 * Ultimately, see Inside TYPO3 for how to use this function in relation to your modules.
	 *
	 * @param array $MOD_MENU MOD_MENU is an array that defines the options in menus.
	 * @param array $CHANGED_SETTINGS CHANGED_SETTINGS represents the array used when passing values to the script from the menus.
	 * @param string $modName modName is the name of this module. Used to get the correct module data.
	 * @param string $type If type is 'ses' then the data is stored as session-lasting data. This means that it'll be wiped out the next time the user logs in.
	 * @param string $dontValidateList dontValidateList can be used to list variables that should not be checked if their value is found in the MOD_MENU array. Used for dynamically generated menus.
	 * @param string $setDefaultList List of default values from $MOD_MENU to set in the output array (only if the values from MOD_MENU are not arrays)
	 * @return array The array $settings, which holds a key for each MOD_MENU key and the values of each key will be within the range of values for each menuitem
	 */
	static public function getModuleData($MOD_MENU, $CHANGED_SETTINGS, $modName, $type = '', $dontValidateList = '', $setDefaultList = '') {
		if ($modName && is_string($modName)) {
			// Getting stored user-data from this module:
			$settings = $GLOBALS['BE_USER']->getModuleData($modName, $type);
			$changed = 0;
			if (!is_array($settings)) {
				$changed = 1;
				$settings = array();
			}
			if (is_array($MOD_MENU)) {
				foreach ($MOD_MENU as $key => $var) {
					// If a global var is set before entering here. eg if submitted, then it's substituting the current value the array.
					if (is_array($CHANGED_SETTINGS) && isset($CHANGED_SETTINGS[$key])) {
						if (is_array($CHANGED_SETTINGS[$key])) {
							$serializedSettings = serialize($CHANGED_SETTINGS[$key]);
							if (strcmp($settings[$key], $serializedSettings)) {
								$settings[$key] = $serializedSettings;
								$changed = 1;
							}
						} else {
							if (strcmp($settings[$key], $CHANGED_SETTINGS[$key])) {
								$settings[$key] = $CHANGED_SETTINGS[$key];
								$changed = 1;
							}
						}
					}
					// If the $var is an array, which denotes the existence of a menu, we check if the value is permitted
					if (is_array($var) && (!$dontValidateList || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($dontValidateList, $key))) {
						// If the setting is an array or not present in the menu-array, MOD_MENU, then the default value is inserted.
						if (is_array($settings[$key]) || !isset($MOD_MENU[$key][$settings[$key]])) {
							$settings[$key] = (string) key($var);
							$changed = 1;
						}
					}
					// Sets default values (only strings/checkboxes, not menus)
					if ($setDefaultList && !is_array($var)) {
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($setDefaultList, $key) && !isset($settings[$key])) {
							$settings[$key] = (string) $var;
						}
					}
				}
			} else {
				die('No menu!');
			}
			if ($changed) {
				$GLOBALS['BE_USER']->pushModuleData($modName, $settings);
			}
			return $settings;
		} else {
			die('Wrong module name: "' . $modName . '"');
		}
	}

	/**
	 * Returns the URL to a given module
	 *
	 * @param string $moduleName Name of the module
	 * @param array $urlParameters URL parameters that should be added as key value pairs
	 * @param boolean/string $backPathOverride backpath that should be used instead of the global $BACK_PATH
	 * @param boolean $returnAbsoluteUrl If set to TRUE, the URL returned will be absolute, $backPathOverride will be ignored in this case
	 * @return boolean/string Calculated URL or FALSE
	 */
	static public function getModuleUrl($moduleName, $urlParameters = array(), $backPathOverride = FALSE, $returnAbsoluteUrl = FALSE) {
		if (!$GLOBALS['BE_USER']->check('modules', $moduleName)) {
			return FALSE;
		}
		if ($backPathOverride === FALSE) {
			$backPath = $GLOBALS['BACK_PATH'];
		} else {
			$backPath = $backPathOverride;
		}
		$allUrlParameters = array();
		$allUrlParameters['M'] = $moduleName;
		$allUrlParameters = array_merge($allUrlParameters, $urlParameters);
		$url = 'mod.php?' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $allUrlParameters, '', TRUE);
		if ($returnAbsoluteUrl) {
			return \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR') . $url;
		} else {
			return $backPath . $url;
		}
	}

	/**
	 * Return a link to the list view
	 *
	 * @param array $urlParameters URL parameters that should be added as key value pairs
	 * @param string $linkTitle title for the link tag
	 * @param string $linkText optional link text after the icon
	 * @return string A complete link tag or empty string
	 */
	static public function getListViewLink($urlParameters = array(), $linkTitle = '', $linkText = '') {
		$url = self::getModuleUrl('web_list', $urlParameters);
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('recordlist') || $url === FALSE) {
			return '';
		} else {
			return '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($linkTitle) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-list-open') . htmlspecialchars($linkText) . '</a>';
		}
	}

	/**
	 * Generates a token and returns a parameter for the URL
	 *
	 * @param string $formName Context of the token
	 * @param string $tokenName The name of the token GET variable
	 * @return string A URL GET variable including ampersand
	 */
	static public function getUrlToken($formName = 'securityToken', $tokenName = 'formToken') {
		$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
		return '&' . $tokenName . '=' . $formprotection->generateToken($formName);
	}

	/*******************************************
	 *
	 * Core
	 *
	 *******************************************/
	/**
	 * Unlock or Lock a record from $table with $uid
	 * If $table and $uid is not set, then all locking for the current BE_USER is removed!
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid
	 * @param integer $pid Record pid
	 * @return void
	 * @internal
	 */
	static public function lockRecords($table = '', $uid = 0, $pid = 0) {
		if (isset($GLOBALS['BE_USER']->user['uid'])) {
			$user_id = intval($GLOBALS['BE_USER']->user['uid']);
			if ($table && $uid) {
				$fields_values = array(
					'userid' => $user_id,
					'feuserid' => 0,
					'tstamp' => $GLOBALS['EXEC_TIME'],
					'record_table' => $table,
					'record_uid' => $uid,
					'username' => $GLOBALS['BE_USER']->user['username'],
					'record_pid' => $pid
				);
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_lockedrecords', $fields_values);
			} else {
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_lockedrecords', 'userid=' . intval($user_id));
			}
		}
	}

	/**
	 * Returns information about whether the record from table, $table, with uid, $uid is currently locked (edited by another user - which should issue a warning).
	 * Notice: Locking is not strictly carried out since locking is abandoned when other backend scripts are activated - which means that a user CAN have a record "open" without having it locked. So this just serves as a warning that counts well in 90% of the cases, which should be sufficient.
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid
	 * @return array
	 * @internal
	 * @see class.db_layout.inc, alt_db_navframe.php, alt_doc.php, db_layout.php
	 */
	static public function isRecordLocked($table, $uid) {
		if (!is_array($GLOBALS['LOCKED_RECORDS'])) {
			$GLOBALS['LOCKED_RECORDS'] = array();
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_lockedrecords', 'sys_lockedrecords.userid<>' . intval($GLOBALS['BE_USER']->user['uid']) . '
								AND sys_lockedrecords.tstamp > ' . ($GLOBALS['EXEC_TIME'] - 2 * 3600));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Get the type of the user that locked this record:
				if ($row['userid']) {
					$userTypeLabel = 'beUser';
				} elseif ($row['feuserid']) {
					$userTypeLabel = 'feUser';
				} else {
					$userTypeLabel = 'user';
				}
				$userType = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.' . $userTypeLabel);
				// Get the username (if available):
				if ($row['username']) {
					$userName = $row['username'];
				} else {
					$userName = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.unknownUser');
				}
				$GLOBALS['LOCKED_RECORDS'][$row['record_table'] . ':' . $row['record_uid']] = $row;
				$GLOBALS['LOCKED_RECORDS'][$row['record_table'] . ':' . $row['record_uid']]['msg'] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.lockedRecordUser'), $userType, $userName, self::calcAge($GLOBALS['EXEC_TIME'] - $row['tstamp'], $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')));
				if ($row['record_pid'] && !isset($GLOBALS['LOCKED_RECORDS'][($row['record_table'] . ':' . $row['record_pid'])])) {
					$GLOBALS['LOCKED_RECORDS']['pages:' . $row['record_pid']]['msg'] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.lockedRecordUser_content'), $userType, $userName, self::calcAge($GLOBALS['EXEC_TIME'] - $row['tstamp'], $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')));
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $GLOBALS['LOCKED_RECORDS'][$table . ':' . $uid];
	}

	/**
	 * Returns select statement for MM relations (as used by TCEFORMs etc)
	 *
	 * @param array $fieldValue Configuration array for the field, taken from $GLOBALS['TCA']
	 * @param string $field Field name
	 * @param array $TSconfig TSconfig array from which to get further configuration settings for the field name
	 * @param string $prefix Prefix string for the key "*foreign_table_where" from $fieldValue array
	 * @return string Part of query
	 * @internal
	 */
	static public function exec_foreign_table_where_query($fieldValue, $field = '', $TSconfig = array(), $prefix = '') {
		$foreign_table = $fieldValue['config'][$prefix . 'foreign_table'];
		$rootLevel = $GLOBALS['TCA'][$foreign_table]['ctrl']['rootLevel'];
		$fTWHERE = $fieldValue['config'][$prefix . 'foreign_table_where'];
		if (strstr($fTWHERE, '###REC_FIELD_')) {
			$fTWHERE_parts = explode('###REC_FIELD_', $fTWHERE);
			foreach ($fTWHERE_parts as $kk => $vv) {
				if ($kk) {
					$fTWHERE_subpart = explode('###', $vv, 2);
					if (substr($fTWHERE_parts[0], -1) === '\'' && $fTWHERE_subpart[1][0] === '\'') {
						$fTWHERE_parts[$kk] = $GLOBALS['TYPO3_DB']->quoteStr($TSconfig['_THIS_ROW'][$fTWHERE_subpart[0]], $foreign_table) . $fTWHERE_subpart[1];
					} else {
						$fTWHERE_parts[$kk] = $GLOBALS['TYPO3_DB']->fullQuoteStr($TSconfig['_THIS_ROW'][$fTWHERE_subpart[0]], $foreign_table) . $fTWHERE_subpart[1];
					}
				}
			}
			$fTWHERE = implode('', $fTWHERE_parts);
		}
		$fTWHERE = str_replace('###CURRENT_PID###', intval($TSconfig['_CURRENT_PID']), $fTWHERE);
		$fTWHERE = str_replace('###THIS_UID###', intval($TSconfig['_THIS_UID']), $fTWHERE);
		$fTWHERE = str_replace('###THIS_CID###', intval($TSconfig['_THIS_CID']), $fTWHERE);
		$fTWHERE = str_replace('###STORAGE_PID###', intval($TSconfig['_STORAGE_PID']), $fTWHERE);
		$fTWHERE = str_replace('###SITEROOT###', intval($TSconfig['_SITEROOT']), $fTWHERE);
		$fTWHERE = str_replace('###PAGE_TSCONFIG_ID###', intval($TSconfig[$field]['PAGE_TSCONFIG_ID']), $fTWHERE);
		$fTWHERE = str_replace('###PAGE_TSCONFIG_IDLIST###', $GLOBALS['TYPO3_DB']->cleanIntList($TSconfig[$field]['PAGE_TSCONFIG_IDLIST']), $fTWHERE);
		$fTWHERE = str_replace('###PAGE_TSCONFIG_STR###', $GLOBALS['TYPO3_DB']->quoteStr($TSconfig[$field]['PAGE_TSCONFIG_STR'], $foreign_table), $fTWHERE);
		$wgolParts = $GLOBALS['TYPO3_DB']->splitGroupOrderLimit($fTWHERE);
		// rootLevel = -1 means that elements can be on the rootlevel OR on any page (pid!=-1)
		// rootLevel = 0 means that elements are not allowed on root level
		// rootLevel = 1 means that elements are only on the root level (pid=0)
		if ($rootLevel == 1 || $rootLevel == -1) {
			$pidWhere = $foreign_table . '.pid' . (($rootLevel == -1) ? '<>-1' : '=0');
			$queryParts = array(
				'SELECT' => self::getCommonSelectFields($foreign_table, $foreign_table . '.'),
				'FROM' => $foreign_table,
				'WHERE' => $pidWhere . ' ' . self::deleteClause($foreign_table) . ' ' . $wgolParts['WHERE'],
				'GROUPBY' => $wgolParts['GROUPBY'],
				'ORDERBY' => $wgolParts['ORDERBY'],
				'LIMIT' => $wgolParts['LIMIT']
			);
		} else {
			$pageClause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			if ($foreign_table != 'pages') {
				$queryParts = array(
					'SELECT' => self::getCommonSelectFields($foreign_table, $foreign_table . '.'),
					'FROM' => $foreign_table . ', pages',
					'WHERE' => 'pages.uid=' . $foreign_table . '.pid
								AND pages.deleted=0 ' . self::deleteClause($foreign_table) . ' AND ' . $pageClause . ' ' . $wgolParts['WHERE'],
					'GROUPBY' => $wgolParts['GROUPBY'],
					'ORDERBY' => $wgolParts['ORDERBY'],
					'LIMIT' => $wgolParts['LIMIT']
				);
			} else {
				$queryParts = array(
					'SELECT' => self::getCommonSelectFields($foreign_table, $foreign_table . '.'),
					'FROM' => 'pages',
					'WHERE' => 'pages.deleted=0
								AND ' . $pageClause . ' ' . $wgolParts['WHERE'],
					'GROUPBY' => $wgolParts['GROUPBY'],
					'ORDERBY' => $wgolParts['ORDERBY'],
					'LIMIT' => $wgolParts['LIMIT']
				);
			}
		}
		return $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
	}

	/**
	 * Returns TSConfig for the TCEFORM object in Page TSconfig.
	 * Used in TCEFORMs
	 *
	 * @param string $table Table name present in TCA
	 * @param array $row Row from table
	 * @return array
	 */
	static public function getTCEFORM_TSconfig($table, $row) {
		self::fixVersioningPid($table, $row);
		$res = array();
		$typeVal = self::getTCAtypeValue($table, $row);
		// Get main config for the table
		list($TScID, $cPid) = self::getTSCpid($table, $row['uid'], $row['pid']);
		$rootLine = self::BEgetRootLine($TScID, '', TRUE);
		if ($TScID >= 0) {
			$tempConf = $GLOBALS['BE_USER']->getTSConfig('TCEFORM.' . $table, self::getPagesTSconfig($TScID, $rootLine));
			if (is_array($tempConf['properties'])) {
				foreach ($tempConf['properties'] as $key => $val) {
					if (is_array($val)) {
						$fieldN = substr($key, 0, -1);
						$res[$fieldN] = $val;
						unset($res[$fieldN]['types.']);
						if (strcmp($typeVal, '') && is_array($val['types.'][$typeVal . '.'])) {
							$res[$fieldN] = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($res[$fieldN], $val['types.'][$typeVal . '.']);
						}
					}
				}
			}
		}
		$res['_CURRENT_PID'] = $cPid;
		$res['_THIS_UID'] = $row['uid'];
		$res['_THIS_CID'] = $row['cid'];
		// So the row will be passed to foreign_table_where_query()
		$res['_THIS_ROW'] = $row;
		foreach ($rootLine as $rC) {
			if (!$res['_STORAGE_PID']) {
				$res['_STORAGE_PID'] = intval($rC['storage_pid']);
			}
			if (!$res['_SITEROOT']) {
				$res['_SITEROOT'] = $rC['is_siteroot'] ? intval($rC['uid']) : 0;
			}
		}
		return $res;
	}

	/**
	 * Find the real PID of the record (with $uid from $table).
	 * This MAY be impossible if the pid is set as a reference to the former record or a page (if two records are created at one time).
	 * NOTICE: Make sure that the input PID is never negative because the record was an offline version!
	 * Therefore, you should always use \TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid($table,$row); on the data you input before calling this function!
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid
	 * @param integer $pid Record pid, could be negative then pointing to a record from same table whose pid to find and return.
	 * @return integer
	 * @internal
	 * @see \TYPO3\CMS\Core\DataHandling\DataHandler::copyRecord(), getTSCpid()
	 */
	static public function getTSconfig_pidValue($table, $uid, $pid) {
		// If pid is an integer this takes precedence in our lookup.
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pid)) {
			$thePidValue = intval($pid);
			// If ref to another record, look that record up.
			if ($thePidValue < 0) {
				$pidRec = self::getRecord($table, abs($thePidValue), 'pid');
				$thePidValue = is_array($pidRec) ? $pidRec['pid'] : -2;
			}
		} else {
			// Try to fetch the record pid from uid. If the uid is 'NEW...' then this will of course return nothing
			$rr = self::getRecord($table, $uid);
			if (is_array($rr)) {
				// First check if the pid is -1 which means it is a workspaced element. Get the "real" record:
				if ($rr['pid'] == '-1') {
					$rr = self::getRecord($table, $rr['t3ver_oid'], 'pid');
					if (is_array($rr)) {
						$thePidValue = $rr['pid'];
					}
				} else {
					// Returning the "pid" of the record
					$thePidValue = $rr['pid'];
				}
			}
			if (!$thePidValue) {
				// Returns -1 if the record with this pid was not found.
				$thePidValue = -1;
			}
		}
		return $thePidValue;
	}

	/**
	 * Return $uid if $table is pages and $uid is integer - otherwise the $pid
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid
	 * @param integer $pid Record pid
	 * @return integer
	 * @internal
	 * @see \TYPO3\CMS\Backend\Form\FormEngine::getTSCpid()
	 */
	static public function getPidForModTSconfig($table, $uid, $pid) {
		$retVal = $table == 'pages' && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid) ? $uid : $pid;
		return $retVal;
	}

	/**
	 * Returns the REAL pid of the record, if possible. If both $uid and $pid is strings, then pid=-1 is returned as an error indication.
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid
	 * @param integer $pid Record pid
	 * @return array Array of two integers; first is the REAL PID of a record and if its a new record negative values are resolved to the true PID, second value is the PID value for TSconfig (uid if table is pages, otherwise the pid)
	 * @internal
	 * @see \TYPO3\CMS\Core\DataHandling\DataHandler::setHistory(), \TYPO3\CMS\Core\DataHandling\DataHandler::process_datamap()
	 */
	static public function getTSCpid($table, $uid, $pid) {
		// If pid is negative (referring to another record) the pid of the other record is fetched and returned.
		$cPid = self::getTSconfig_pidValue($table, $uid, $pid);
		// $TScID is the id of $table = pages, else it's the pid of the record.
		$TScID = self::getPidForModTSconfig($table, $uid, $cPid);
		return array($TScID, $cPid);
	}

	/**
	 * Returns first found domain record "domainName" (without trailing slash) if found in the input $rootLine
	 *
	 * @param array $rootLine Root line array
	 * @return string Domain name, if found.
	 */
	static public function firstDomainRecord($rootLine) {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
			foreach ($rootLine as $row) {
				$dRec = self::getRecordsByField('sys_domain', 'pid', $row['uid'], ' AND redirectTo=\'\' AND hidden=0', '', 'sorting');
				if (is_array($dRec)) {
					$dRecord = reset($dRec);
					return rtrim($dRecord['domainName'], '/');
				}
			}
		}
	}

	/**
	 * Returns the sys_domain record for $domain, optionally with $path appended.
	 *
	 * @param string $domain Domain name
	 * @param string $path Appended path
	 * @return array Domain record, if found
	 */
	static public function getDomainStartPage($domain, $path = '') {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
			$domain = explode(':', $domain);
			$domain = strtolower(preg_replace('/\\.$/', '', $domain[0]));
			// Path is calculated.
			$path = trim(preg_replace('/\\/[^\\/]*$/', '', $path));
			// Stuff
			$domain .= $path;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('sys_domain.*', 'pages,sys_domain', '
				pages.uid=sys_domain.pid
				AND sys_domain.hidden=0
				AND (sys_domain.domainName=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($domain, 'sys_domain') . ' OR sys_domain.domainName=' . $GLOBALS['TYPO3_DB']->fullQuoteStr(($domain . '/'), 'sys_domain') . ')' . self::deleteClause('pages'), '', '', '1');
			$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			return $result;
		}
	}

	/**
	 * Returns overlayered RTE setup from an array with TSconfig. Used in TCEforms and TCEmain
	 *
	 * @param array $RTEprop The properties of Page TSconfig in the key "RTE.
	 * @param string $table Table name
	 * @param string $field Field name
	 * @param string $type Type value of the current record (like from CType of tt_content)
	 * @return array Array with the configuration for the RTE
	 * @internal
	 */
	static public function RTEsetup($RTEprop, $table, $field, $type = '') {
		$thisConfig = is_array($RTEprop['default.']) ? $RTEprop['default.'] : array();
		$thisFieldConf = $RTEprop['config.'][$table . '.'][$field . '.'];
		if (is_array($thisFieldConf)) {
			unset($thisFieldConf['types.']);
			$thisConfig = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($thisConfig, $thisFieldConf);
		}
		if ($type && is_array($RTEprop['config.'][$table . '.'][$field . '.']['types.'][$type . '.'])) {
			$thisConfig = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($thisConfig, $RTEprop['config.'][$table . '.'][$field . '.']['types.'][$type . '.']);
		}
		return $thisConfig;
	}

	/**
	 * Returns first possible RTE object if available.
	 * Usage: $RTEobj = &\TYPO3\CMS\Backend\Utility\BackendUtility::RTEgetObj();
	 *
	 * @return mixed If available, returns RTE object, otherwise an array of messages from possible RTEs
	 */
	static public function &RTEgetObj() {
		// If no RTE object has been set previously, try to create it:
		if (!isset($GLOBALS['T3_VAR']['RTEobj'])) {
			// Set the object string to blank by default:
			$GLOBALS['T3_VAR']['RTEobj'] = array();
			// Traverse registered RTEs:
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_reg'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_reg'] as $extKey => $rteObjCfg) {
					$rteObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($rteObjCfg['objRef']);
					if (is_object($rteObj)) {
						if ($rteObj->isAvailable()) {
							$GLOBALS['T3_VAR']['RTEobj'] = $rteObj;
							break;
						} else {
							$GLOBALS['T3_VAR']['RTEobj'] = array_merge($GLOBALS['T3_VAR']['RTEobj'], $rteObj->errorLog);
						}
					}
				}
			}
			if (!count($GLOBALS['T3_VAR']['RTEobj'])) {
				$GLOBALS['T3_VAR']['RTEobj'][] = 'No RTEs configured at all';
			}
		}
		// Return RTE object (if any!)
		return $GLOBALS['T3_VAR']['RTEobj'];
	}

	/**
	 * Returns soft-reference parser for the softRef processing type
	 * Usage: $softRefObj = &\TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj('[parser key]');
	 *
	 * @param string $spKey softRef parser key
	 * @return mixed If available, returns Soft link parser object.
	 */
	static public function &softRefParserObj($spKey) {
		// If no softRef parser object has been set previously, try to create it:
		if (!isset($GLOBALS['T3_VAR']['softRefParser'][$spKey])) {
			// Set the object string to blank by default:
			$GLOBALS['T3_VAR']['softRefParser'][$spKey] = '';
			// Now, try to create parser object:
			$objRef = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser'][$spKey]
				? $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser'][$spKey]
				: $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser_GL'][$spKey];
			if ($objRef) {
				$softRefParserObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($objRef, '');
				if (is_object($softRefParserObj)) {
					$GLOBALS['T3_VAR']['softRefParser'][$spKey] = $softRefParserObj;
				}
			}
		}
		// Return RTE object (if any!)
		return $GLOBALS['T3_VAR']['softRefParser'][$spKey];
	}

	/**
	 * Returns array of soft parser references
	 *
	 * @param string $parserList softRef parser list
	 * @return array Array where the parser key is the key and the value is the parameter string
	 */
	static public function explodeSoftRefParserList($parserList) {
		// Looking for global parsers:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser_GL'])) {
			$parserList = implode(',', array_keys($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser_GL'])) . ',' . $parserList;
		}
		// Return immediately if list is blank:
		if (!strlen($parserList)) {
			return FALSE;
		}
		// Otherwise parse the list:
		$keyList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $parserList, 1);
		$output = array();
		foreach ($keyList as $val) {
			$reg = array();
			if (preg_match('/^([[:alnum:]_-]+)\\[(.*)\\]$/', $val, $reg)) {
				$output[$reg[1]] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $reg[2], 1);
			} else {
				$output[$val] = '';
			}
		}
		return $output;
	}

	/**
	 * Returns TRUE if $modName is set and is found as a main- or submodule in $TBE_MODULES array
	 *
	 * @param string $modName Module name
	 * @return boolean
	 */
	static public function isModuleSetInTBE_MODULES($modName) {
		$loaded = array();
		foreach ($GLOBALS['TBE_MODULES'] as $mkey => $list) {
			$loaded[$mkey] = 1;
			if (!is_array($list) && trim($list)) {
				$subList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $list, 1);
				foreach ($subList as $skey) {
					$loaded[$mkey . '_' . $skey] = 1;
				}
			}
		}
		return $modName && isset($loaded[$modName]);
	}

	/**
	 * Counting references to a record/file
	 *
	 * @param string $table Table name (or "_FILE" if its a file)
	 * @param string $ref Reference: If table, then integer-uid, if _FILE, then file reference (relative to PATH_site)
	 * @param string $msg Message with %s, eg. "There were %s records pointing to this file!
	 * @param string $count Reference count
	 * @return string Output string (or integer count value if no msg string specified)
	 */
	static public function referenceCount($table, $ref, $msg = '', $count = NULL) {
		if ($count === NULL) {
			// Look up the path:
			if ($table == '_FILE') {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($ref, PATH_site)) {
					$ref = substr($ref, strlen(PATH_site));
					$condition = 'ref_string=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($ref, 'sys_refindex');
				} else {
					return '';
				}
			} else {
				$condition = 'ref_uid=' . intval($ref);
			}
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'sys_refindex', 'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_refindex') . ' AND ' . $condition . ' AND deleted=0');
		}
		return $count ? ($msg ? sprintf($msg, $count) : $count) : '';
	}

	/**
	 * Counting translations of records
	 *
	 * @param string $table Table name
	 * @param string $ref Reference: the record's uid
	 * @param string $msg Message with %s, eg. "This record has %s translation(s) which will be deleted, too!
	 * @return string Output string (or integer count value if no msg string specified)
	 */
	static public function translationCount($table, $ref, $msg = '') {
		if (empty($GLOBALS['TCA'][$table]['ctrl']['transForeignTable']) && $GLOBALS['TCA'][$table]['ctrl']['languageField'] && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] && !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']) {
			$where = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . '=' . intval($ref) . ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '<>0';
			if (!empty($GLOBALS['TCA'][$table]['ctrl']['delete'])) {
				$where .= ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['delete'] . '=0';
			}
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $table, $where);
		}
		return $count ? ($msg ? sprintf($msg, $count) : $count) : '';
	}

	/*******************************************
	 *
	 * Workspaces / Versioning
	 *
	 *******************************************/
	/**
	 * Select all versions of a record, ordered by version id (DESC)
	 *
	 * @param string $table Table name to select from
	 * @param integer $uid Record uid for which to find versions.
	 * @param string $fields Field list to select
	 * @param integer $workspace Workspace ID, if zero all versions regardless of workspace is found.
	 * @param boolean $includeDeletedRecords If set, deleted-flagged versions are included! (Only for clean-up script!)
	 * @param array $row The current record
	 * @return array Array of versions of table/uid
	 */
	static public function selectVersionsOfRecord($table, $uid, $fields = '*', $workspace = 0, $includeDeletedRecords = FALSE, $row = NULL) {
		$realPid = 0;
		$outputRows = array();
		if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			if (is_array($row) && !$includeDeletedRecords) {
				$row['_CURRENT_VERSION'] = TRUE;
				$realPid = $row['pid'];
				$outputRows[] = $row;
			} else {
				// Select UID version:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, 'uid=' . intval($uid) . ($includeDeletedRecords ? '' : self::deleteClause($table)));
				// Add rows to output array:
				if ($res) {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					if ($row) {
						$row['_CURRENT_VERSION'] = TRUE;
						$realPid = $row['pid'];
						$outputRows[] = $row;
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
			}
			// Select all offline versions of record:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, 'pid=-1 AND uid<>' . intval($uid) . ' AND t3ver_oid=' . intval($uid) . ($workspace != 0 ? ' AND t3ver_wsid=' . intval($workspace) : '') . ($includeDeletedRecords ? '' : self::deleteClause($table)), '', 't3ver_id DESC');
			// Add rows to output array:
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$outputRows[] = $row;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// Set real-pid:
			foreach ($outputRows as $idx => $oRow) {
				$outputRows[$idx]['_REAL_PID'] = $realPid;
			}
			return $outputRows;
		}
	}

	/**
	 * Find page-tree PID for versionized record
	 * Will look if the "pid" value of the input record is -1 and if the table supports versioning - if so, it will translate the -1 PID into the PID of the original record
	 * Used whenever you are tracking something back, like making the root line.
	 * Will only translate if the workspace of the input record matches that of the current user (unless flag set)
	 * Principle; Record offline! => Find online?
	 *
	 * @param string $table Table name
	 * @param array $rr Record array passed by reference. As minimum, "pid" and "uid" fields must exist! "t3ver_oid" and "t3ver_wsid" is nice and will save you a DB query.
	 * @param boolean $ignoreWorkspaceMatch Ignore workspace match
	 * @return void (Passed by ref). If the record had its pid corrected to the online versions pid, then "_ORIG_pid" is set to the original pid value (-1 of course). The field "_ORIG_pid" is used by various other functions to detect if a record was in fact in a versionized branch.
	 * @see \TYPO3\CMS\Frontend\Page\PageRepository::fixVersioningPid()
	 */
	static public function fixVersioningPid($table, &$rr, $ignoreWorkspaceMatch = FALSE) {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')) {
			// Check that the input record is an offline version from a table that supports versioning:
			if (is_array($rr) && $rr['pid'] == -1 && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
				// Check values for t3ver_oid and t3ver_wsid:
				if (isset($rr['t3ver_oid']) && isset($rr['t3ver_wsid'])) {
					// If "t3ver_oid" is already a field, just set this:
					$oid = $rr['t3ver_oid'];
					$wsid = $rr['t3ver_wsid'];
				} else {
					// Otherwise we have to expect "uid" to be in the record and look up based on this:
					$newPidRec = self::getRecord($table, $rr['uid'], 't3ver_oid,t3ver_wsid');
					if (is_array($newPidRec)) {
						$oid = $newPidRec['t3ver_oid'];
						$wsid = $newPidRec['t3ver_wsid'];
					}
				}
				// If ID of current online version is found, look up the PID value of that:
				if ($oid && ($ignoreWorkspaceMatch || !strcmp((int) $wsid, $GLOBALS['BE_USER']->workspace))) {
					$oidRec = self::getRecord($table, $oid, 'pid');
					if (is_array($oidRec)) {
						$rr['_ORIG_pid'] = $rr['pid'];
						$rr['pid'] = $oidRec['pid'];
					}
				}
			}
		}
	}

	/**
	 * Workspace Preview Overlay
	 * Generally ALWAYS used when records are selected based on uid or pid.
	 * If records are selected on other fields than uid or pid (eg. "email = ....")
	 * then usage might produce undesired results and that should be evaluated on individual basis.
	 * Principle; Record online! => Find offline?
	 * Recently, this function has been modified so it MAY set $row to FALSE.
	 * This happens if a version overlay with the move-id pointer is found in which case we would like a backend preview.
	 * In other words, you should check if the input record is still an array afterwards when using this function.
	 *
	 * @param string $table Table name
	 * @param array $row Record array passed by reference. As minimum, the "uid" and  "pid" fields must exist! Fake fields cannot exist since the fields in the array is used as field names in the SQL look up. It would be nice to have fields like "t3ver_state" and "t3ver_mode_id" as well to avoid a new lookup inside movePlhOL().
	 * @param integer $wsid Workspace ID, if not specified will use $GLOBALS['BE_USER']->workspace
	 * @param boolean $unsetMovePointers If TRUE the function does not return a "pointer" row for moved records in a workspace
	 * @return void (Passed by ref).
	 * @see fixVersioningPid()
	 */
	static public function workspaceOL($table, &$row, $wsid = -99, $unsetMovePointers = FALSE) {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')) {
			// If this is FALSE the placeholder is shown raw in the backend.
			// I don't know if this move can be useful for users to toggle. Technically it can help debugging.
			$previewMovePlaceholders = TRUE;
			// Initialize workspace ID
			if ($wsid == -99) {
				$wsid = $GLOBALS['BE_USER']->workspace;
			}
			// Check if workspace is different from zero and record is set:
			if ($wsid !== 0 && is_array($row)) {
				// Check if input record is a move-placeholder and if so, find the pointed-to live record:
				if ($previewMovePlaceholders) {
					$orig_uid = $row['uid'];
					$orig_pid = $row['pid'];
					$movePldSwap = self::movePlhOL($table, $row);
				}
				$wsAlt = self::getWorkspaceVersionOfRecord($wsid, $table, $row['uid'], implode(',', array_keys($row)));
				// If version was found, swap the default record with that one.
				if (is_array($wsAlt)) {
					// Check if this is in move-state:
					if ($previewMovePlaceholders && !$movePldSwap && ($table == 'pages' || (int) $GLOBALS['TCA'][$table]['ctrl']['versioningWS'] >= 2) && $unsetMovePointers) {
						// Only for WS ver 2... (moving)
						// If t3ver_state is not found, then find it... (but we like best if it is here...)
						if (!isset($wsAlt['t3ver_state'])) {
							$stateRec = self::getRecord($table, $wsAlt['uid'], 't3ver_state');
							$state = $stateRec['t3ver_state'];
						} else {
							$state = $wsAlt['t3ver_state'];
						}
						if ((int) $state === 4) {
							// TODO: Same problem as frontend in versionOL(). See TODO point there.
							$row = FALSE;
							return;
						}
					}
					// Always correct PID from -1 to what it should be
					if (isset($wsAlt['pid'])) {
						// Keep the old (-1) - indicates it was a version.
						$wsAlt['_ORIG_pid'] = $wsAlt['pid'];
						// Set in the online versions PID.
						$wsAlt['pid'] = $row['pid'];
					}
					// For versions of single elements or page+content, swap UID and PID
					$wsAlt['_ORIG_uid'] = $wsAlt['uid'];
					$wsAlt['uid'] = $row['uid'];
					// Backend css class:
					$wsAlt['_CSSCLASS'] = 'ver-element';
					// Changing input record to the workspace version alternative:
					$row = $wsAlt;
				}
				// If the original record was a move placeholder, the uid and pid of that is preserved here:
				if ($movePldSwap) {
					$row['_MOVE_PLH'] = TRUE;
					$row['_MOVE_PLH_uid'] = $orig_uid;
					$row['_MOVE_PLH_pid'] = $orig_pid;
					// For display; To make the icon right for the placeholder vs. the original
					$row['t3ver_state'] = 3;
				}
			}
		}
	}

	/**
	 * Checks if record is a move-placeholder (t3ver_state==3) and if so it will set $row to be the pointed-to live record (and return TRUE)
	 *
	 * @param string $table Table name
	 * @param array $row Row (passed by reference) - must be online record!
	 * @return boolean TRUE if overlay is made.
	 * @see \TYPO3\CMS\Frontend\Page\PageRepository::movePlhOl()
	 */
	static public function movePlhOL($table, &$row) {
		// Only for WS ver 2... (moving)
		if ($table == 'pages' || (int) $GLOBALS['TCA'][$table]['ctrl']['versioningWS'] >= 2) {
			// If t3ver_move_id or t3ver_state is not found, then find it... (but we like best if it is here...)
			if (!isset($row['t3ver_move_id']) || !isset($row['t3ver_state'])) {
				$moveIDRec = self::getRecord($table, $row['uid'], 't3ver_move_id, t3ver_state');
				$moveID = $moveIDRec['t3ver_move_id'];
				$state = $moveIDRec['t3ver_state'];
			} else {
				$moveID = $row['t3ver_move_id'];
				$state = $row['t3ver_state'];
			}
			// Find pointed-to record.
			if ((int) $state === 3 && $moveID) {
				if ($origRow = self::getRecord($table, $moveID, implode(',', array_keys($row)))) {
					$row = $origRow;
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Select the workspace version of a record, if exists
	 *
	 * @param integer $workspace Workspace ID
	 * @param string $table Table name to select from
	 * @param integer $uid Record uid for which to find workspace version.
	 * @param string $fields Field list to select
	 * @return array If found, return record, otherwise FALSE
	 */
	static public function getWorkspaceVersionOfRecord($workspace, $table, $uid, $fields = '*') {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')) {
			if ($workspace !== 0 && $GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
				// Select workspace version of record:
				$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow($fields, $table, 'pid=-1 AND ' . 't3ver_oid=' . intval($uid) . ' AND ' . 't3ver_wsid=' . intval($workspace) . self::deleteClause($table));
				if (is_array($row)) {
					return $row;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Returns live version of record
	 *
	 * @param string $table Table name
	 * @param integer $uid Record UID of draft, offline version
	 * @param string $fields Field list, default is *
	 * @return array If found, the record, otherwise nothing.
	 */
	static public function getLiveVersionOfRecord($table, $uid, $fields = '*') {
		$liveVersionId = self::getLiveVersionIdOfRecord($table, $uid);
		if (is_null($liveVersionId) === FALSE) {
			return self::getRecord($table, $liveVersionId, $fields);
		}
	}

	/**
	 * Gets the id of the live version of a record.
	 *
	 * @param string $table Name of the table
	 * @param integer $uid Uid of the offline/draft record
	 * @return integer The id of the live version of the record (or NULL if nothing was found)
	 */
	static public function getLiveVersionIdOfRecord($table, $uid) {
		$liveVersionId = NULL;
		if (self::isTableWorkspaceEnabled($table)) {
			$currentRecord = self::getRecord($table, $uid, 'pid,t3ver_oid');
			if (is_array($currentRecord) && $currentRecord['pid'] == -1) {
				$liveVersionId = $currentRecord['t3ver_oid'];
			}
		}
		return $liveVersionId;
	}

	/**
	 * Will return where clause de-selecting new(/deleted)-versions from other workspaces.
	 * If in live-workspace, don't show "MOVE-TO-PLACEHOLDERS" records if versioningWS is 2 (allows moving)
	 *
	 * @param string $table Table name
	 * @return string Where clause if applicable.
	 */
	static public function versioningPlaceholderClause($table) {
		if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			$currentWorkspace = intval($GLOBALS['BE_USER']->workspace);
			return ' AND (' . $table . '.t3ver_state <= 0 OR ' . $table . '.t3ver_wsid = ' . $currentWorkspace . ')';
		}
	}

	/**
	 * Get additional where clause to select records of a specific workspace (includes live as well).
	 *
	 * @param string $table Table name
	 * @param integer $workspaceId Workspace ID
	 * @return string Workspace where clause
	 */
	static public function getWorkspaceWhereClause($table, $workspaceId = NULL) {
		$whereClause = '';
		if (self::isTableWorkspaceEnabled($table)) {
			if (is_null($workspaceId)) {
				$workspaceId = $GLOBALS['BE_USER']->workspace;
			}
			$workspaceId = intval($workspaceId);
			$pidOperator = $workspaceId === 0 ? '!=' : '=';
			$whereClause = ' AND ' . $table . '.t3ver_wsid=' . $workspaceId . ' AND ' . $table . '.pid' . $pidOperator . '-1';
		}
		return $whereClause;
	}

	/**
	 * Count number of versions on a page
	 *
	 * @param integer $workspace Workspace ID
	 * @param integer $pageId Page ID
	 * @return array Overview of records
	 */
	static public function countVersionsOfRecordsOnPage($workspace, $pageId) {
		$output = array();
		if ($workspace != 0) {
			foreach ($GLOBALS['TCA'] as $tableName => $cfg) {
				if ($tableName != 'pages' && $cfg['ctrl']['versioningWS']) {
					// Select all records from this table in the database from the workspace
					// This joins the online version with the offline version as tables A and B
					$output[$tableName] = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('B.uid as live_uid, A.uid as offline_uid', $tableName . ' A,' . $tableName . ' B', 'A.pid=-1' . ' AND B.pid=' . intval($pageId) . ' AND A.t3ver_wsid=' . intval($workspace) . ' AND A.t3ver_oid=B.uid' . self::deleteClause($tableName, 'A') . self::deleteClause($tableName, 'B'));
					if (!is_array($output[$tableName]) || !count($output[$tableName])) {
						unset($output[$tableName]);
					}
				}
			}
		}
		return $output;
	}

	/**
	 * Performs mapping of new uids to new versions UID in case of import inside a workspace.
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid (of live record placeholder)
	 * @return integer Uid of offline version if any, otherwise live uid.
	 */
	static public function wsMapId($table, $uid) {
		if ($wsRec = self::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $table, $uid, 'uid')) {
			return $wsRec['uid'];
		} else {
			return $uid;
		}
	}

	/**
	 * Returns move placeholder of online (live) version
	 *
	 * @param string $table Table name
	 * @param integer $uid Record UID of online version
	 * @param string $fields Field list, default is *
	 * @return array If found, the record, otherwise nothing.
	 */
	static public function getMovePlaceholder($table, $uid, $fields = '*') {
		$workspace = $GLOBALS['BE_USER']->workspace;
		if ($workspace !== 0 && $GLOBALS['TCA'][$table] && (int) $GLOBALS['TCA'][$table]['ctrl']['versioningWS'] >= 2) {
			// Select workspace version of record:
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow($fields, $table, 'pid<>-1 AND ' . 't3ver_state=3 AND ' . 't3ver_move_id=' . intval($uid) . ' AND ' . 't3ver_wsid=' . intval($workspace) . self::deleteClause($table));
			if (is_array($row)) {
				return $row;
			}
		}
		return FALSE;
	}

	/*******************************************
	 *
	 * Miscellaneous
	 *
	 *******************************************/
	/**
	 * Prints TYPO3 Copyright notice for About Modules etc. modules.
	 *
	 * Warning:
	 * DO NOT prevent this notice from being shown in ANY WAY.
	 * According to the GPL license an interactive application must show such a notice on start-up ('If the program is interactive, make it output a short notice... ' - see GPL.txt)
	 * Therefore preventing this notice from being properly shown is a violation of the license, regardless of whether you remove it or use a stylesheet to obstruct the display.
	 *
	 * @param boolean Display the version number within the copyright notice?
	 * @return string Text/Image (HTML) for copyright notice.
	 */
	static public function TYPO3_copyRightNotice($showVersionNumber = TRUE) {
		// Copyright Notice
		$loginCopyrightWarrantyProvider = strip_tags(trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['loginCopyrightWarrantyProvider']));
		$loginCopyrightWarrantyURL = strip_tags(trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['loginCopyrightWarrantyURL']));

		$versionNumber = $showVersionNumber ?
				' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:version.short') . ' ' .
				htmlspecialchars(TYPO3_version) : '';

		if (strlen($loginCopyrightWarrantyProvider) >= 2 && strlen($loginCopyrightWarrantyURL) >= 10) {
			$warrantyNote = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:warranty.by'), htmlspecialchars($loginCopyrightWarrantyProvider), '<a href="' . htmlspecialchars($loginCopyrightWarrantyURL) . '" target="_blank">', '</a>');
		} else {
			$warrantyNote = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:no.warranty'), '<a href="' . TYPO3_URL_LICENSE . '" target="_blank">', '</a>');
		}
		$cNotice = '<a href="' . TYPO3_URL_GENERAL . '" target="_blank">' .
				'<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/loginlogo_transp.gif', 'width="75" height="24" vspace="2" hspace="4"') . ' alt="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:typo3.logo') . '" align="left" />' .
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:typo3.cms') . $versionNumber . '</a>. ' .
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:copyright') . ' &copy; ' . htmlspecialchars(TYPO3_copyright_year) . ' Kasper Sk&aring;rh&oslash;j. ' .
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:extension.copyright') . ' ' .
				sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:details.link'), ('<a href="' . TYPO3_URL_GENERAL . '" target="_blank">' . TYPO3_URL_GENERAL . '</a>')) . ' ' .
				strip_tags($warrantyNote, '<a>') . ' ' .
				sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:free.software'), ('<a href="' . TYPO3_URL_LICENSE . '" target="_blank">'), '</a> ') .
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:keep.notice');
		return $cNotice;
	}

	/**
	 * Display some warning messages if this installation is obviously insecure!!
	 * These warnings are only displayed to admin users
	 *
	 * @return string Rendered messages as HTML
	 */
	static public function displayWarningMessages() {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			// Array containing warnings that must be displayed
			$warnings = array();
			// If this file exists and it isn't older than one hour, the Install Tool is enabled
			$enableInstallToolFile = PATH_site . 'typo3conf/ENABLE_INSTALL_TOOL';
			// Cleanup command, if set
			$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('adminWarning_cmd');
			switch ($cmd) {
			case 'remove_ENABLE_INSTALL_TOOL':
				if (unlink($enableInstallToolFile)) {
					unset($enableInstallToolFile);
				}
				break;
			}
			// Check if the Install Tool Password is still default: joh316
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] == md5('joh316')) {
				$url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=about');
				$warnings['install_password'] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_password'), '<a href="' . $url . '">', '</a>');
			}
			// Check if there is still a default user 'admin' with password 'password' (MD5sum = 5f4dcc3b5aa765d61d8327deb882cf99)
			$where_clause = 'username=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('admin', 'be_users') . ' AND password=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('5f4dcc3b5aa765d61d8327deb882cf99', 'be_users') . self::deleteClause('be_users');
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, username, password', 'be_users', $where_clause);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$url = 'alt_doc.php?returnUrl=' . urlencode('mod.php?M=help_AboutmodulesAboutmodules') . '&edit[be_users][' . $row['uid'] . ']=edit';
				$warnings['backend_admin'] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.backend_admin'), '<a href="' . htmlspecialchars($url) . '">', '</a>');
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// Check whether the file ENABLE_INSTALL_TOOL contains the string "KEEP_FILE" which permanently unlocks the install tool
			if (is_file($enableInstallToolFile) && trim(file_get_contents($enableInstallToolFile)) === 'KEEP_FILE') {
				$url = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT') . '?adminWarning_cmd=remove_ENABLE_INSTALL_TOOL';
				$warnings['install_enabled'] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_enabled'), '<span style="white-space:nowrap;">' . $enableInstallToolFile . '</span>');
				$warnings['install_enabled'] .= ' <a href="' . $url . '">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_enabled_cmd') . '</a>';
			}
			// Check if the encryption key is empty
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] == '') {
				$url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=config#set_encryptionKey');
				$warnings['install_encryption'] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_encryption'), '<a href="' . $url . '">', '</a>');
			}
			// Check if parts of fileDenyPattern were removed which is dangerous on Apache
			$defaultParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', FILE_DENY_PATTERN_DEFAULT, TRUE);
			$givenParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'], TRUE);
			$result = array_intersect($defaultParts, $givenParts);
			if ($defaultParts !== $result) {
				$warnings['file_deny_pattern'] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_deny_pattern_partsNotPresent'), '<br /><pre>' . htmlspecialchars(FILE_DENY_PATTERN_DEFAULT) . '</pre><br />');
			}
			// Check if fileDenyPattern allows to upload .htaccess files which is dangerous on Apache
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] != FILE_DENY_PATTERN_DEFAULT && \TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern('.htaccess')) {
				$warnings['file_deny_htaccess'] = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_deny_htaccess');
			}
			// Check if there are still updates to perform
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version(TYPO3_branch)) {
				$url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=update');
				$warnings['install_update'] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_update'), '<a href="' . $url . '">', '</a>');
			}
			// Check if sys_refindex is empty
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'sys_refindex');
			$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
			$lastRefIndexUpdate = $registry->get('core', 'sys_refindex_lastUpdate');
			if (!$count && $lastRefIndexUpdate) {
				$url =  \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('tools_dbint') . '&id=0&SET[function]=refindex';
				$warnings['backend_reference'] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.backend_reference_index'), '<a href="' . $url . '">', '</a>', self::dateTime($lastRefIndexUpdate));
			}
			// Check for memcached if configured
			$memCacheUse = FALSE;
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $table => $conf) {
					if (is_array($conf)) {
						foreach ($conf as $key => $value) {
							if (!is_array($value) && $value === 'TYPO3\\CMS\\Core\\Cache\\Backend\\MemcachedBackend') {
								$servers = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$table]['options']['servers'];
								$memCacheUse = TRUE;
								break;
							}
						}
					}
				}
				if ($memCacheUse) {
					$failed = array();
					$defaultPort = ini_get('memcache.default_port');
					if (function_exists('memcache_connect')) {
						if (is_array($servers)) {
							foreach ($servers as $testServer) {
								$configuredServer = $testServer;
								if (substr($testServer, 0, 7) == 'unix://') {
									$host = $testServer;
									$port = 0;
								} else {
									if (substr($testServer, 0, 6) === 'tcp://') {
										$testServer = substr($testServer, 6);
									}
									if (strstr($testServer, ':') !== FALSE) {
										list($host, $port) = explode(':', $testServer, 2);
									} else {
										$host = $testServer;
										$port = $defaultPort;
									}
								}
								$memcache_obj = @memcache_connect($host, $port);
								if ($memcache_obj != NULL) {
									memcache_close($memcache_obj);
								} else {
									$failed[] = $configuredServer;
								}
							}
						}
					}
					if (count($failed) > 0) {
						$warnings['memcached'] = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.memcache_not_usable') . '<br/>' . implode(', ', $failed);
					}
				}
			}
			// Hook for additional warnings
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'displayWarningMessages_postProcess')) {
						$hookObj->displayWarningMessages_postProcess($warnings);
					}
				}
			}
			if (count($warnings)) {
				if (count($warnings) > 1) {
					$securityWarnings = '<ul><li>' . implode('</li><li>', $warnings) . '</li></ul>';
				} else {
					$securityWarnings = '<p>' . implode('', $warnings) . '</p>';
				}
				$securityMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $securityWarnings, $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.header'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$content = '<div style="margin: 20px 0px;">' . $securityMessage->render() . '</div>';
				unset($warnings);
				return $content;
			}
		}
		return '<p>&nbsp;</p>';
	}

	/**
	 * Returns "web" if the $path (absolute) is within the DOCUMENT ROOT - and thereby qualifies as a "web" folder.
	 *
	 * @param string $path Path to evaluate
	 * @return boolean
	 */
	static public function getPathType_web_nonweb($path) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($path, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT')) ? 'web' : '';
	}

	/**
	 * Creates ADMCMD parameters for the "viewpage" extension / "cms" frontend
	 *
	 * @param array $pageinfo Page record
	 * @return string Query-parameters
	 * @internal
	 */
	static public function ADMCMD_previewCmds($pageinfo) {
		if ($pageinfo['fe_group'] > 0) {
			$simUser = '&ADMCMD_simUser=' . $pageinfo['fe_group'];
		}
		if ($pageinfo['starttime'] > $GLOBALS['EXEC_TIME']) {
			$simTime = '&ADMCMD_simTime=' . $pageinfo['starttime'];
		}
		if ($pageinfo['endtime'] < $GLOBALS['EXEC_TIME'] && $pageinfo['endtime'] != 0) {
			$simTime = '&ADMCMD_simTime=' . ($pageinfo['endtime'] - 1);
		}
		return $simUser . $simTime;
	}

	/**
	 * Returns an array with key=>values based on input text $params
	 * $params is exploded by line-breaks and each line is supposed to be on the syntax [key] = [some value]
	 * These pairs will be parsed into an array an returned.
	 *
	 * @param string $params String of parameters on multiple lines to parse into key-value pairs (see function description)
	 * @return array
	 */
	static public function processParams($params) {
		$paramArr = array();
		$lines = explode(LF, $params);
		foreach ($lines as $val) {
			$val = trim($val);
			if ($val) {
				$pair = explode('=', $val, 2);
				$paramArr[trim($pair[0])] = trim($pair[1]);
			}
		}
		return $paramArr;
	}

	/**
	 * Returns the name of the backend script relative to the TYPO3 main directory.
	 *
	 * @param string $interface Name of the backend interface  (backend, frontend) to look up the script name for. If no interface is given, the interface for the current backend user is used.
	 * @return string The name of the backend script relative to the TYPO3 main directory.
	 */
	static public function getBackendScript($interface = '') {
		if (!$interface) {
			$interface = $GLOBALS['BE_USER']->uc['interfaceSetup'];
		}
		switch ($interface) {
		case 'frontend':
			$script = '../.';
			break;
		case 'backend':

		default:
			$script = 'backend.php';
			break;
		}
		return $script;
	}

	/**
	 * Determines whether a table is enabled for workspaces.
	 *
	 * @param string $table Name of the table to be checked
	 * @return boolean
	 */
	static public function isTableWorkspaceEnabled($table) {
		return isset($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) && $GLOBALS['TCA'][$table]['ctrl']['versioningWS'];
	}

	/**
	 * Gets the TCA configuration of a field.
	 *
	 * @param string $table Name of the table
	 * @param string $field Name of the field
	 * @return array
	 */
	static public function getTcaFieldConfiguration($table, $field) {
		$configuration = array();
		if (isset($GLOBALS['TCA'][$table]['columns'][$field]['config'])) {
			$configuration = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
		}
		return $configuration;
	}

	/**
	 * Whether to ignore restrictions on a web-mount of a table.
	 * The regular behaviour is that records to be accessed need to be
	 * in a valid user's web-mount.
	 *
	 * @param string $table Name of the table
	 * @return boolean
	 */
	static public function isWebMountRestrictionIgnored($table) {
		return !empty($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreWebMountRestriction']);
	}

	/**
	 * Whether to ignore restrictions on root-level records.
	 * The regular behaviour is that records on the root-level (page-id 0)
	 * only can be accessed by admin users.
	 *
	 * @param string $table Name of the table
	 * @return boolean
	 */
	static public function isRootLevelRestrictionIgnored($table) {
		return !empty($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreRootLevelRestriction']);
	}

}


?>
