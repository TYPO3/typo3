<?php
namespace TYPO3\CMS\Core\Database;

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
 * Contains class for loading database groups
 *
 * Revised for TYPO3 3.6 September/2003 by Kasper Skårhøj
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Load database groups (relations)
 * Used to process the relations created by the TCA element types "group" and "select" for database records. Manages MM-relations as well.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class RelationHandler {

	/**
	 * @var boolean $fetchAllFields if false getFromDB() fetches only uid, pid, thumbnail and label fields (as defined in TCA)
	 */
	protected $fetchAllFields = FALSE;

	// If set, values that are not ids in tables are normally discarded. By this options they will be preserved.
	/**
	 * @todo Define visibility
	 */
	public $registerNonTableValues = 0;

	// Internal, dynamic:
	// Contains the table names as keys. The values are the id-values for each table. Should ONLY contain proper table names.
	/**
	 * @todo Define visibility
	 */
	public $tableArray = array();

	// Contains items in an numeric array (table/id for each). Tablenames here might be "_NO_TABLE"
	/**
	 * @todo Define visibility
	 */
	public $itemArray = array();

	// Array for NON-table elements
	/**
	 * @todo Define visibility
	 */
	public $nonTableArray = array();

	/**
	 * @todo Define visibility
	 */
	public $additionalWhere = array();

	// Deleted-column is added to additionalWhere... if this is set...
	/**
	 * @todo Define visibility
	 */
	public $checkIfDeleted = 1;

	/**
	 * @todo Define visibility
	 */
	public $dbPaths = array();

	// Will contain the first table name in the $tablelist (for positive ids)
	/**
	 * @todo Define visibility
	 */
	public $firstTable = '';

	// Will contain the second table name in the $tablelist (for negative ids)
	/**
	 * @todo Define visibility
	 */
	public $secondTable = '';

	// private
	// Boolean - if 1, uid_local and uid_foreign are switched, and the current table is inserted as tablename - this means you display a foreign relation "from the opposite side"
	/**
	 * @todo Define visibility
	 */
	public $MM_is_foreign = 0;

	// Field name at the "local" side of the MM relation
	/**
	 * @todo Define visibility
	 */
	public $MM_oppositeField = '';

	// Only set if MM_is_foreign is set
	/**
	 * @todo Define visibility
	 */
	public $MM_oppositeTable = '';

	// Only set if MM_is_foreign is set
	/**
	 * @todo Define visibility
	 */
	public $MM_oppositeFieldConf = '';

	// Is empty by default; if MM_is_foreign is set and there is more than one table allowed (on the "local" side), then it contains the first table (as a fallback)
	/**
	 * @todo Define visibility
	 */
	public $MM_isMultiTableRelationship = 0;

	// Current table => Only needed for reverse relations
	/**
	 * @todo Define visibility
	 */
	public $currentTable;

	// If a record should be undeleted (so do not use the $useDeleteClause on \TYPO3\CMS\Backend\Utility\BackendUtility)
	/**
	 * @todo Define visibility
	 */
	public $undeleteRecord;

	// Array of fields value pairs that should match while SELECT and will be written into MM table if $MM_insert_fields is not set
	/**
	 * @todo Define visibility
	 */
	public $MM_match_fields = array();

	// Array of fields and value pairs used for insert in MM table
	/**
	 * @todo Define visibility
	 */
	public $MM_insert_fields = array();

	// Extra MM table where
	/**
	 * @todo Define visibility
	 */
	public $MM_table_where = '';

	/**
	 * @var boolean
	 */
	protected $updateReferenceIndex = TRUE;

	/**
	 * Initialization of the class.
	 *
	 * @param string $itemlist List of group/select items
	 * @param string $tablelist Comma list of tables, first table takes priority if no table is set for an entry in the list.
	 * @param string $MMtable Name of a MM table.
	 * @param integer $MMuid Local UID for MM lookup
	 * @param string $currentTable Current table name
	 * @param integer $conf TCA configuration for current field
	 * @return void
	 * @todo Define visibility
	 */
	public function start($itemlist, $tablelist, $MMtable = '', $MMuid = 0, $currentTable = '', $conf = array()) {
		// SECTION: MM reverse relations
		$this->MM_is_foreign = $conf['MM_opposite_field'] ? 1 : 0;
		$this->MM_oppositeField = $conf['MM_opposite_field'];
		$this->MM_table_where = $conf['MM_table_where'];
		$this->MM_hasUidField = $conf['MM_hasUidField'];
		$this->MM_match_fields = is_array($conf['MM_match_fields']) ? $conf['MM_match_fields'] : array();
		$this->MM_insert_fields = is_array($conf['MM_insert_fields']) ? $conf['MM_insert_fields'] : $this->MM_match_fields;
		$this->currentTable = $currentTable;
		if ($this->MM_is_foreign) {
			$tmp = $conf['type'] === 'group' ? $conf['allowed'] : $conf['foreign_table'];
			// Normally, $conf['allowed'] can contain a list of tables, but as we are looking at a MM relation from the foreign side, it only makes sense to allow one one table in $conf['allowed']
			$tmp = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tmp);
			$this->MM_oppositeTable = $tmp[0];
			unset($tmp);
			// Only add the current table name if there is more than one allowed field
			// We must be sure this has been done at least once before accessing the "columns" part of TCA for a table.
			$this->MM_oppositeFieldConf = $GLOBALS['TCA'][$this->MM_oppositeTable]['columns'][$this->MM_oppositeField]['config'];
			if ($this->MM_oppositeFieldConf['allowed']) {
				$oppositeFieldConf_allowed = explode(',', $this->MM_oppositeFieldConf['allowed']);
				if (count($oppositeFieldConf_allowed) > 1 || $this->MM_oppositeFieldConf['allowed'] === '*') {
					$this->MM_isMultiTableRelationship = $oppositeFieldConf_allowed[0];
				}
			}
		}
		// SECTION:	normal MM relations
		// If the table list is "*" then all tables are used in the list:
		if (!strcmp(trim($tablelist), '*')) {
			$tablelist = implode(',', array_keys($GLOBALS['TCA']));
		}
		// The tables are traversed and internal arrays are initialized:
		$tempTableArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tablelist, 1);
		foreach ($tempTableArray as $key => $val) {
			$tName = trim($val);
			$this->tableArray[$tName] = array();
			if ($this->checkIfDeleted && $GLOBALS['TCA'][$tName]['ctrl']['delete']) {
				$fieldN = $tName . '.' . $GLOBALS['TCA'][$tName]['ctrl']['delete'];
				$this->additionalWhere[$tName] .= ' AND ' . $fieldN . '=0';
			}
		}
		if (is_array($this->tableArray)) {
			reset($this->tableArray);
		} else {
			return 'No tables!';
		}
		// Set first and second tables:
		// Is the first table
		$this->firstTable = key($this->tableArray);
		next($this->tableArray);
		// If the second table is set and the ID number is less than zero (later) then the record is regarded to come from the second table...
		$this->secondTable = key($this->tableArray);
		// Now, populate the internal itemArray and tableArray arrays:
		// If MM, then call this function to do that:
		if ($MMtable) {
			if ($MMuid) {
				$this->readMM($MMtable, $MMuid);
			} else {
				// Revert to readList() for new records in order to load possible default values from $itemlist
				$this->readList($itemlist);
			}
		} elseif ($MMuid && $conf['foreign_field']) {
			// If not MM but foreign_field, the read the records by the foreign_field
			$this->readForeignField($MMuid, $conf);
		} else {
			// If not MM, then explode the itemlist by "," and traverse the list:
			$this->readList($itemlist);
			// Do automatic default_sortby, if any
			if ($conf['foreign_default_sortby']) {
				$this->sortList($conf['foreign_default_sortby']);
			}
		}
	}

	/**
	 * Magic setter method.
	 * Used for compatibility with changed attribute visibility
	 *
	 * @param string $name name of the attribute
	 * @param mixed $value value to set the attribute to
	 * @deprecated since 6.1, only required as compatibility layer for renamed attribute $fromTC
	 */
	public function __set($name, $value) {
		if($name === 'fromTC') {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
				'$fromTC is protected since TYPO3 6.1. Use setFetchAllFields() instead!'
			);
			$this->setFetchAllFields(!$value);
		}
	}

	/**
	 * Sets $fetchAllFields
	 *
	 * @param boolean $allFields enables fetching of all fields in getFromDB()
	 */
	public function setFetchAllFields($allFields) {
		$this->fetchAllFields = (bool)$allFields;
	}

	/**
	 * Sets whether the reference index shall be updated.
	 *
	 * @param boolean $updateReferenceIndex Whether the reference index shall be updated
	 * @return void
	 */
	public function setUpdateReferenceIndex($updateReferenceIndex) {
		$this->updateReferenceIndex = (bool) $updateReferenceIndex;
	}

	/**
	 * Explodes the item list and stores the parts in the internal arrays itemArray and tableArray from MM records.
	 *
	 * @param string $itemlist Item list
	 * @return void
	 * @todo Define visibility
	 */
	public function readList($itemlist) {
		if ((string) trim($itemlist) != '') {
			$tempItemArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $itemlist);
			// Changed to trimExplode 31/3 04; HMENU special type "list" didn't work if there were spaces in the list... I suppose this is better overall...
			foreach ($tempItemArray as $key => $val) {
				// Will be set to "1" if the entry was a real table/id:
				$isSet = 0;
				// Extract table name and id. This is un the formular [tablename]_[id] where table name MIGHT contain "_", hence the reversion of the string!
				$val = strrev($val);
				$parts = explode('_', $val, 2);
				$theID = strrev($parts[0]);
				// Check that the id IS an integer:
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($theID)) {
					// Get the table name: If a part of the exploded string, use that. Otherwise if the id number is LESS than zero, use the second table, otherwise the first table
					$theTable = trim($parts[1]) ? strrev(trim($parts[1])) : ($this->secondTable && $theID < 0 ? $this->secondTable : $this->firstTable);
					// If the ID is not blank and the table name is among the names in the inputted tableList, then proceed:
					if (((string) $theID != '' && $theID) && $theTable && isset($this->tableArray[$theTable])) {
						// Get ID as the right value:
						$theID = $this->secondTable ? abs(intval($theID)) : intval($theID);
						// Register ID/table name in internal arrays:
						$this->itemArray[$key]['id'] = $theID;
						$this->itemArray[$key]['table'] = $theTable;
						$this->tableArray[$theTable][] = $theID;
						// Set update-flag:
						$isSet = 1;
					}
				}
				// If it turns out that the value from the list was NOT a valid reference to a table-record, then we might still set it as a NO_TABLE value:
				if (!$isSet && $this->registerNonTableValues) {
					$this->itemArray[$key]['id'] = $tempItemArray[$key];
					$this->itemArray[$key]['table'] = '_NO_TABLE';
					$this->nonTableArray[] = $tempItemArray[$key];
				}
			}
		}
	}

	/**
	 * Does a sorting on $this->itemArray depending on a default sortby field.
	 * This is only used for automatic sorting of comma separated lists.
	 * This function is only relevant for data that is stored in comma separated lists!
	 *
	 * @param string $sortby The default_sortby field/command (e.g. 'price DESC')
	 * @return void
	 * @todo Define visibility
	 */
	public function sortList($sortby) {
		// Sort directly without fetching addional data
		if ($sortby == 'uid') {
			usort($this->itemArray, create_function('$a,$b', 'return $a["id"] < $b["id"] ? -1 : 1;'));
		} elseif (count($this->tableArray) == 1) {
			reset($this->tableArray);
			$table = key($this->tableArray);
			$uidList = implode(',', current($this->tableArray));
			if ($uidList) {
				$this->itemArray = array();
				$this->tableArray = array();
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'uid IN (' . $uidList . ')', '', $sortby);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$this->itemArray[] = array('id' => $row['uid'], 'table' => $table);
					$this->tableArray[$table][] = $row['uid'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
	}

	/**
	 * Reads the record tablename/id into the internal arrays itemArray and tableArray from MM records.
	 * You can call this function after start if you supply no list to start()
	 *
	 * @param string $tableName MM Tablename
	 * @param integer $uid Local UID
	 * @return void
	 * @todo Define visibility
	 */
	public function readMM($tableName, $uid) {
		$key = 0;
		$additionalWhere = '';
		// In case of a reverse relation
		if ($this->MM_is_foreign) {
			$uidLocal_field = 'uid_foreign';
			$uidForeign_field = 'uid_local';
			$sorting_field = 'sorting_foreign';
			if ($this->MM_isMultiTableRelationship) {
				$additionalWhere .= ' AND ( tablenames=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->currentTable, $tableName);
				// Be backwards compatible! When allowing more than one table after having previously allowed only one table, this case applies.
				if ($this->currentTable == $this->MM_isMultiTableRelationship) {
					$additionalWhere .= ' OR tablenames=\'\'';
				}
				$additionalWhere .= ' ) ';
			}
			$theTable = $this->MM_oppositeTable;
		} else {
			// Default
			$uidLocal_field = 'uid_local';
			$uidForeign_field = 'uid_foreign';
			$sorting_field = 'sorting';
		}
		if ($this->MM_table_where) {
			$additionalWhere .= LF . str_replace('###THIS_UID###', intval($uid), $this->MM_table_where);
		}
		foreach ($this->MM_match_fields as $field => $value) {
			$additionalWhere .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $tableName);
		}
		// Select all MM relations:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $tableName, $uidLocal_field . '=' . intval($uid) . $additionalWhere, '', $sorting_field);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Default
			if (!$this->MM_is_foreign) {
				// If tablesnames columns exists and contain a name, then this value is the table, else it's the firstTable...
				$theTable = $row['tablenames'] ? $row['tablenames'] : $this->firstTable;
			}
			if (($row[$uidForeign_field] || $theTable == 'pages') && $theTable && isset($this->tableArray[$theTable])) {
				$this->itemArray[$key]['id'] = $row[$uidForeign_field];
				$this->itemArray[$key]['table'] = $theTable;
				$this->tableArray[$theTable][] = $row[$uidForeign_field];
			} elseif ($this->registerNonTableValues) {
				$this->itemArray[$key]['id'] = $row[$uidForeign_field];
				$this->itemArray[$key]['table'] = '_NO_TABLE';
				$this->nonTableArray[] = $row[$uidForeign_field];
			}
			$key++;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * Writes the internal itemArray to MM table:
	 *
	 * @param string $MM_tableName MM table name
	 * @param integer $uid Local UID
	 * @param boolean $prependTableName If set, then table names will always be written.
	 * @return void
	 * @todo Define visibility
	 */
	public function writeMM($MM_tableName, $uid, $prependTableName = 0) {
		// In case of a reverse relation
		if ($this->MM_is_foreign) {
			$uidLocal_field = 'uid_foreign';
			$uidForeign_field = 'uid_local';
			$sorting_field = 'sorting_foreign';
		} else {
			// default
			$uidLocal_field = 'uid_local';
			$uidForeign_field = 'uid_foreign';
			$sorting_field = 'sorting';
		}
		// If there are tables...
		$tableC = count($this->tableArray);
		if ($tableC) {
			// Boolean: does the field "tablename" need to be filled?
			$prep = $tableC > 1 || $prependTableName || $this->MM_isMultiTableRelationship ? 1 : 0;
			$c = 0;
			$additionalWhere_tablenames = '';
			if ($this->MM_is_foreign && $prep) {
				$additionalWhere_tablenames = ' AND tablenames=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->currentTable, $MM_tableName);
			}
			$additionalWhere = '';
			// Add WHERE clause if configured
			if ($this->MM_table_where) {
				$additionalWhere .= LF . str_replace('###THIS_UID###', intval($uid), $this->MM_table_where);
			}
			// Select, update or delete only those relations that match the configured fields
			foreach ($this->MM_match_fields as $field => $value) {
				$additionalWhere .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $MM_tableName);
			}
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($uidForeign_field . ($prep ? ', tablenames' : '') . ($this->MM_hasUidField ? ', uid' : ''), $MM_tableName, $uidLocal_field . '=' . $uid . $additionalWhere_tablenames . $additionalWhere, '', $sorting_field);
			$oldMMs = array();
			// This array is similar to $oldMMs but also holds the uid of the MM-records, if any (configured by MM_hasUidField).
			// If the UID is present it will be used to update sorting and delete MM-records.
			// This is necessary if the "multiple" feature is used for the MM relations.
			// $oldMMs is still needed for the in_array() search used to look if an item from $this->itemArray is in $oldMMs
			$oldMMs_inclUid = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if (!$this->MM_is_foreign && $prep) {
					$oldMMs[] = array($row['tablenames'], $row[$uidForeign_field]);
				} else {
					$oldMMs[] = $row[$uidForeign_field];
				}
				$oldMMs_inclUid[] = array($row['tablenames'], $row[$uidForeign_field], $row['uid']);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// For each item, insert it:
			foreach ($this->itemArray as $val) {
				$c++;
				if ($prep || $val['table'] == '_NO_TABLE') {
					// Insert current table if needed
					if ($this->MM_is_foreign) {
						$tablename = $this->currentTable;
					} else {
						$tablename = $val['table'];
					}
				} else {
					$tablename = '';
				}
				if (!$this->MM_is_foreign && $prep) {
					$item = array($val['table'], $val['id']);
				} else {
					$item = $val['id'];
				}
				if (in_array($item, $oldMMs)) {
					$oldMMs_index = array_search($item, $oldMMs);
					// In principle, selecting on the UID is all we need to do if a uid field is available since that is unique! But as long as it "doesn't hurt" we just add it to the where clause. It should all match up.
					$whereClause = $uidLocal_field . '=' . $uid . ' AND ' . $uidForeign_field . '=' . $val['id'] . ($this->MM_hasUidField ? ' AND uid=' . intval($oldMMs_inclUid[$oldMMs_index][2]) : '');
					if ($tablename) {
						$whereClause .= ' AND tablenames=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tablename, $MM_tableName);
					}
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($MM_tableName, $whereClause . $additionalWhere, array($sorting_field => $c));
					// Remove the item from the $oldMMs array so after this foreach loop only the ones that need to be deleted are in there.
					unset($oldMMs[$oldMMs_index]);
					// Remove the item from the $oldMMs array so after this foreach loop only the ones that need to be deleted are in there.
					unset($oldMMs_inclUid[$oldMMs_index]);
				} else {
					$insertFields = $this->MM_insert_fields;
					$insertFields[$uidLocal_field] = $uid;
					$insertFields[$uidForeign_field] = $val['id'];
					$insertFields[$sorting_field] = $c;
					if ($tablename) {
						$insertFields['tablenames'] = $tablename;
					}
					$GLOBALS['TYPO3_DB']->exec_INSERTquery($MM_tableName, $insertFields);
					if ($this->MM_is_foreign) {
						$this->updateRefIndex($val['table'], $val['id']);
					}
				}
			}
			// Delete all not-used relations:
			if (is_array($oldMMs) && count($oldMMs) > 0) {
				$removeClauses = array();
				$updateRefIndex_records = array();
				foreach ($oldMMs as $oldMM_key => $mmItem) {
					// If UID field is present, of course we need only use that for deleting.
					if ($this->MM_hasUidField) {
						$removeClauses[] = 'uid=' . intval($oldMMs_inclUid[$oldMM_key][2]);
						$elDelete = $oldMMs_inclUid[$oldMM_key];
					} else {
						if (is_array($mmItem)) {
							$removeClauses[] = 'tablenames=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($mmItem[0], $MM_tableName) . ' AND ' . $uidForeign_field . '=' . $mmItem[1];
						} else {
							$removeClauses[] = $uidForeign_field . '=' . $mmItem;
						}
					}
					if ($this->MM_is_foreign) {
						if (is_array($mmItem)) {
							$updateRefIndex_records[] = array($mmItem[0], $mmItem[1]);
						} else {
							$updateRefIndex_records[] = array($this->firstTable, $mmItem);
						}
					}
				}
				$deleteAddWhere = ' AND (' . implode(' OR ', $removeClauses) . ')';
				$GLOBALS['TYPO3_DB']->exec_DELETEquery($MM_tableName, $uidLocal_field . '=' . intval($uid) . $deleteAddWhere . $additionalWhere_tablenames . $additionalWhere);
				// Update ref index:
				foreach ($updateRefIndex_records as $pair) {
					$this->updateRefIndex($pair[0], $pair[1]);
				}
			}
			// Update ref index; In tcemain it is not certain that this will happen because if only the MM field is changed the record itself is not updated and so the ref-index is not either.
			// This could also have been fixed in updateDB in tcemain, however I decided to do it here ...
			$this->updateRefIndex($this->currentTable, $uid);
		}
	}

	/**
	 * Remaps MM table elements from one local uid to another
	 * Does NOT update the reference index for you, must be called subsequently to do that!
	 *
	 * @param string $MM_tableName MM table name
	 * @param integer $uid Local, current UID
	 * @param integer $newUid Local, new UID
	 * @param boolean $prependTableName If set, then table names will always be written.
	 * @return void
	 * @todo Define visibility
	 */
	public function remapMM($MM_tableName, $uid, $newUid, $prependTableName = 0) {
		// In case of a reverse relation
		if ($this->MM_is_foreign) {
			$uidLocal_field = 'uid_foreign';
		} else {
			// default
			$uidLocal_field = 'uid_local';
		}
		// If there are tables...
		$tableC = count($this->tableArray);
		if ($tableC) {
			// Boolean: does the field "tablename" need to be filled?
			$prep = $tableC > 1 || $prependTableName || $this->MM_isMultiTableRelationship ? 1 : 0;
			$c = 0;
			$additionalWhere_tablenames = '';
			if ($this->MM_is_foreign && $prep) {
				$additionalWhere_tablenames = ' AND tablenames=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->currentTable, $MM_tableName);
			}
			$additionalWhere = '';
			// Add WHERE clause if configured
			if ($this->MM_table_where) {
				$additionalWhere .= LF . str_replace('###THIS_UID###', intval($uid), $this->MM_table_where);
			}
			// Select, update or delete only those relations that match the configured fields
			foreach ($this->MM_match_fields as $field => $value) {
				$additionalWhere .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $MM_tableName);
			}
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($MM_tableName, $uidLocal_field . '=' . intval($uid) . $additionalWhere_tablenames . $additionalWhere, array($uidLocal_field => $newUid));
		}
	}

	/**
	 * Reads items from a foreign_table, that has a foreign_field (uid of the parent record) and
	 * stores the parts in the internal array itemArray and tableArray.
	 *
	 * @param integer $uid The uid of the parent record (this value is also on the foreign_table in the foreign_field)
	 * @param array $conf TCA configuration for current field
	 * @return void
	 * @todo Define visibility
	 */
	public function readForeignField($uid, $conf) {
		$key = 0;
		$uid = intval($uid);
		$foreign_table = $conf['foreign_table'];
		$foreign_table_field = $conf['foreign_table_field'];
		$useDeleteClause = $this->undeleteRecord ? FALSE : TRUE;
		$foreign_match_fields = is_array($conf['foreign_match_fields']) ? $conf['foreign_match_fields'] : array();
		// Search for $uid in foreign_field, and if we have symmetric relations, do this also on symmetric_field
		if ($conf['symmetric_field']) {
			$whereClause = '(' . $conf['foreign_field'] . '=' . $uid . ' OR ' . $conf['symmetric_field'] . '=' . $uid . ')';
		} else {
			$whereClause = $conf['foreign_field'] . '=' . $uid;
		}
		// Use the deleteClause (e.g. "deleted=0") on this table
		if ($useDeleteClause) {
			$whereClause .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($foreign_table);
		}
		// If it's requested to look for the parent uid AND the parent table,
		// add an additional SQL-WHERE clause
		if ($foreign_table_field && $this->currentTable) {
			$whereClause .= ' AND ' . $foreign_table_field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->currentTable, $foreign_table);
		}
		// Add additional where clause if foreign_match_fields are defined
		foreach ($foreign_match_fields as $field => $value) {
			$whereClause .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $foreign_table);
		}
		// Select children in the same workspace:
		if (\TYPO3\CMS\Backend\Utility\BackendUtility::isTableWorkspaceEnabled($this->currentTable) && \TYPO3\CMS\Backend\Utility\BackendUtility::isTableWorkspaceEnabled($foreign_table)) {
			$currentRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($this->currentTable, $uid, 't3ver_wsid', '', $useDeleteClause);
			$whereClause .= \TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceWhereClause($foreign_table, $currentRecord['t3ver_wsid']);
		}
		// Get the correct sorting field
		// Specific manual sortby for data handled by this field
		if ($conf['foreign_sortby']) {
			if ($conf['symmetric_sortby'] && $conf['symmetric_field']) {
				// Sorting depends on, from which side of the relation we're looking at it
				$sortby = '
					CASE
						WHEN ' . $conf['foreign_field'] . '=' . $uid . '
						THEN ' . $conf['foreign_sortby'] . '
						ELSE ' . $conf['symmetric_sortby'] . '
					END';
			} else {
				// Regular single-side behaviour
				$sortby = $conf['foreign_sortby'];
			}
		} elseif ($conf['foreign_default_sortby']) {
			// Specific default sortby for data handled by this field
			$sortby = $conf['foreign_default_sortby'];
		} elseif ($GLOBALS['TCA'][$foreign_table]['ctrl']['sortby']) {
			// Manual sortby for all table records
			$sortby = $GLOBALS['TCA'][$foreign_table]['ctrl']['sortby'];
		} elseif ($GLOBALS['TCA'][$foreign_table]['ctrl']['default_sortby']) {
			// Default sortby for all table records
			$sortby = $GLOBALS['TCA'][$foreign_table]['ctrl']['default_sortby'];
		}
		// Strip a possible "ORDER BY" in front of the $sortby value
		$sortby = $GLOBALS['TYPO3_DB']->stripOrderBy($sortby);
		// Get the rows from storage
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $foreign_table, $whereClause, '', $sortby);
		if (count($rows)) {
			foreach ($rows as $row) {
				$this->itemArray[$key]['id'] = $row['uid'];
				$this->itemArray[$key]['table'] = $foreign_table;
				$this->tableArray[$foreign_table][] = $row['uid'];
				$key++;
			}
		}
	}

	/**
	 * Write the sorting values to a foreign_table, that has a foreign_field (uid of the parent record)
	 *
	 * @param array $conf TCA configuration for current field
	 * @param integer $parentUid The uid of the parent record
	 * @param boolean $updateToUid Whether to update the foreign field with the $parentUid (on Copy)
	 * @param boolean $skipSorting Do not update the sorting columns, this could happen for imported values
	 * @return void
	 * @todo Define visibility
	 */
	public function writeForeignField($conf, $parentUid, $updateToUid = 0, $skipSorting = FALSE) {
		$c = 0;
		$foreign_table = $conf['foreign_table'];
		$foreign_field = $conf['foreign_field'];
		$symmetric_field = $conf['symmetric_field'];
		$foreign_table_field = $conf['foreign_table_field'];
		$foreign_match_fields = is_array($conf['foreign_match_fields']) ? $conf['foreign_match_fields'] : array();
		// If there are table items and we have a proper $parentUid
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($parentUid) && count($this->tableArray)) {
			// If updateToUid is not a positive integer, set it to '0', so it will be ignored
			if (!(\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($updateToUid) && $updateToUid > 0)) {
				$updateToUid = 0;
			}
			$considerWorkspaces = $GLOBALS['BE_USER']->workspace !== 0 && \TYPO3\CMS\Backend\Utility\BackendUtility::isTableWorkspaceEnabled($foreign_table);
			$fields = 'uid,' . $foreign_field;
			// Consider the symmetric field if defined:
			if ($symmetric_field) {
				$fields .= ',' . $symmetric_field;
			}
			// Consider workspaces if defined and currently used:
			if ($considerWorkspaces) {
				$fields .= ',' . 't3ver_state,t3ver_oid';
			}
			// Update all items
			foreach ($this->itemArray as $val) {
				$uid = $val['id'];
				$table = $val['table'];
				// Fetch the current (not overwritten) relation record if we should handle symmetric relations
				if ($symmetric_field || $considerWorkspaces) {
					$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid, $fields, '', FALSE);
				}
				if ($symmetric_field) {
					$isOnSymmetricSide = self::isOnSymmetricSide($parentUid, $conf, $row);
				}
				$updateValues = $foreign_match_fields;
				$workspaceValues = array();
				// No update to the uid is requested, so this is the normal behaviour
				// just update the fields and care about sorting
				if (!$updateToUid) {
					// Always add the pointer to the parent uid
					if ($isOnSymmetricSide) {
						$updateValues[$symmetric_field] = $parentUid;
					} else {
						$updateValues[$foreign_field] = $parentUid;
					}
					// If it is configured in TCA also to store the parent table in the child record, just do it
					if ($foreign_table_field && $this->currentTable) {
						$updateValues[$foreign_table_field] = $this->currentTable;
					}
					// Update sorting columns if not to be skipped
					if (!$skipSorting) {
						// Get the correct sorting field
						// Specific manual sortby for data handled by this field
						if ($conf['foreign_sortby']) {
							$sortby = $conf['foreign_sortby'];
						} elseif ($GLOBALS['TCA'][$foreign_table]['ctrl']['sortby']) {
							// manual sortby for all table records
							$sortby = $GLOBALS['TCA'][$foreign_table]['ctrl']['sortby'];
						}
						// Apply sorting on the symmetric side (it depends on who created the relation, so what uid is in the symmetric_field):
						if ($isOnSymmetricSide && isset($conf['symmetric_sortby']) && $conf['symmetric_sortby']) {
							$sortby = $conf['symmetric_sortby'];
						} else {
							$sortby = $GLOBALS['TYPO3_DB']->stripOrderBy($sortby);
						}
						if ($sortby) {
							$updateValues[$sortby] = ($workspaceValues[$sortby] = ++$c);
						}
					}
				} else {
					if ($isOnSymmetricSide) {
						$updateValues[$symmetric_field] = $updateToUid;
					} else {
						$updateValues[$foreign_field] = $updateToUid;
					}
				}
				// Update accordant fields in the database:
				if (count($updateValues)) {
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($uid), $updateValues);
					$this->updateRefIndex($table, $uid);
				}
				// Update accordant fields in the database for workspaces overlays/placeholders:
				if (count($workspaceValues) && $considerWorkspaces) {
					if (isset($row['t3ver_oid']) && $row['t3ver_oid'] && $row['t3ver_state'] == -1) {
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($row['t3ver_oid']), $workspaceValues);
					}
				}
			}
		}
	}

	/**
	 * After initialization you can extract an array of the elements from the object. Use this function for that.
	 *
	 * @param boolean $prependTableName If set, then table names will ALWAYS be prepended (unless its a _NO_TABLE value)
	 * @return array A numeric array.
	 * @todo Define visibility
	 */
	public function getValueArray($prependTableName = '') {
		// INIT:
		$valueArray = array();
		$tableC = count($this->tableArray);
		// If there are tables in the table array:
		if ($tableC) {
			// If there are more than ONE table in the table array, then always prepend table names:
			$prep = $tableC > 1 || $prependTableName ? 1 : 0;
			// Traverse the array of items:
			foreach ($this->itemArray as $val) {
				$valueArray[] = ($prep && $val['table'] != '_NO_TABLE' ? $val['table'] . '_' : '') . $val['id'];
			}
		}
		// Return the array
		return $valueArray;
	}

	/**
	 * Converts id numbers from negative to positive.
	 *
	 * @param array $valueArray Array of [table]_[id] pairs.
	 * @param string $fTable Foreign table (the one used for positive numbers)
	 * @param string $nfTable Negative foreign table
	 * @return array The array with ID integer values, converted to positive for those where the table name was set but did NOT match the positive foreign table.
	 * @todo Define visibility
	 */
	public function convertPosNeg($valueArray, $fTable, $nfTable) {
		if (is_array($valueArray) && $fTable) {
			foreach ($valueArray as $key => $val) {
				$val = strrev($val);
				$parts = explode('_', $val, 2);
				$theID = strrev($parts[0]);
				$theTable = strrev($parts[1]);
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($theID) && (!$theTable || !strcmp($theTable, $fTable) || !strcmp($theTable, $nfTable))) {
					$valueArray[$key] = $theTable && strcmp($theTable, $fTable) ? $theID * -1 : $theID;
				}
			}
		}
		return $valueArray;
	}

	/**
	 * Reads all records from internal tableArray into the internal ->results array where keys are table names and for each table, records are stored with uids as their keys.
	 * If $this->fetchAllFields is false you can save a little memory since only uid,pid and a few other fields are selected.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function getFromDB() {
		// Traverses the tables listed:
		foreach ($this->tableArray as $key => $val) {
			if (is_array($val)) {
				$itemList = implode(',', $val);
				if ($itemList) {
					if ($this->fetchAllFields) {
						$from = '*';
					} else {
						$from = 'uid,pid';
						if ($GLOBALS['TCA'][$key]['ctrl']['label']) {
							// Titel
							$from .= ',' . $GLOBALS['TCA'][$key]['ctrl']['label'];
						}
						if ($GLOBALS['TCA'][$key]['ctrl']['label_alt']) {
							// Alternative Title-Fields
							$from .= ',' . $GLOBALS['TCA'][$key]['ctrl']['label_alt'];
						}
						if ($GLOBALS['TCA'][$key]['ctrl']['thumbnail']) {
							// Thumbnail
							$from .= ',' . $GLOBALS['TCA'][$key]['ctrl']['thumbnail'];
						}
					}
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($from, $key, 'uid IN (' . $itemList . ')' . $this->additionalWhere[$key]);
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						$this->results[$key][$row['uid']] = $row;
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
			}
		}
		return $this->results;
	}

	/**
	 * Prepare items from itemArray to be transferred to the TCEforms interface (as a comma list)
	 *
	 * @return string
	 * @todo Define visibility
	 */
	public function readyForInterface() {
		if (!is_array($this->itemArray)) {
			return FALSE;
		}
		$output = array();
		// For use when getting the paths
		$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
		foreach ($this->itemArray as $key => $val) {
			$theRow = $this->results[$val['table']][$val['id']];
			if ($theRow && is_array($GLOBALS['TCA'][$val['table']])) {
				$label = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(strip_tags(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($val['table'], $theRow)), $titleLen);
				$label = $label ? $label : '[...]';
				$output[] = str_replace(',', '', $val['table'] . '_' . $val['id'] . '|' . rawurlencode($label));
			}
		}
		return implode(',', $output);
	}

	/**
	 * Counts the items in $this->itemArray and puts this value in an array by default.
	 *
	 * @param boolean $returnAsArray Whether to put the count value in an array
	 * @return mixed The plain count as integer or the same inside an array
	 * @todo Define visibility
	 */
	public function countItems($returnAsArray = TRUE) {
		$count = count($this->itemArray);
		if ($returnAsArray) {
			$count = array($count);
		}
		return $count;
	}

	/**
	 * Update Reference Index (sys_refindex) for a record
	 * Should be called any almost any update to a record which could affect references inside the record.
	 * (copied from TCEmain)
	 *
	 * @param string $table Table name
	 * @param integer $id Record UID
	 * @return array Information concerning modifications delivered by \TYPO3\CMS\Core\Database\ReferenceIndex::updateRefIndexTable()
	 * @todo Define visibility
	 */
	public function updateRefIndex($table, $id) {
		if ($this->updateReferenceIndex === TRUE) {
			/** @var $refIndexObj \TYPO3\CMS\Core\Database\ReferenceIndex */
			$refIndexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
			return $refIndexObj->updateRefIndexTable($table, $id);
		}
	}

	/**
	 * Checks, if we're looking from the "other" side, the symmetric side, to a symmetric relation.
	 *
	 * @param string $parentUid The uid of the parent record
	 * @param array $parentConf The TCA configuration of the parent field embedding the child records
	 * @param array $childRec The record row of the child record
	 * @return boolean Returns TRUE if looking from the symmetric ("other") side to the relation.
	 * @todo Define visibility
	 */
	public function isOnSymmetricSide($parentUid, $parentConf, $childRec) {
		return \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($childRec['uid']) && $parentConf['symmetric_field'] && $parentUid == $childRec[$parentConf['symmetric_field']] ? TRUE : FALSE;
	}

}


?>
