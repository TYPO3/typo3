<?php
namespace TYPO3\CMS\Core\Integrity;

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
 * Contains a class for evaluation of database integrity according to $GLOBALS['TCA']
 * Most of these functions are considered obsolete!
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * This class holds functions used by the TYPO3 backend to check the integrity of the database (The DBint module, 'lowlevel' extension)
 *
 * Depends on: Depends on \TYPO3\CMS\Core\Database\RelationHandler
 *
 * @todo Need to really extend this class when the tcemain library has been updated and the whole API is better defined. There are some known bugs in this library. Further it would be nice with a facility to not only analyze but also clean up!
 * @see SC_mod_tools_dbint_index::func_relations(), SC_mod_tools_dbint_index::func_records()
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class DatabaseIntegrityCheck {

	/**
	 * @var boolean If set, genTree() includes deleted pages. This is default.
	 * @todo Define visibility
	 */
	public $genTree_includeDeleted = TRUE;

	/**
	 * @var boolean If set, genTree() includes versionized pages/records. This is default.
	 * @todo Define visibility
	 */
	public $genTree_includeVersions = TRUE;

	/**
	 * @var boolean If set, genTree() includes records from pages.
	 * @todo Define visibility
	 */
	public $genTree_includeRecords = FALSE;

	/**
	 * @var string Extra where-clauses for the tree-selection
	 * @todo Define visibility
	 */
	public $perms_clause = '';

	/**
	 * @var int If set, genTree() generates HTML, that visualizes the tree.
	 * @todo Define visibility
	 */
	public $genTree_makeHTML = 0;

	// Internal
	/**
	 * @var array Will hold id/rec pairs from genTree()
	 * @todo Define visibility
	 */
	public $page_idArray = array();

	/**
	 * @var array
	 * @todo Define visibility
	 */
	public $rec_idArray = array();

	/**
	 * @var string  Will hold the HTML-code visualising the tree. genTree()
	 * @todo Define visibility
	 */
	public $genTree_HTML = '';

	/**
	 * @var string
	 * @todo Define visibility
	 */
	public $backPath = '';

	// Internal
	/**
	 * @var array
	 * @todo Define visibility
	 */
	public $checkFileRefs = array();

	/**
	 * @var array From the select-fields
	 * @todo Define visibility
	 */
	public $checkSelectDBRefs = array();

	/**
	 * @var array From the group-fields
	 * @todo Define visibility
	 */
	public $checkGroupDBRefs = array();

	/**
	 * @var array Statistics
	 * @todo Define visibility
	 */
	public $recStats = array(
		'allValid' => array(),
		'published_versions' => array(),
		'deleted' => array()
	);

	/**
	 * @var array
	 * @todo Define visibility
	 */
	public $lRecords = array();

	/**
	 * @var string
	 * @todo Define visibility
	 */
	public $lostPagesList = '';

	/**
	 * Generates a list of Page-uid's that corresponds to the tables in the tree.
	 * This list should ideally include all records in the pages-table.
	 *
	 * @param integer $theID a pid (page-record id) from which to start making the tree
	 * @param string $depthData HTML-code (image-tags) used when this function calls itself recursively.
	 * @param boolean $versions Internal variable, don't set from outside!
	 * @return void
	 * @todo Define visibility
	 */
	public function genTree($theID, $depthData, $versions = FALSE) {
		if ($versions) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title,doktype,deleted,t3ver_wsid,t3ver_id,t3ver_count' . (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms') ? ',hidden' : ''), 'pages', 'pid=-1 AND t3ver_oid=' . intval($theID) . ' ' . (!$this->genTree_includeDeleted ? 'AND deleted=0' : '') . $this->perms_clause, '', 'sorting');
		} else {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title,doktype,deleted' . (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms') ? ',hidden' : ''), 'pages', 'pid=' . intval($theID) . ' ' . (!$this->genTree_includeDeleted ? 'AND deleted=0' : '') . $this->perms_clause, '', 'sorting');
		}
		// Traverse the records selected:
		$a = 0;
		$c = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Prepare the additional label used in the HTML output in case of versions:
			if ($versions) {
				$versionLabel = '[v1.' . $row['t3ver_id'] . '; WS#' . $row['t3ver_wsid'] . ']';
			} else {
				$versionLabel = '';
			}
			$a++;
			$newID = $row['uid'];
			// Build HTML output:
			if ($this->genTree_makeHTML) {
				$this->genTree_HTML .= LF . '<div><span class="nobr">';
				$PM = 'join';
				$LN = $a == $c ? 'blank' : 'line';
				$BTM = $a == $c ? 'bottom' : '';
				$this->genTree_HTML .= $depthData . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . $PM . $BTM . '.gif'), 'width="18" height="16"') . ' align="top" alt="" />' . $versionLabel . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $row) . htmlspecialchars(($row['uid'] . ': ' . \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(strip_tags($row['title']), 50))) . '</span></div>';
			}
			// Register various data for this item:
			$this->page_idArray[$newID] = $row;
			$this->recStats['all_valid']['pages'][$newID] = $newID;
			if ($row['deleted']) {
				$this->recStats['deleted']['pages'][$newID] = $newID;
			}
			if ($versions && $row['t3ver_count'] >= 1) {
				$this->recStats['published_versions']['pages'][$newID] = $newID;
			}
			if ($row['deleted']) {
				$this->recStats['deleted']++;
			}
			if ($row['hidden']) {
				$this->recStats['hidden']++;
			}
			$this->recStats['doktype'][$row['doktype']]++;
			// Create the HTML code prefix for recursive call:
			$genHTML = $depthData . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . $LN . '.gif'), 'width="18" height="16"') . ' align="top" alt="" />' . $versionLabel;
			// If all records should be shown, do so:
			if ($this->genTree_includeRecords) {
				foreach ($GLOBALS['TCA'] as $tableName => $cfg) {
					if ($tableName != 'pages') {
						$this->genTree_records($newID, $this->genTree_HTML ? $genHTML : '', $tableName);
					}
				}
			}
			// Add sub pages:
			$this->genTree($newID, $this->genTree_HTML ? $genHTML : '');
			// If versions are included in the tree, add those now:
			if ($this->genTree_includeVersions) {
				$this->genTree($newID, $this->genTree_HTML ? $genHTML : '', TRUE);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * @param integer $theID a pid (page-record id) from which to start making the tree
	 * @param string $depthData HTML-code used when this function calls itself recursively.
	 * @param string $table Table to get the records from
	 * @param boolean $versions Internal variable, don't set from outside!
	 * @return 	void
	 * @todo Define visibility
	 */
	public function genTree_records($theID, $depthData, $table = '', $versions = FALSE) {
		if ($versions) {
			// Select all records from table pointing to this page:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(\TYPO3\CMS\Backend\Utility\BackendUtility::getCommonSelectFields($table), $table, 'pid=-1 AND t3ver_oid=' . intval($theID) . (!$this->genTree_includeDeleted ? \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table) : ''));
		} else {
			// Select all records from table pointing to this page:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(\TYPO3\CMS\Backend\Utility\BackendUtility::getCommonSelectFields($table), $table, 'pid=' . intval($theID) . (!$this->genTree_includeDeleted ? \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table) : ''));
		}
		// Traverse selected:
		$a = 0;
		$c = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Prepare the additional label used in the HTML output in case of versions:
			if ($versions) {
				$versionLabel = '[v1.' . $row['t3ver_id'] . '; WS#' . $row['t3ver_wsid'] . ']';
			} else {
				$versionLabel = '';
			}
			$a++;
			$newID = $row['uid'];
			// Build HTML output:
			if ($this->genTree_makeHTML) {
				$this->genTree_HTML .= LF . '<div><span class="nobr">';
				$PM = 'join';
				$LN = $a == $c ? 'blank' : 'line';
				$BTM = $a == $c ? 'bottom' : '';
				$this->genTree_HTML .= $depthData . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . $PM . $BTM . '.gif'), 'width="18" height="16"') . ' align="top" alt="" />' . $versionLabel . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $row, array('title' => $table)) . htmlspecialchars(($row['uid'] . ': ' . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $row))) . '</span></div>';
			}
			// Register various data for this item:
			$this->rec_idArray[$table][$newID] = $row;
			$this->recStats['all_valid'][$table][$newID] = $newID;
			if ($row['deleted']) {
				$this->recStats['deleted'][$table][$newID] = $newID;
			}
			if ($versions && $row['t3ver_count'] >= 1 && $row['t3ver_wsid'] == 0) {
				$this->recStats['published_versions'][$table][$newID] = $newID;
			}
			// Select all versions of this record:
			if ($this->genTree_includeVersions && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
				$genHTML = $depthData . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . $LN . '.gif'), 'width="18" height="16"') . ' align="top" alt="" />';
				$this->genTree_records($newID, $genHTML, $table, TRUE);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * Generates tree and returns statistics
	 *
	 * @param integer $root
	 * @return array Record statistics
	 * @deprecated and unused since 6.0, will be removed two versions later
	 * @todo Define visibility
	 */
	public function genTreeStatus($root = 0) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->genTree_includeDeleted = TRUE;
		// if set, genTree() includes deleted pages. This is default.
		$this->genTree_includeVersions = TRUE;
		// if set, genTree() includes verisonized pages/records. This is default.
		$this->genTree_includeRecords = TRUE;
		// if set, genTree() includes records from pages.
		$this->perms_clause = '';
		// extra where-clauses for the tree-selection
		$this->genTree_makeHTML = 0;
		// if set, genTree() generates HTML, that visualizes the tree.
		$this->genTree($root, '');
		return $this->recStats;
	}

	/**
	 * Fills $this->lRecords with the records from all tc-tables that are not attached to a PID in the pid-list.
	 *
	 * @param string $pid_list list of pid's (page-record uid's). This list is probably made by genTree()
	 * @return void
	 * @todo Define visibility
	 */
	public function lostRecords($pid_list) {
		$this->lostPagesList = '';
		if ($pid_list) {
			foreach ($GLOBALS['TCA'] as $table => $tableConf) {
				$pid_list_tmp = $pid_list;
				if (!isset($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) || !$GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
					// Remove preceding "-1," for non-versioned tables
					$pid_list_tmp = preg_replace('/^\\-1,/', '', $pid_list_tmp);
				}
				$garbage = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,' . $GLOBALS['TCA'][$table]['ctrl']['label'], $table, 'pid NOT IN (' . $pid_list_tmp . ')');
				$lostIdList = array();
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($garbage)) {
					$this->lRecords[$table][$row['uid']] = array(
						'uid' => $row['uid'],
						'pid' => $row['pid'],
						'title' => strip_tags(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $row))
					);
					$lostIdList[] = $row['uid'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($garbage);
				if ($table == 'pages') {
					$this->lostPagesList = implode(',', $lostIdList);
				}
			}
		}
	}

	/**
	 * Fixes lost record from $table with uid $uid by setting the PID to zero.
	 * If there is a disabled column for the record that will be set as well.
	 *
	 * @param string $table Database tablename
	 * @param integer $uid The uid of the record which will have the PID value set to 0 (zero)
	 * @return boolean TRUE if done.
	 * @todo Define visibility
	 */
	public function fixLostRecord($table, $uid) {
		if ($table && $GLOBALS['TCA'][$table] && $uid && is_array($this->lRecords[$table][$uid]) && $GLOBALS['BE_USER']->user['admin']) {
			$updateFields = array();
			$updateFields['pid'] = 0;
			// If possible a lost record restored is hidden as default
			if ($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']) {
				$updateFields[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']] = 1;
			}
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($uid), $updateFields);
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Counts records from $GLOBALS['TCA']-tables that ARE attached to an existing page.
	 *
	 * @param string $pid_list list of pid's (page-record uid's). This list is probably made by genTree()
	 * @return array an array with the number of records from all $GLOBALS['TCA']-tables that are attached to a PID in the pid-list.
	 * @todo Define visibility
	 */
	public function countRecords($pid_list) {
		$list = array();
		$list_n = array();
		if ($pid_list) {
			foreach ($GLOBALS['TCA'] as $table => $tableConf) {
				$pid_list_tmp = $pid_list;
				if (!isset($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) || !$GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
					// Remove preceding "-1," for non-versioned tables
					$pid_list_tmp = preg_replace('/^\\-1,/', '', $pid_list_tmp);
				}
				$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $table, 'pid IN (' . $pid_list_tmp . ')');
				if ($count) {
					$list[$table] = $count;
				}
				$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $table, 'pid IN (' . $pid_list_tmp . ')' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table));
				if ($count) {
					$list_n[$table] = $count;
				}
			}
		}
		return array('all' => $list, 'non_deleted' => $list_n);
	}

	/**
	 * Finding relations in database based on type 'group' (files or database-uid's in a list)
	 *
	 * @param string $mode $mode = file, $mode = db, $mode = '' (all...)
	 * @return array An array with all fields listed that somehow are references to other records (foreign-keys) or files
	 * @todo Define visibility
	 */
	public function getGroupFields($mode) {
		$result = array();
		foreach ($GLOBALS['TCA'] as $table => $tableConf) {
			$cols = $GLOBALS['TCA'][$table]['columns'];
			foreach ($cols as $field => $config) {
				if ($config['config']['type'] == 'group') {
					if ((!$mode || $mode == 'file') && $config['config']['internal_type'] == 'file' || (!$mode || $mode == 'db') && $config['config']['internal_type'] == 'db') {
						$result[$table][] = $field;
					}
				}
				if ((!$mode || $mode == 'db') && $config['config']['type'] == 'select' && $config['config']['foreign_table']) {
					$result[$table][] = $field;
				}
			}
			if ($result[$table]) {
				$result[$table] = implode(',', $result[$table]);
			}
		}
		return $result;
	}

	/**
	 * Finds all fields that hold filenames from uploadfolder
	 *
	 * @param string $uploadfolder Path to uploadfolder
	 * @return array An array with all fields listed that have references to files in the $uploadfolder
	 * @todo Define visibility
	 */
	public function getFileFields($uploadfolder) {
		$result = array();
		foreach ($GLOBALS['TCA'] as $table => $tableConf) {
			$cols = $GLOBALS['TCA'][$table]['columns'];
			foreach ($cols as $field => $config) {
				if ($config['config']['type'] == 'group' && $config['config']['internal_type'] == 'file' && $config['config']['uploadfolder'] == $uploadfolder) {
					$result[] = array($table, $field);
				}
			}
		}
		return $result;
	}

	/**
	 * Returns an array with arrays of table/field pairs which are allowed to hold references to the input table name - according to $GLOBALS['TCA']
	 *
	 * @param string $theSearchTable Table name
	 * @return array
	 * @todo Define visibility
	 */
	public function getDBFields($theSearchTable) {
		$result = array();
		foreach ($GLOBALS['TCA'] as $table => $tableConf) {
			$cols = $GLOBALS['TCA'][$table]['columns'];
			foreach ($cols as $field => $config) {
				if ($config['config']['type'] == 'group' && $config['config']['internal_type'] == 'db') {
					if (trim($config['config']['allowed']) == '*' || strstr($config['config']['allowed'], $theSearchTable)) {
						$result[] = array($table, $field);
					}
				} elseif ($config['config']['type'] == 'select' && $config['config']['foreign_table'] == $theSearchTable) {
					$result[] = array($table, $field);
				}
			}
		}
		return $result;
	}

	/**
	 * This selects non-empty-records from the tables/fields in the fkey_array generated by getGroupFields()
	 *
	 * @param array $fkey_arrays Array with tables/fields generated by getGroupFields()
	 * @return void
	 * @see getGroupFields()
	 * @todo Define visibility
	 */
	public function selectNonEmptyRecordsWithFkeys($fkey_arrays) {
		if (is_array($fkey_arrays)) {
			foreach ($fkey_arrays as $table => $field_list) {
				if ($GLOBALS['TCA'][$table] && trim($field_list)) {
					$fieldArr = explode(',', $field_list);
					if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal')) {
						$fields = $GLOBALS['TYPO3_DB']->admin_get_fields($table);
						$field = array_shift($fieldArr);
						$cl_fl = $GLOBALS['TYPO3_DB']->MetaType($fields[$field]['type'], $table) == 'I' || $GLOBALS['TYPO3_DB']->MetaType($fields[$field]['type'], $table) == 'N' || $GLOBALS['TYPO3_DB']->MetaType($fields[$field]['type'], $table) == 'R' ? $field . '<>0' : $field . '<>\'\'';
						foreach ($fieldArr as $field) {
							$cl_fl .= $GLOBALS['TYPO3_DB']->MetaType($fields[$field]['type'], $table) == 'I' || $GLOBALS['TYPO3_DB']->MetaType($fields[$field]['type'], $table) == 'N' || $GLOBALS['TYPO3_DB']->MetaType($fields[$field]['type'], $table) == 'R' ? ' OR ' . $field . '<>0' : ' OR ' . $field . '<>\'\'';
						}
						unset($fields);
					} else {
						$cl_fl = implode('<>\'\' OR ', $fieldArr) . '<>\'\'';
					}
					$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,' . $field_list, $table, $cl_fl);
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
						foreach ($fieldArr as $field) {
							if (trim($row[$field])) {
								$fieldConf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
								if ($fieldConf['type'] == 'group') {
									if ($fieldConf['internal_type'] == 'file') {
										// Files...
										if ($fieldConf['MM']) {
											$tempArr = array();
											/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
											$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
											$dbAnalysis->start('', 'files', $fieldConf['MM'], $row['uid']);
											foreach ($dbAnalysis->itemArray as $somekey => $someval) {
												if ($someval['id']) {
													$tempArr[] = $someval['id'];
												}
											}
										} else {
											$tempArr = explode(',', trim($row[$field]));
										}
										foreach ($tempArr as $file) {
											$file = trim($file);
											if ($file) {
												$this->checkFileRefs[$fieldConf['uploadfolder']][$file] += 1;
											}
										}
									}
									if ($fieldConf['internal_type'] == 'db') {
										/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
										$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
										$dbAnalysis->start($row[$field], $fieldConf['allowed'], $fieldConf['MM'], $row['uid'], $table, $fieldConf);
										foreach ($dbAnalysis->itemArray as $tempArr) {
											$this->checkGroupDBRefs[$tempArr['table']][$tempArr['id']] += 1;
										}
									}
								}
								if ($fieldConf['type'] == 'select' && $fieldConf['foreign_table']) {
									/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
									$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
									$dbAnalysis->start($row[$field], $fieldConf['foreign_table'], $fieldConf['MM'], $row['uid'], $table, $fieldConf);
									foreach ($dbAnalysis->itemArray as $tempArr) {
										if ($tempArr['id'] > 0) {
											$this->checkGroupDBRefs[$fieldConf['foreign_table']][$tempArr['id']] += 1;
										}
									}
								}
							}
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($mres);
				}
			}
		}
	}

	/**
	 * Depends on selectNonEmpty.... to be executed first!!
	 *
	 * @return array Report over files; keys are "moreReferences", "noReferences", "noFile", "error
	 * @todo Define visibility
	 */
	public function testFileRefs() {
		$output = array();
		// Handle direct references with upload folder setting (workaround)
		$newCheckFileRefs = array();
		foreach ($this->checkFileRefs as $folder => $files) {
			// Only direct references without a folder setting
			if ($folder !== '') {
				$newCheckFileRefs[$folder] = $files;
				continue;
			}
			foreach ($files as $file => $references) {
				// Direct file references have often many references (removes occurences in the moreReferences section of the result array)
				if ($references > 1) {
					$references = 1;
				}
				// The directory must be empty (prevents checking of the root directory)
				$directory = dirname($file);
				if ($directory !== '') {
					$newCheckFileRefs[$directory][basename($file)] = $references;
				}
			}
		}
		$this->checkFileRefs = $newCheckFileRefs;
		foreach ($this->checkFileRefs as $folder => $fileArr) {
			$path = PATH_site . $folder;
			if (@is_dir($path)) {
				$d = dir($path);
				while ($entry = $d->read()) {
					if (@is_file(($path . '/' . $entry))) {
						if (isset($fileArr[$entry])) {
							if ($fileArr[$entry] > 1) {
								$temp = $this->whereIsFileReferenced($folder, $entry);
								$tempList = '';
								foreach ($temp as $inf) {
									$tempList .= '[' . $inf['table'] . '][' . $inf['uid'] . '][' . $inf['field'] . '] (pid:' . $inf['pid'] . ') - ';
								}
								$output['moreReferences'][] = array($path, $entry, $fileArr[$entry], $tempList);
							}
							unset($fileArr[$entry]);
						} else {
							// Contains workaround for direct references
							if (!strstr($entry, 'index.htm') && !preg_match(('/^' . preg_quote($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') . '/'), $folder)) {
								$output['noReferences'][] = array($path, $entry);
							}
						}
					}
				}
				$d->close();
				$tempCounter = 0;
				foreach ($fileArr as $file => $value) {
					// Workaround for direct file references
					if (preg_match('/^' . preg_quote($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') . '/', $folder)) {
						$file = $folder . '/' . $file;
						$folder = '';
						$path = substr(PATH_site, 0, -1);
					}
					$temp = $this->whereIsFileReferenced($folder, $file);
					$tempList = '';
					foreach ($temp as $inf) {
						$tempList .= '[' . $inf['table'] . '][' . $inf['uid'] . '][' . $inf['field'] . '] (pid:' . $inf['pid'] . ') - ';
					}
					$tempCounter++;
					$output['noFile'][substr($path, -3) . '_' . substr($file, 0, 3) . '_' . $tempCounter] = array($path, $file, $tempList);
				}
			} else {
				$output['error'][] = array($path);
			}
		}
		return $output;
	}

	/**
	 * Depends on selectNonEmpty.... to be executed first!!
	 *
	 * @param array $theArray Table with key/value pairs being table names and arrays with uid numbers
	 * @return string HTML Error message
	 * @todo Define visibility
	 */
	public function testDBRefs($theArray) {
		$result = '';
		foreach ($theArray as $table => $dbArr) {
			if ($GLOBALS['TCA'][$table]) {
				$idlist = array_keys($dbArr);
				$theList = implode(',', $idlist);
				if ($theList) {
					$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'uid IN (' . $theList . ')' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table));
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
						if (isset($dbArr[$row['uid']])) {
							unset($dbArr[$row['uid']]);
						} else {
							$result .= 'Strange Error. ...<br />';
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($mres);
					foreach ($dbArr as $theId => $theC) {
						$result .= 'There are ' . $theC . ' records pointing to this missing or deleted record; [' . $table . '][' . $theId . ']<br />';
					}
				}
			} else {
				$result .= 'Codeerror. Table is not a table...<br />';
			}
		}
		return $result;
	}

	/**
	 * Finding all references to record based on table/uid
	 *
	 * @param string $searchTable Table name
	 * @param integer $id Uid of database record
	 * @return array Array with other arrays containing information about where references was found
	 * @todo Define visibility
	 */
	public function whereIsRecordReferenced($searchTable, $id) {
		// Gets tables / Fields that reference to files
		$fileFields = $this->getDBFields($searchTable);
		$theRecordList = array();
		foreach ($fileFields as $info) {
			$table = $info[0];
			$field = $info[1];
			$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,' . $GLOBALS['TCA'][$table]['ctrl']['label'] . ',' . $field, $table, $field . ' LIKE \'%' . $GLOBALS['TYPO3_DB']->quoteStr($id, $table) . '%\'');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
				// Now this is the field, where the reference COULD come from. But we're not garanteed, so we must carefully examine the data.
				$fieldConf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
				$allowedTables = $fieldConf['type'] == 'group' ? $fieldConf['allowed'] : $fieldConf['foreign_table'];
				/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
				$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
				$dbAnalysis->start($row[$field], $allowedTables, $fieldConf['MM'], $row['uid'], $table, $fieldConf);
				foreach ($dbAnalysis->itemArray as $tempArr) {
					if ($tempArr['table'] == $searchTable && $tempArr['id'] == $id) {
						$theRecordList[] = array('table' => $table, 'uid' => $row['uid'], 'field' => $field, 'pid' => $row['pid']);
					}
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($mres);
		}
		return $theRecordList;
	}

	/**
	 * Finding all references to file based on uploadfolder / filename
	 *
	 * @param string $uploadfolder Upload folder where file is found
	 * @param string $filename Filename to search for
	 * @return array Array with other arrays containing information about where references was found
	 * @todo Define visibility
	 */
	public function whereIsFileReferenced($uploadfolder, $filename) {
		// Gets tables / Fields that reference to files
		$fileFields = $this->getFileFields($uploadfolder);
		$theRecordList = array();
		foreach ($fileFields as $info) {
			$table = $info[0];
			$field = $info[1];
			$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,' . $GLOBALS['TCA'][$table]['ctrl']['label'] . ',' . $field, $table, $field . ' LIKE \'%' . $GLOBALS['TYPO3_DB']->quoteStr($filename, $table) . '%\'');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
				// Now this is the field, where the reference COULD come from.
				// But we're not guaranteed, so we must carefully examine the data.
				$tempArr = explode(',', trim($row[$field]));
				foreach ($tempArr as $file) {
					$file = trim($file);
					if ($file == $filename) {
						$theRecordList[] = array('table' => $table, 'uid' => $row['uid'], 'field' => $field, 'pid' => $row['pid']);
					}
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($mres);
		}
		return $theRecordList;
	}

}


?>